<?php
// api/forum.php — Forum: categories, posts, replies, reactions

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';

match ($action) {
    'categories'    => handleCategories(),
    'posts'         => handlePosts(),
    'post'          => handlePost(),
    'create_post'   => handleCreatePost(),
    'edit_post'     => handleEditPost(),
    'delete_post'   => handleDeletePost(),
    'replies'       => handleReplies(),
    'create_reply'  => handleCreateReply(),
    'edit_reply'    => handleEditReply(),
    'delete_reply'  => handleDeleteReply(),
    'react'         => handleReact(),
    'recent'        => handleRecent(),
    default         => jsonResponse(['error' => 'Ação inválida'], 400),
};

// ── CATEGORIES ───────────────────────────────────────────────
function handleCategories(): never {
    $db   = getDB();
    $stmt = $db->query('
        SELECT fc.*,
               COUNT(DISTINCT fp.id) AS post_count
        FROM forum_categories fc
        LEFT JOIN forum_posts fp ON fp.category_id = fc.id
        WHERE fc.is_active = 1
        GROUP BY fc.id
        ORDER BY fc.sort_order ASC
    ');
    jsonResponse($stmt->fetchAll());
}

// ── POST LIST ────────────────────────────────────────────────
function handlePosts(): never {
    $db          = getDB();
    $categoryId  = (int)($_GET['category_id'] ?? 0);
    $page        = max(1, (int)($_GET['page'] ?? 1));
    $perPage     = 20;
    $offset      = ($page - 1) * $perPage;

    $where  = $categoryId ? 'WHERE fp.category_id = ?' : 'WHERE 1';
    $params = $categoryId ? [$categoryId] : [];

    $stmt = $db->prepare("
        SELECT fp.id, fp.title, fp.slug, fp.is_pinned, fp.is_locked,
               fp.view_count, fp.reply_count, fp.last_reply_at, fp.created_at,
               p.id AS author_id, p.username AS author, p.avatar AS author_avatar, p.rank AS author_rank,
               fc.name AS category_name, fc.slug AS category_slug
        FROM forum_posts fp
        JOIN players p ON p.id = fp.author_id
        JOIN forum_categories fc ON fc.id = fp.category_id
        {$where}
        ORDER BY fp.is_pinned DESC, fp.last_reply_at DESC, fp.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $params[] = $perPage;
    $params[] = $offset;
    $stmt->execute($params);
    $posts = $stmt->fetchAll();

    // Total count
    $countStmt = $db->prepare("SELECT COUNT(*) FROM forum_posts fp {$where}");
    $countStmt->execute($categoryId ? [$categoryId] : []);
    $total = (int)$countStmt->fetchColumn();

    jsonResponse([
        'posts'    => $posts,
        'total'    => $total,
        'page'     => $page,
        'per_page' => $perPage,
        'pages'    => ceil($total / $perPage),
    ]);
}

// ── SINGLE POST ───────────────────────────────────────────────
function handlePost(): never {
    $db   = getDB();
    $slug = $_GET['slug'] ?? '';
    $id   = (int)($_GET['id'] ?? 0);

    if ($slug) {
        $stmt = $db->prepare('
            SELECT fp.*, p.username AS author, p.avatar AS author_avatar,
                   p.rank AS author_rank, p.kills AS author_kills,
                   p.created_at AS author_joined, fc.name AS category_name
            FROM forum_posts fp
            JOIN players p ON p.id = fp.author_id
            JOIN forum_categories fc ON fc.id = fp.category_id
            WHERE fp.slug = ?
        ');
        $stmt->execute([$slug]);
    } else {
        $stmt = $db->prepare('
            SELECT fp.*, p.username AS author, p.avatar AS author_avatar,
                   p.rank AS author_rank, fc.name AS category_name
            FROM forum_posts fp
            JOIN players p ON p.id = fp.author_id
            JOIN forum_categories fc ON fc.id = fp.category_id
            WHERE fp.id = ?
        ');
        $stmt->execute([$id]);
    }

    $post = $stmt->fetch();
    if (!$post) jsonResponse(['error' => 'Post não encontrado'], 404);

    // Increment view count
    $db->prepare('UPDATE forum_posts SET view_count = view_count + 1 WHERE id = ?')
       ->execute([$post['id']]);

    jsonResponse($post);
}

// ── CREATE POST ───────────────────────────────────────────────
function handleCreatePost(): never {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'Método inválido'], 405);
    $me   = requireAuth();
    $data = json_decode(file_get_contents('php://input'), true) ?? [];

    $title      = sanitize($data['title']       ?? '');
    $body       = trim($data['body']            ?? '');
    $categoryId = (int)($data['category_id']   ?? 0);

    if (strlen($title) < 5)  jsonResponse(['error' => 'Título muito curto (min 5 chars)'], 422);
    if (strlen($body)  < 10) jsonResponse(['error' => 'Corpo muito curto (min 10 chars)'], 422);
    if (!$categoryId)        jsonResponse(['error' => 'Categoria obrigatória'], 422);

    $db   = getDB();
    $slug = slugify($title) . '-' . substr(uniqid(), -6);

    $db->prepare('
        INSERT INTO forum_posts (category_id, author_id, title, slug, body, last_reply_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ')->execute([$categoryId, $me['id'], $title, $slug, $body]);

    $postId = (int)$db->lastInsertId();
    jsonResponse(['success' => true, 'post_id' => $postId, 'slug' => $slug], 201);
}

// ── EDIT POST ─────────────────────────────────────────────────
function handleEditPost(): never {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'Método inválido'], 405);
    $me   = requireAuth();
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    $id   = (int)($data['id'] ?? 0);

    $db   = getDB();
    $post = $db->prepare('SELECT * FROM forum_posts WHERE id = ?');
    $post->execute([$id]);
    $post = $post->fetch();
    if (!$post) jsonResponse(['error' => 'Post não encontrado'], 404);

    // Only author or admin may edit
    if ((int)$post['author_id'] !== (int)$me['id'] && !$me['is_admin']) {
        jsonResponse(['error' => 'Sem permissão'], 403);
    }
    if ($post['is_locked'] && !$me['is_admin']) {
        jsonResponse(['error' => 'Post bloqueado'], 403);
    }

    $fields = [];
    $values = [];
    if (!empty($data['title'])) { $fields[] = 'title = ?'; $values[] = sanitize($data['title']); }
    if (!empty($data['body']))  { $fields[] = 'body = ?';  $values[] = $data['body']; }

    if (empty($fields)) jsonResponse(['error' => 'Nada para editar'], 422);

    $values[] = $id;
    $db->prepare('UPDATE forum_posts SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($values);
    jsonResponse(['success' => true]);
}

// ── DELETE POST ───────────────────────────────────────────────
function handleDeletePost(): never {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'Método inválido'], 405);
    $me   = requireAuth();
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    $id   = (int)($data['id'] ?? 0);

    $db   = getDB();
    $stmt = $db->prepare('SELECT author_id FROM forum_posts WHERE id = ?');
    $stmt->execute([$id]);
    $post = $stmt->fetch();
    if (!$post) jsonResponse(['error' => 'Post não encontrado'], 404);
    if ((int)$post['author_id'] !== (int)$me['id'] && !$me['is_admin']) {
        jsonResponse(['error' => 'Sem permissão'], 403);
    }

    $db->prepare('DELETE FROM forum_posts WHERE id = ?')->execute([$id]);
    jsonResponse(['success' => true]);
}

// ── REPLIES ───────────────────────────────────────────────────
function handleReplies(): never {
    $db     = getDB();
    $postId = (int)($_GET['post_id'] ?? 0);
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $perPage = 30;
    $offset = ($page - 1) * $perPage;

    if (!$postId) jsonResponse(['error' => 'post_id obrigatório'], 422);

    $stmt = $db->prepare('
        SELECT fr.id, fr.body, fr.is_edited, fr.created_at, fr.updated_at,
               p.id AS author_id, p.username AS author, p.avatar AS author_avatar,
               p.rank AS author_rank,
               (SELECT COUNT(*) FROM forum_reactions WHERE target_type="reply" AND target_id=fr.id AND type="like") AS likes,
               (SELECT COUNT(*) FROM forum_reactions WHERE target_type="reply" AND target_id=fr.id AND type="fire") AS fires
        FROM forum_replies fr
        JOIN players p ON p.id = fr.author_id
        WHERE fr.post_id = ?
        ORDER BY fr.created_at ASC
        LIMIT ? OFFSET ?
    ');
    $stmt->execute([$postId, $perPage, $offset]);
    $replies = $stmt->fetchAll();

    $total = (int)$db->prepare('SELECT COUNT(*) FROM forum_replies WHERE post_id = ?')
                     ->execute([$postId]) ? $db->prepare('SELECT COUNT(*) FROM forum_replies WHERE post_id = ?')
                     ->execute([$postId]) : 0;

    // Simpler count
    $c = $db->prepare('SELECT COUNT(*) FROM forum_replies WHERE post_id = ?');
    $c->execute([$postId]);
    $total = (int)$c->fetchColumn();

    jsonResponse([
        'replies'  => $replies,
        'total'    => $total,
        'page'     => $page,
        'per_page' => $perPage,
    ]);
}

// ── CREATE REPLY ──────────────────────────────────────────────
function handleCreateReply(): never {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'Método inválido'], 405);
    $me   = requireAuth();
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    $postId = (int)($data['post_id'] ?? 0);
    $body   = trim($data['body']    ?? '');

    if (!$postId)            jsonResponse(['error' => 'post_id obrigatório'], 422);
    if (strlen($body) < 2)  jsonResponse(['error' => 'Resposta muito curta'], 422);

    $db = getDB();

    // Check post not locked
    $stmt = $db->prepare('SELECT is_locked FROM forum_posts WHERE id = ?');
    $stmt->execute([$postId]);
    $post = $stmt->fetch();
    if (!$post) jsonResponse(['error' => 'Post não encontrado'], 404);
    if ($post['is_locked'] && !$me['is_admin']) jsonResponse(['error' => 'Post bloqueado'], 403);

    $db->prepare('INSERT INTO forum_replies (post_id, author_id, body) VALUES (?, ?, ?)')
       ->execute([$postId, $me['id'], $body]);
    $replyId = (int)$db->lastInsertId();

    // Update reply count & last_reply_at on post
    $db->prepare('UPDATE forum_posts SET reply_count = reply_count + 1, last_reply_at = NOW() WHERE id = ?')
       ->execute([$postId]);

    jsonResponse(['success' => true, 'reply_id' => $replyId], 201);
}

// ── EDIT REPLY ────────────────────────────────────────────────
function handleEditReply(): never {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'Método inválido'], 405);
    $me   = requireAuth();
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    $id   = (int)($data['id']   ?? 0);
    $body = trim($data['body'] ?? '');

    if (!$id || strlen($body) < 2) jsonResponse(['error' => 'Dados inválidos'], 422);

    $db   = getDB();
    $stmt = $db->prepare('SELECT author_id FROM forum_replies WHERE id = ?');
    $stmt->execute([$id]);
    $reply = $stmt->fetch();
    if (!$reply) jsonResponse(['error' => 'Resposta não encontrada'], 404);
    if ((int)$reply['author_id'] !== (int)$me['id'] && !$me['is_admin']) {
        jsonResponse(['error' => 'Sem permissão'], 403);
    }

    $db->prepare('UPDATE forum_replies SET body = ?, is_edited = 1 WHERE id = ?')->execute([$body, $id]);
    jsonResponse(['success' => true]);
}

// ── DELETE REPLY ──────────────────────────────────────────────
function handleDeleteReply(): never {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'Método inválido'], 405);
    $me   = requireAuth();
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    $id   = (int)($data['id'] ?? 0);

    $db   = getDB();
    $stmt = $db->prepare('SELECT author_id, post_id FROM forum_replies WHERE id = ?');
    $stmt->execute([$id]);
    $reply = $stmt->fetch();
    if (!$reply) jsonResponse(['error' => 'Resposta não encontrada'], 404);
    if ((int)$reply['author_id'] !== (int)$me['id'] && !$me['is_admin']) {
        jsonResponse(['error' => 'Sem permissão'], 403);
    }

    $db->prepare('DELETE FROM forum_replies WHERE id = ?')->execute([$id]);
    $db->prepare('UPDATE forum_posts SET reply_count = GREATEST(reply_count - 1, 0) WHERE id = ?')
       ->execute([$reply['post_id']]);
    jsonResponse(['success' => true]);
}

// ── REACT ─────────────────────────────────────────────────────
function handleReact(): never {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'Método inválido'], 405);
    $me   = requireAuth();
    $data = json_decode(file_get_contents('php://input'), true) ?? [];

    $targetType = in_array($data['target_type'] ?? '', ['post','reply']) ? $data['target_type'] : null;
    $targetId   = (int)($data['target_id']   ?? 0);
    $type       = in_array($data['type'] ?? '', ['like','fire','gg']) ? $data['type'] : 'like';

    if (!$targetType || !$targetId) jsonResponse(['error' => 'Dados inválidos'], 422);

    $db = getDB();
    // Toggle: if already reacted with same type, remove; else insert/update
    $exists = $db->prepare('
        SELECT type FROM forum_reactions
        WHERE player_id=? AND target_type=? AND target_id=?
    ');
    $exists->execute([$me['id'], $targetType, $targetId]);
    $current = $exists->fetchColumn();

    if ($current === $type) {
        $db->prepare('DELETE FROM forum_reactions WHERE player_id=? AND target_type=? AND target_id=?')
           ->execute([$me['id'], $targetType, $targetId]);
        jsonResponse(['action' => 'removed', 'type' => $type]);
    } else {
        $db->prepare('
            INSERT INTO forum_reactions (player_id, target_type, target_id, type)
            VALUES (?,?,?,?)
            ON DUPLICATE KEY UPDATE type = VALUES(type)
        ')->execute([$me['id'], $targetType, $targetId, $type]);
        jsonResponse(['action' => 'added', 'type' => $type]);
    }
}

// ── RECENT ACTIVITY (for homepage) ───────────────────────────
function handleRecent(): never {
    $db   = getDB();
    $stmt = $db->query('
        SELECT fp.id, fp.title, fp.slug, fp.reply_count, fp.created_at,
               p.username AS author, p.avatar AS author_avatar
        FROM forum_posts fp
        JOIN players p ON p.id = fp.author_id
        ORDER BY fp.last_reply_at DESC, fp.created_at DESC
        LIMIT 5
    ');
    jsonResponse($stmt->fetchAll());
}

<?php
// api/news.php — News & Clips

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';

match ($action) {
    'list'          => handleList(),
    'single'        => handleSingle(),
    'create'        => handleCreate(),
    'clips'         => handleClips(),
    'create_clip'   => handleCreateClip(),
    default         => jsonResponse(['error' => 'Ação inválida'], 400),
};

function handleList(): never {
    $db    = getDB();
    $limit = min((int)($_GET['limit'] ?? 10), 50);
    $page  = max(1, (int)($_GET['page'] ?? 1));
    $stmt  = $db->prepare('
        SELECT n.id, n.title, n.slug, n.excerpt, n.thumbnail, n.published_at,
               p.username AS author, p.avatar AS author_avatar
        FROM news n
        JOIN players p ON p.id = n.author_id
        WHERE n.is_published = 1
        ORDER BY n.published_at DESC
        LIMIT ? OFFSET ?
    ');
    $stmt->execute([$limit, ($page - 1) * $limit]);
    jsonResponse($stmt->fetchAll());
}

function handleSingle(): never {
    $db   = getDB();
    $slug = $_GET['slug'] ?? '';
    if (!$slug) jsonResponse(['error' => 'slug obrigatório'], 422);

    $stmt = $db->prepare('
        SELECT n.*, p.username AS author, p.avatar AS author_avatar
        FROM news n JOIN players p ON p.id = n.author_id
        WHERE n.slug = ? AND n.is_published = 1
    ');
    $stmt->execute([$slug]);
    $news = $stmt->fetch();
    if (!$news) jsonResponse(['error' => 'Notícia não encontrada'], 404);
    jsonResponse($news);
}

function handleCreate(): never {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'Método inválido'], 405);
    $me   = requireAdmin();
    $data = json_decode(file_get_contents('php://input'), true) ?? [];

    $title   = sanitize($data['title']   ?? '');
    $excerpt = sanitize($data['excerpt'] ?? '');
    $body    = $data['body']             ?? '';
    if (strlen($title) < 5 || strlen($body) < 10) jsonResponse(['error' => 'Dados inválidos'], 422);

    $db   = getDB();
    $slug = slugify($title) . '-' . substr(uniqid(), -6);
    $db->prepare('
        INSERT INTO news (author_id, title, slug, excerpt, body, thumbnail, is_published, published_at)
        VALUES (?,?,?,?,?,?,?,NOW())
    ')->execute([
        $me['id'], $title, $slug, $excerpt, $body,
        $data['thumbnail'] ?? null,
        (int)(bool)($data['publish'] ?? true),
    ]);
    jsonResponse(['success' => true, 'slug' => $slug], 201);
}

function handleClips(): never {
    $db       = getDB();
    $featured = isset($_GET['featured']) ? 'AND c.is_featured = 1' : '';
    $limit    = min((int)($_GET['limit'] ?? 12), 50);
    $stmt     = $db->prepare("
        SELECT c.id, c.title, c.thumbnail, c.video_url, c.duration, c.game, c.views, c.created_at,
               p.id AS player_id, p.username, p.avatar
        FROM clips c
        JOIN players p ON p.id = c.player_id
        WHERE 1 {$featured}
        ORDER BY c.is_featured DESC, c.views DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    jsonResponse($stmt->fetchAll());
}

function handleCreateClip(): never {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'Método inválido'], 405);
    $me   = requireAuth();
    $data = json_decode(file_get_contents('php://input'), true) ?? [];

    $title   = sanitize($data['title']     ?? '');
    $videoUrl = filter_var($data['video_url'] ?? '', FILTER_VALIDATE_URL);
    if (!$title || !$videoUrl) jsonResponse(['error' => 'title e video_url obrigatórios'], 422);

    $db = getDB();
    $db->prepare('
        INSERT INTO clips (player_id, title, thumbnail, video_url, duration, game)
        VALUES (?,?,?,?,?,?)
    ')->execute([
        $me['id'], $title,
        $data['thumbnail'] ?? null,
        $videoUrl,
        (int)($data['duration'] ?? 0),
        sanitize($data['game'] ?? ''),
    ]);
    jsonResponse(['success' => true, 'clip_id' => (int)$db->lastInsertId()], 201);
}

<?php
// api/players.php — Player profiles, stats, rankings, search

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';

match ($action) {
    'profile'    => handleProfile(),
    'stats'      => handleStats(),
    'rankings'   => handleRankings(),
    'search'     => handleSearch(),
    'online'     => handleOnline(),
    'update'     => handleUpdate(),
    'friends'    => handleFriends(),
    'add_friend' => handleAddFriend(),
    default      => jsonResponse(['error' => 'Ação inválida'], 400),
};

// ── PROFILE ──────────────────────────────────────────────────
function handleProfile(): never {
    $db       = getDB();
    $id       = (int)($_GET['id'] ?? 0);
    $username = $_GET['username'] ?? '';

    if ($id) {
        $stmt = $db->prepare('SELECT * FROM players WHERE id = ? AND is_banned = 0');
        $stmt->execute([$id]);
    } elseif ($username) {
        $stmt = $db->prepare('SELECT * FROM players WHERE username = ? AND is_banned = 0');
        $stmt->execute([$username]);
    } else {
        jsonResponse(['error' => 'id ou username obrigatório'], 422);
    }

    $player = $stmt->fetch();
    if (!$player) jsonResponse(['error' => 'Jogador não encontrado'], 404);

    // Achievements
    $ach = $db->prepare('
        SELECT a.*, pa.earned_at
        FROM achievements a
        JOIN player_achievements pa ON a.id = pa.achievement_id
        WHERE pa.player_id = ?
        ORDER BY pa.earned_at DESC
    ');
    $ach->execute([$player['id']]);

    // Per-game stats
    $stats = $db->prepare('SELECT * FROM player_stats WHERE player_id = ?');
    $stats->execute([$player['id']]);

    jsonResponse([
        'player'       => publicPlayer($player),
        'achievements' => $ach->fetchAll(),
        'game_stats'   => $stats->fetchAll(),
    ]);
}

// ── STATS (update own stats) ─────────────────────────────────
function handleStats(): never {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'Método inválido'], 405);
    }
    $me   = requireAuth();
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    $game = sanitize($data['game'] ?? '');
    if (!$game) jsonResponse(['error' => 'game obrigatório'], 422);

    $db = getDB();
    $db->prepare('
        INSERT INTO player_stats (player_id, game, kills, deaths, assists, wins, losses, headshots, mvp_count)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            kills     = kills     + VALUES(kills),
            deaths    = deaths    + VALUES(deaths),
            assists   = assists   + VALUES(assists),
            wins      = wins      + VALUES(wins),
            losses    = losses    + VALUES(losses),
            headshots = headshots + VALUES(headshots),
            mvp_count = mvp_count + VALUES(mvp_count)
    ')->execute([
        $me['id'], $game,
        (int)($data['kills']     ?? 0),
        (int)($data['deaths']    ?? 0),
        (int)($data['assists']   ?? 0),
        (int)($data['wins']      ?? 0),
        (int)($data['losses']    ?? 0),
        (int)($data['headshots'] ?? 0),
        (int)($data['mvp_count'] ?? 0),
    ]);

    // Sync totals back to players table
    $db->prepare('
        UPDATE players p
        SET kills  = (SELECT COALESCE(SUM(kills),0)  FROM player_stats WHERE player_id = p.id),
            deaths = (SELECT COALESCE(SUM(deaths),0) FROM player_stats WHERE player_id = p.id),
            wins   = (SELECT COALESCE(SUM(wins),0)   FROM player_stats WHERE player_id = p.id),
            losses = (SELECT COALESCE(SUM(losses),0) FROM player_stats WHERE player_id = p.id)
        WHERE id = ?
    ')->execute([$me['id']]);

    jsonResponse(['success' => true]);
}

// ── RANKINGS ─────────────────────────────────────────────────
function handleRankings(): never {
    $db      = getDB();
    $sortBy  = in_array($_GET['sort'] ?? '', ['kills','wins','kd']) ? ($_GET['sort'] ?? 'kills') : 'kills';
    $limit   = min((int)($_GET['limit'] ?? 20), 100);

    $orderClause = match ($sortBy) {
        'kd'   => 'IF(deaths=0, kills, kills/deaths) DESC',
        'wins' => 'wins DESC',
        default => 'kills DESC',
    };

    $stmt = $db->prepare("
        SELECT id, username, avatar, rank, kills, deaths, wins, losses,
               IF(deaths=0, kills, ROUND(kills/deaths,2)) AS kd_ratio
        FROM players
        WHERE is_banned = 0
        ORDER BY {$orderClause}
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    jsonResponse($stmt->fetchAll());
}

// ── SEARCH ───────────────────────────────────────────────────
function handleSearch(): never {
    $q = trim($_GET['q'] ?? '');
    if (strlen($q) < 2) jsonResponse(['error' => 'Query muito curta'], 422);

    $db   = getDB();
    $stmt = $db->prepare('
        SELECT id, username, avatar, rank, is_online, current_game
        FROM players
        WHERE username LIKE ? AND is_banned = 0
        LIMIT 20
    ');
    $stmt->execute(['%' . $q . '%']);
    jsonResponse($stmt->fetchAll());
}

// ── ONLINE ───────────────────────────────────────────────────
function handleOnline(): never {
    $db   = getDB();
    $stmt = $db->query('
        SELECT id, username, avatar, rank, current_game
        FROM players
        WHERE is_online = 1 AND is_banned = 0
        ORDER BY username ASC
        LIMIT 50
    ');
    jsonResponse($stmt->fetchAll());
}

// ── UPDATE PROFILE ───────────────────────────────────────────
function handleUpdate(): never {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'Método inválido'], 405);
    $me   = requireAuth();
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    $db   = getDB();

    $fields = [];
    $values = [];

    if (!empty($data['bio'])) {
        $fields[] = 'bio = ?';
        $values[] = sanitize(substr($data['bio'], 0, 500));
    }
    if (!empty($data['current_game'])) {
        $fields[] = 'current_game = ?';
        $values[] = sanitize($data['current_game']);
    }
    if (isset($data['is_online'])) {
        $fields[] = 'is_online = ?';
        $values[] = (int)(bool)$data['is_online'];
    }

    if (empty($fields)) jsonResponse(['error' => 'Nada para atualizar'], 422);

    $values[] = $me['id'];
    $db->prepare('UPDATE players SET ' . implode(', ', $fields) . ' WHERE id = ?')
       ->execute($values);

    jsonResponse(['success' => true]);
}

// ── FRIENDS LIST ─────────────────────────────────────────────
function handleFriends(): never {
    $me   = requireAuth();
    $db   = getDB();
    $stmt = $db->prepare('
        SELECT p.id, p.username, p.avatar, p.rank, p.is_online, p.current_game,
               f.status, f.created_at AS since
        FROM friendships f
        JOIN players p ON p.id = IF(f.requester_id = ?, f.addressee_id, f.requester_id)
        WHERE (f.requester_id = ? OR f.addressee_id = ?) AND f.status = "accepted"
        ORDER BY p.is_online DESC, p.username ASC
    ');
    $stmt->execute([$me['id'], $me['id'], $me['id']]);
    jsonResponse($stmt->fetchAll());
}

// ── ADD FRIEND ───────────────────────────────────────────────
function handleAddFriend(): never {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'Método inválido'], 405);
    $me   = requireAuth();
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    $targetId = (int)($data['player_id'] ?? 0);
    if (!$targetId || $targetId === (int)$me['id']) {
        jsonResponse(['error' => 'ID inválido'], 422);
    }
    $db = getDB();
    try {
        $db->prepare('INSERT INTO friendships (requester_id, addressee_id) VALUES (?,?)')
           ->execute([$me['id'], $targetId]);
        jsonResponse(['success' => true, 'status' => 'pending']);
    } catch (\PDOException) {
        jsonResponse(['error' => 'Pedido já enviado'], 409);
    }
}

// ── Helpers ──────────────────────────────────────────────────
function publicPlayer(array $p): array {
    return [
        'id'          => $p['id'],
        'username'    => $p['username'],
        'avatar'      => $p['avatar'],
        'bio'         => $p['bio'],
        'rank'        => $p['rank'],
        'kills'       => $p['kills'],
        'deaths'      => $p['deaths'],
        'wins'        => $p['wins'],
        'losses'      => $p['losses'],
        'kd_ratio'    => $p['deaths'] == 0 ? $p['kills'] : round($p['kills'] / max(1,$p['deaths']),2),
        'playtime_hrs'=> $p['playtime_hrs'],
        'current_game'=> $p['current_game'],
        'is_online'   => (bool)$p['is_online'],
        'is_admin'    => (bool)$p['is_admin'],
        'last_seen'   => $p['last_seen'],
        'created_at'  => $p['created_at'],
    ];
}

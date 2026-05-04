<?php
// api/auth.php — Register / Login / Logout / Me

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';

match ($action) {
    'register' => handleRegister(),
    'login'    => handleLogin(),
    'logout'   => handleLogout(),
    'me'       => handleMe(),
    default    => jsonResponse(['error' => 'Ação inválida'], 400),
};

// ── REGISTER ────────────────────────────────────────────────
function handleRegister(): never {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'Método inválido'], 405);
    }
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $username = trim($data['username'] ?? '');
    $email    = trim($data['email']    ?? '');
    $password = $data['password']      ?? '';

    // Validation
    if (strlen($username) < 3 || strlen($username) > 32) {
        jsonResponse(['error' => 'Username deve ter entre 3 e 32 caracteres'], 422);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['error' => 'Email inválido'], 422);
    }
    if (strlen($password) < 8) {
        jsonResponse(['error' => 'Password deve ter pelo menos 8 caracteres'], 422);
    }

    $db = getDB();

    // Check duplicates
    $stmt = $db->prepare('SELECT id FROM players WHERE username = ? OR email = ?');
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        jsonResponse(['error' => 'Username ou email já em uso'], 409);
    }

    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $db->prepare(
        'INSERT INTO players (username, email, password_hash) VALUES (?, ?, ?)'
    );
    $stmt->execute([$username, $email, $hash]);
    $playerId = (int) $db->lastInsertId();

    // Auto-login after register
    $player = fetchPlayer($db, $playerId);
    $_SESSION['player'] = $player;

    jsonResponse(['success' => true, 'player' => publicPlayer($player)], 201);
}

// ── LOGIN ────────────────────────────────────────────────────
function handleLogin(): never {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'Método inválido'], 405);
    }
    $data     = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $login    = trim($data['login']    ?? '');
    $password = $data['password']      ?? '';

    if (!$login || !$password) {
        jsonResponse(['error' => 'Preenche todos os campos'], 422);
    }

    $db   = getDB();
    $stmt = $db->prepare(
        'SELECT * FROM players WHERE (username = ? OR email = ?) AND is_banned = 0'
    );
    $stmt->execute([$login, $login]);
    $player = $stmt->fetch();

    if (!$player || !password_verify($password, $player['password_hash'])) {
        jsonResponse(['error' => 'Credenciais inválidas'], 401);
    }

    // Update online status & last seen
    $db->prepare('UPDATE players SET is_online = 1, last_seen = NOW() WHERE id = ?')
       ->execute([$player['id']]);

    $_SESSION['player'] = $player;
    jsonResponse(['success' => true, 'player' => publicPlayer($player)]);
}

// ── LOGOUT ───────────────────────────────────────────────────
function handleLogout(): never {
    $player = currentPlayer();
    if ($player) {
        getDB()->prepare('UPDATE players SET is_online = 0, last_seen = NOW() WHERE id = ?')
               ->execute([$player['id']]);
    }
    session_destroy();
    jsonResponse(['success' => true]);
}

// ── ME ───────────────────────────────────────────────────────
function handleMe(): never {
    $player = currentPlayer();
    if (!$player) {
        jsonResponse(['authenticated' => false]);
    }
    // Refresh from DB
    $fresh = fetchPlayer(getDB(), $player['id']);
    $_SESSION['player'] = $fresh;
    jsonResponse(['authenticated' => true, 'player' => publicPlayer($fresh)]);
}

// ── Helpers ──────────────────────────────────────────────────
function fetchPlayer(PDO $db, int $id): array|false {
    $stmt = $db->prepare('SELECT * FROM players WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function publicPlayer(array $p): array {
    return [
        'id'          => $p['id'],
        'username'    => $p['username'],
        'avatar'      => $p['avatar'],
        'rank'        => $p['rank'],
        'kills'       => $p['kills'],
        'deaths'      => $p['deaths'],
        'wins'        => $p['wins'],
        'losses'      => $p['losses'],
        'current_game'=> $p['current_game'],
        'is_online'   => (bool)$p['is_online'],
        'is_admin'    => (bool)$p['is_admin'],
        'created_at'  => $p['created_at'],
    ];
}

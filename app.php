<?php
// config/app.php — Global config & helpers

define('APP_NAME',    'NVR Community Hub');
define('APP_URL',     'http://localhost/nvr_hub'); // change to your domain
define('SESSION_TTL', 60 * 60 * 24 * 7);          // 7 days

// ── Session bootstrap ──────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => SESSION_TTL,
        'path'     => '/',
        'secure'   => false,      // set true on HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ── JSON response helper ────────────────────────────────────
function jsonResponse(mixed $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

// ── Auth helpers ────────────────────────────────────────────
function currentPlayer(): ?array {
    return $_SESSION['player'] ?? null;
}

function requireAuth(): array {
    $player = currentPlayer();
    if (!$player) {
        jsonResponse(['error' => 'Autenticação necessária'], 401);
    }
    return $player;
}

function requireAdmin(): array {
    $player = requireAuth();
    if (!$player['is_admin']) {
        jsonResponse(['error' => 'Acesso negado'], 403);
    }
    return $player;
}

// ── Utility ─────────────────────────────────────────────────
function slugify(string $text): string {
    $text = mb_strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

function sanitize(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function timeAgo(\DateTime|string $date): string {
    $time  = is_string($date) ? strtotime($date) : $date->getTimestamp();
    $diff  = time() - $time;
    return match(true) {
        $diff <  60      => 'agora mesmo',
        $diff <  3600    => floor($diff / 60) . ' min atrás',
        $diff <  86400   => floor($diff / 3600) . 'h atrás',
        $diff <  604800  => floor($diff / 86400) . 'd atrás',
        default          => date('d/m/Y', $time),
    };
}

function kd(int $kills, int $deaths): string {
    return $deaths === 0 ? number_format($kills, 2) : number_format($kills / $deaths, 2);
}

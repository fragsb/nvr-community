-- ============================================================
--  NVR COMMUNITY HUB — Database Schema
--  MySQL 8.0+
-- ============================================================

CREATE DATABASE IF NOT EXISTS nvr_hub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nvr_hub;

-- ──────────────────────────────────────────────
--  PLAYERS / USERS
-- ──────────────────────────────────────────────
CREATE TABLE players (
    id            INT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(32)      NOT NULL UNIQUE,
    email         VARCHAR(120)     NOT NULL UNIQUE,
    password_hash VARCHAR(255)     NOT NULL,
    avatar        VARCHAR(255)     DEFAULT NULL,
    bio           TEXT             DEFAULT NULL,
    rank          ENUM('Bronze','Silver','Gold','Platinum','Diamond','Master') DEFAULT 'Bronze',
    kills         INT UNSIGNED     DEFAULT 0,
    deaths        INT UNSIGNED     DEFAULT 0,
    wins          INT UNSIGNED     DEFAULT 0,
    losses        INT UNSIGNED     DEFAULT 0,
    playtime_hrs  DECIMAL(8,2)     DEFAULT 0.00,
    current_game  VARCHAR(64)      DEFAULT NULL,
    is_online     TINYINT(1)       DEFAULT 0,
    is_admin      TINYINT(1)       DEFAULT 0,
    is_banned     TINYINT(1)       DEFAULT 0,
    last_seen     DATETIME         DEFAULT NULL,
    created_at    DATETIME         DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME         DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_username  (username),
    INDEX idx_email     (email),
    INDEX idx_rank      (rank),
    INDEX idx_online    (is_online)
);

-- ──────────────────────────────────────────────
--  PLAYER STATS (detailed per-game)
-- ──────────────────────────────────────────────
CREATE TABLE player_stats (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    player_id   INT UNSIGNED NOT NULL,
    game        VARCHAR(64)  NOT NULL,
    kills       INT UNSIGNED DEFAULT 0,
    deaths      INT UNSIGNED DEFAULT 0,
    assists     INT UNSIGNED DEFAULT 0,
    wins        INT UNSIGNED DEFAULT 0,
    losses      INT UNSIGNED DEFAULT 0,
    headshots   INT UNSIGNED DEFAULT 0,
    mvp_count   INT UNSIGNED DEFAULT 0,
    updated_at  DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_player_game (player_id, game),
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE
);

-- ──────────────────────────────────────────────
--  PLAYER ACHIEVEMENTS
-- ──────────────────────────────────────────────
CREATE TABLE achievements (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(80)  NOT NULL,
    description TEXT,
    icon        VARCHAR(120) DEFAULT NULL,
    points      INT UNSIGNED DEFAULT 0
);

CREATE TABLE player_achievements (
    player_id      INT UNSIGNED NOT NULL,
    achievement_id INT UNSIGNED NOT NULL,
    earned_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (player_id, achievement_id),
    FOREIGN KEY (player_id)      REFERENCES players(id)      ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE
);

-- ──────────────────────────────────────────────
--  FRIENDS
-- ──────────────────────────────────────────────
CREATE TABLE friendships (
    requester_id INT UNSIGNED NOT NULL,
    addressee_id INT UNSIGNED NOT NULL,
    status       ENUM('pending','accepted','blocked') DEFAULT 'pending',
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (requester_id, addressee_id),
    FOREIGN KEY (requester_id) REFERENCES players(id) ON DELETE CASCADE,
    FOREIGN KEY (addressee_id) REFERENCES players(id) ON DELETE CASCADE
);

-- ──────────────────────────────────────────────
--  SESSIONS
-- ──────────────────────────────────────────────
CREATE TABLE sessions (
    token      CHAR(64)     PRIMARY KEY,
    player_id  INT UNSIGNED NOT NULL,
    ip_address VARCHAR(45)  DEFAULT NULL,
    user_agent TEXT         DEFAULT NULL,
    created_at DATETIME     DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME     NOT NULL,
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
    INDEX idx_player (player_id),
    INDEX idx_expires (expires_at)
);

-- ──────────────────────────────────────────────
--  FORUM — CATEGORIES
-- ──────────────────────────────────────────────
CREATE TABLE forum_categories (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(80)  NOT NULL,
    slug        VARCHAR(80)  NOT NULL UNIQUE,
    description TEXT         DEFAULT NULL,
    icon        VARCHAR(60)  DEFAULT NULL,
    sort_order  INT          DEFAULT 0,
    is_active   TINYINT(1)   DEFAULT 1,
    created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP
);

-- ──────────────────────────────────────────────
--  FORUM — POSTS (threads)
-- ──────────────────────────────────────────────
CREATE TABLE forum_posts (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id  INT UNSIGNED NOT NULL,
    author_id    INT UNSIGNED NOT NULL,
    title        VARCHAR(200) NOT NULL,
    slug         VARCHAR(220) NOT NULL UNIQUE,
    body         LONGTEXT     NOT NULL,
    is_pinned    TINYINT(1)   DEFAULT 0,
    is_locked    TINYINT(1)   DEFAULT 0,
    view_count   INT UNSIGNED DEFAULT 0,
    reply_count  INT UNSIGNED DEFAULT 0,
    last_reply_at DATETIME    DEFAULT NULL,
    created_at   DATETIME     DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_category  (category_id),
    INDEX idx_author    (author_id),
    INDEX idx_pinned    (is_pinned),
    INDEX idx_created   (created_at),
    FOREIGN KEY (category_id) REFERENCES forum_categories(id) ON DELETE CASCADE,
    FOREIGN KEY (author_id)   REFERENCES players(id)          ON DELETE CASCADE
);

-- ──────────────────────────────────────────────
--  FORUM — REPLIES
-- ──────────────────────────────────────────────
CREATE TABLE forum_replies (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id    INT UNSIGNED NOT NULL,
    author_id  INT UNSIGNED NOT NULL,
    body       LONGTEXT     NOT NULL,
    is_edited  TINYINT(1)   DEFAULT 0,
    created_at DATETIME     DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_post   (post_id),
    INDEX idx_author (author_id),
    FOREIGN KEY (post_id)   REFERENCES forum_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES players(id)     ON DELETE CASCADE
);

-- ──────────────────────────────────────────────
--  FORUM — REACTIONS (likes)
-- ──────────────────────────────────────────────
CREATE TABLE forum_reactions (
    player_id  INT UNSIGNED NOT NULL,
    target_type ENUM('post','reply') NOT NULL,
    target_id  INT UNSIGNED NOT NULL,
    type       ENUM('like','fire','gg') DEFAULT 'like',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (player_id, target_type, target_id),
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE
);

-- ──────────────────────────────────────────────
--  NEWS
-- ──────────────────────────────────────────────
CREATE TABLE news (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    author_id   INT UNSIGNED NOT NULL,
    title       VARCHAR(200) NOT NULL,
    slug        VARCHAR(220) NOT NULL UNIQUE,
    excerpt     TEXT         DEFAULT NULL,
    body        LONGTEXT     NOT NULL,
    thumbnail   VARCHAR(255) DEFAULT NULL,
    is_published TINYINT(1)  DEFAULT 0,
    published_at DATETIME    DEFAULT NULL,
    created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_published (is_published, published_at),
    FOREIGN KEY (author_id) REFERENCES players(id) ON DELETE CASCADE
);

-- ──────────────────────────────────────────────
--  CLIPS
-- ──────────────────────────────────────────────
CREATE TABLE clips (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    player_id   INT UNSIGNED NOT NULL,
    title       VARCHAR(150) NOT NULL,
    thumbnail   VARCHAR(255) DEFAULT NULL,
    video_url   VARCHAR(255) NOT NULL,
    duration    INT UNSIGNED DEFAULT 0,
    game        VARCHAR(64)  DEFAULT NULL,
    views       INT UNSIGNED DEFAULT 0,
    is_featured TINYINT(1)   DEFAULT 0,
    created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_player   (player_id),
    INDEX idx_featured (is_featured),
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE
);

-- ──────────────────────────────────────────────
--  TOURNAMENTS
-- ──────────────────────────────────────────────
CREATE TABLE tournaments (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(120) NOT NULL,
    description  TEXT         DEFAULT NULL,
    game         VARCHAR(64)  NOT NULL,
    prize_pool   VARCHAR(80)  DEFAULT NULL,
    max_players  INT UNSIGNED DEFAULT 32,
    starts_at    DATETIME     NOT NULL,
    ends_at      DATETIME     DEFAULT NULL,
    status       ENUM('upcoming','active','finished','cancelled') DEFAULT 'upcoming',
    created_at   DATETIME     DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE tournament_registrations (
    tournament_id INT UNSIGNED NOT NULL,
    player_id     INT UNSIGNED NOT NULL,
    registered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (tournament_id, player_id),
    FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
    FOREIGN KEY (player_id)     REFERENCES players(id)     ON DELETE CASCADE
);

-- ──────────────────────────────────────────────
--  SEED DATA
-- ──────────────────────────────────────────────

-- Default forum categories
INSERT INTO forum_categories (name, slug, description, icon, sort_order) VALUES
('General Discussion',  'general',    'Fala sobre qualquer coisa da comunidade',  '💬', 1),
('Game Strategies',     'strategies', 'Partilha estratégias e táticas de jogo',   '🎯', 2),
('Clips & Highlights',  'clips',      'Mostra os teus melhores momentos',          '🎬', 3),
('Bug Reports',         'bugs',       'Reporta erros e problemas técnicos',        '🐛', 4),
('Tournaments',         'tournaments','Discussão sobre torneios e competições',    '🏆', 5),
('Introductions',       'intros',     'Apresenta-te à comunidade',                '👋', 6);

-- Default achievements
INSERT INTO achievements (name, description, icon, points) VALUES
('First Blood',         'Primeira kill no jogo',                '🩸', 10),
('Headshot Master',     '100 headshots',                        '🎯', 50),
('Forum Veteran',       '100 posts no fórum',                   '📝', 30),
('Win Streak',          '10 vitórias consecutivas',             '🔥', 100),
('Community Star',      '500 likes recebidos no fórum',         '⭐', 75),
('Tournament Champion', 'Vencedor de um torneio',               '🏆', 200);

-- Admin / demo player (password: Admin123!)
INSERT INTO players (username, email, password_hash, rank, is_admin, kills, deaths, wins, losses, current_game, is_online) VALUES
('NVR_Admin',  'admin@nvrhub.gg',  '$2y$12$examplehashadmin123456789012345678901234567890123456', 'Master',   1, 0,   0,  0,  0, NULL,        0),
('Jmah',       'jmah@nvrhub.gg',   '$2y$12$examplehashjmah1234567890123456789012345678901234567', 'Diamond',  0, 1240, 380, 89, 21, 'Cyberpunk', 1),
('Kimly',      'kimly@nvrhub.gg',  '$2y$12$examplehashkimly123456789012345678901234567890123456', 'Platinum', 0, 870,  410, 62, 38, 'Cyberpunk', 1),
('Shartiien',  'shar@nvrhub.gg',   '$2y$12$examplehashshar1234567890123456789012345678901234567', 'Gold',     0, 540,  520, 41, 59, 'Cyberpunk', 1),
('Amrian',     'amr@nvrhub.gg',    '$2y$12$examplehashamr12345678901234567890123456789012345678', 'Silver',   0, 210,  680, 18, 82, 'Cyberpunk', 1);

CREATE TABLE IF NOT EXISTS chyrralon_players (
    auth_user_id VARCHAR(128) NOT NULL PRIMARY KEY,
    email VARCHAR(255) NULL,
    username VARCHAR(255) NULL,
    display_name VARCHAR(255) NULL,
    role VARCHAR(64) NOT NULL,
    roles_json JSON NOT NULL,
    auth_type VARCHAR(32) NOT NULL,
    is_guest TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_chyrralon_players_auth_type (auth_type),
    INDEX idx_chyrralon_players_is_guest (is_guest)
);

CREATE TABLE IF NOT EXISTS chyrralon_games (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    game_id VARCHAR(64) NOT NULL,
    owner_auth_user_id VARCHAR(128) NOT NULL,
    state_json JSON NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_chyrralon_games_game_id (game_id),
    INDEX idx_chyrralon_games_owner (owner_auth_user_id),
    CONSTRAINT fk_chyrralon_games_owner
        FOREIGN KEY (owner_auth_user_id)
        REFERENCES chyrralon_players (auth_user_id)
        ON DELETE CASCADE
);

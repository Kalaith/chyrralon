<?php

declare(strict_types=1);

namespace Chyrralon\Repositories;

use Chyrralon\Models\AuthUser;
use PDO;
use RuntimeException;

final class GameRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function upsertPlayer(AuthUser $user): void
    {
        $sql = <<<'SQL'
            INSERT INTO chyrralon_players (
                auth_user_id, email, username, display_name, role, roles_json, auth_type, is_guest, created_at, updated_at
            ) VALUES (
                :auth_user_id, :email, :username, :display_name, :role, :roles_json, :auth_type, :is_guest, NOW(), NOW()
            )
            ON DUPLICATE KEY UPDATE
                email = VALUES(email),
                username = VALUES(username),
                display_name = VALUES(display_name),
                role = VALUES(role),
                roles_json = VALUES(roles_json),
                auth_type = VALUES(auth_type),
                is_guest = VALUES(is_guest),
                updated_at = NOW()
            SQL;

        $statement = $this->pdo->prepare($sql);
        $statement->execute([
            ':auth_user_id' => $user->id,
            ':email' => $user->email,
            ':username' => $user->username,
            ':display_name' => $user->displayName,
            ':role' => $user->role,
            ':roles_json' => json_encode($user->roles, JSON_THROW_ON_ERROR),
            ':auth_type' => $user->authType,
            ':is_guest' => $user->isGuest ? 1 : 0,
        ]);
    }

    /**
     * @param array<string, mixed> $state
     */
    public function createGame(AuthUser $owner, string $gameId, array $state): void
    {
        $this->upsertPlayer($owner);

        $statement = $this->pdo->prepare(
            'INSERT INTO chyrralon_games (game_id, owner_auth_user_id, state_json, created_at, updated_at)
             VALUES (:game_id, :owner_auth_user_id, :state_json, NOW(), NOW())'
        );
        $statement->execute([
            ':game_id' => $gameId,
            ':owner_auth_user_id' => $owner->id,
            ':state_json' => json_encode($state, JSON_THROW_ON_ERROR),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findOwnedGame(AuthUser $owner, string $gameId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT state_json FROM chyrralon_games WHERE game_id = :game_id AND owner_auth_user_id = :owner_auth_user_id LIMIT 1'
        );
        $statement->execute([
            ':game_id' => $gameId,
            ':owner_auth_user_id' => $owner->id,
        ]);

        $row = $statement->fetch();
        if (!is_array($row) || !isset($row['state_json']) || !is_string($row['state_json'])) {
            return null;
        }

        $state = json_decode($row['state_json'], true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($state)) {
            throw new RuntimeException('Stored game state is invalid.');
        }

        return $state;
    }

    /**
     * @param array<string, mixed> $state
     */
    public function saveOwnedGame(AuthUser $owner, string $gameId, array $state): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE chyrralon_games
             SET state_json = :state_json, updated_at = NOW()
             WHERE game_id = :game_id AND owner_auth_user_id = :owner_auth_user_id'
        );
        $statement->execute([
            ':state_json' => json_encode($state, JSON_THROW_ON_ERROR),
            ':game_id' => $gameId,
            ':owner_auth_user_id' => $owner->id,
        ]);

        if ($statement->rowCount() < 1) {
            throw new RuntimeException('Game not found for authenticated player.');
        }
    }

    public function moveGuestGamesToUser(string $guestUserId, AuthUser $targetUser): int
    {
        $this->upsertPlayer($targetUser);
        $this->pdo->beginTransaction();

        try {
            $statement = $this->pdo->prepare(
                'UPDATE chyrralon_games
                 SET owner_auth_user_id = :target_user_id, updated_at = NOW()
                 WHERE owner_auth_user_id = :source_user_id'
            );
            $statement->execute([
                ':target_user_id' => $targetUser->id,
                ':source_user_id' => $guestUserId,
            ]);

            $movedRows = $statement->rowCount();

            $deletePlayer = $this->pdo->prepare('DELETE FROM chyrralon_players WHERE auth_user_id = :source_user_id');
            $deletePlayer->execute([':source_user_id' => $guestUserId]);

            $this->pdo->commit();
            return $movedRows;
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }
}

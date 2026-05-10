<?php

declare(strict_types=1);

namespace Chyrralon\Actions;

use Chyrralon\Models\AuthUser;
use Chyrralon\Repositories\GameRepository;
use Chyrralon\Services\GameEngine;
use RuntimeException;

final class GameActions
{
    public function __construct(
        private readonly GameRepository $repository,
        private readonly GameEngine $engine
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function create(AuthUser $user): array
    {
        $gameId = 'game_' . bin2hex(random_bytes(12));
        $gameState = $this->engine->createGame($gameId, $user->displayName ?? $user->username ?? 'Player 1');
        $this->repository->createGame($user, $gameId, $gameState);

        return $gameState;
    }

    /**
     * @return array<string, mixed>
     */
    public function get(AuthUser $user, string $gameId): array
    {
        return $this->loadOwnedGame($user, $gameId);
    }

    /**
     * @return array<string, mixed>
     */
    public function processPhase(AuthUser $user, string $gameId): array
    {
        $gameState = $this->loadOwnedGame($user, $gameId);
        $this->engine->setGameState($gameId, $gameState);
        $updatedState = $this->engine->processPhase($gameId);
        $this->repository->saveOwnedGame($user, $gameId, $updatedState);

        return $updatedState;
    }

    /**
     * @param array<string, mixed> $position
     * @return array<string, mixed>
     */
    public function summon(AuthUser $user, string $gameId, string $playerId, string $cardId, array $position): array
    {
        $gameState = $this->loadOwnedGame($user, $gameId);
        $this->engine->setGameState($gameId, $gameState);
        $updatedState = $this->engine->summonCreature($gameId, $playerId, $cardId, $position);
        $this->repository->saveOwnedGame($user, $gameId, $updatedState);

        return $updatedState;
    }

    /**
     * @return array<string, mixed>
     */
    public function mutate(AuthUser $user, string $gameId, string $playerId, string $creatureId, string $mutationCardId): array
    {
        $gameState = $this->loadOwnedGame($user, $gameId);
        $this->engine->setGameState($gameId, $gameState);
        $updatedState = $this->engine->applyMutation($gameId, $playerId, $creatureId, $mutationCardId);
        $this->repository->saveOwnedGame($user, $gameId, $updatedState);

        return $updatedState;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadOwnedGame(AuthUser $user, string $gameId): array
    {
        $gameState = $this->repository->findOwnedGame($user, $gameId);
        if ($gameState === null) {
            throw new RuntimeException('Game not found for authenticated player.');
        }

        return $gameState;
    }
}

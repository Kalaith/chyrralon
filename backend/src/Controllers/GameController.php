<?php

declare(strict_types=1);

namespace Chyrralon\Controllers;

use Chyrralon\Actions\GameActions;
use Chyrralon\Core\Database;
use Chyrralon\Http\Response;
use Chyrralon\Http\Request;
use Chyrralon\Models\AuthUser;
use Chyrralon\Repositories\GameRepository;
use Chyrralon\Services\GameEngine;

class GameController
{
    public static function create(Request $request, Response $response): Response
    {
        try {
            return self::success($response, self::actions()->create(self::authUser($request)));
        } catch (\Exception $e) {
            return self::json($response, ['error' => $e->getMessage()], 400);
        }
    }

    public static function get(Request $request, Response $response, array $args): Response
    {
        try {
            return self::success($response, self::actions()->get(self::authUser($request), (string) $args['gameId']));
        } catch (\Exception $e) {
            return self::json($response, ['error' => $e->getMessage()], 404);
        }
    }

    public static function processPhase(Request $request, Response $response, array $args): Response
    {
        try {
            return self::success($response, self::actions()->processPhase(self::authUser($request), (string) $args['gameId']));
        } catch (\Exception $e) {
            return self::json($response, ['error' => $e->getMessage()], 400);
        }
    }

    public static function summon(Request $request, Response $response, array $args): Response
    {
        try {
            $gameId = $args['gameId'];
            $body = $request->getParsedBody();

            if (!$body || !isset($body['playerId']) || !isset($body['cardId']) || !isset($body['position']) || !is_array($body['position'])) {
                throw new \Exception('Missing required fields: playerId, cardId, position');
            }

            $gameState = self::actions()->summon(
                self::authUser($request),
                (string) $gameId,
                (string) $body['playerId'],
                (string) $body['cardId'],
                $body['position']
            );

            return self::success($response, $gameState);
        } catch (\Exception $e) {
            return self::json($response, ['error' => $e->getMessage()], 400);
        }
    }

    public static function mutate(Request $request, Response $response, array $args): Response
    {
        try {
            $gameId = $args['gameId'];
            $body = $request->getParsedBody();

            if (!$body || !isset($body['playerId']) || !isset($body['creatureId']) || !isset($body['mutationCardId'])) {
                throw new \Exception('Missing required fields: playerId, creatureId, mutationCardId');
            }

            $gameState = self::actions()->mutate(
                self::authUser($request),
                (string) $gameId,
                (string) $body['playerId'],
                (string) $body['creatureId'],
                (string) $body['mutationCardId']
            );

            return self::success($response, $gameState);
        } catch (\Exception $e) {
            return self::json($response, ['error' => $e->getMessage()], 400);
        }
    }

    private static function actions(): GameActions
    {
        return new GameActions(
            new GameRepository(Database::getConnection()),
            GameEngine::getInstance()
        );
    }

    private static function authUser(Request $request): AuthUser
    {
        $authUser = $request->getAttribute('auth_user');
        if (!$authUser instanceof AuthUser) {
            throw new \RuntimeException('Authentication required');
        }

        return $authUser;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function success(Response $response, array $data): Response
    {
        return self::json($response, [
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function json(Response $response, array $payload, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($payload));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}

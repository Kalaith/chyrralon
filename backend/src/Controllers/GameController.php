<?php

namespace Chyrralon\Controllers;

use Chyrralon\Http\Response;
use Chyrralon\Http\Request;
use Chyrralon\Services\GameEngine;

class GameController
{
    public static function create(Request $request, Response $response): Response
    {
        try {
            $gameId = 'game_' . uniqid();
            $gameState = GameEngine::getInstance()->createGame($gameId);
            $response->getBody()->write(json_encode($gameState));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public static function get(Request $request, Response $response, array $args): Response
    {
        try {
            $gameId = $args['gameId'];
            $gameState = GameEngine::getInstance()->getGame($gameId);

            if (!$gameState) {
                $response->getBody()->write(json_encode(['error' => 'Game not found']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            $response->getBody()->write(json_encode($gameState));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public static function processPhase(Request $request, Response $response, array $args): Response
    {
        try {
            $gameId = $args['gameId'];
            $gameState = GameEngine::getInstance()->processPhase($gameId);
            $response->getBody()->write(json_encode($gameState));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public static function summon(Request $request, Response $response, array $args): Response
    {
        try {
            $gameId = $args['gameId'];
            $body = $request->getParsedBody();

            if (!$body || !isset($body['playerId']) || !isset($body['cardId']) || !isset($body['position'])) {
                throw new \Exception('Missing required fields: playerId, cardId, position');
            }

            $gameState = GameEngine::getInstance()->summonCreature(
                $gameId,
                $body['playerId'],
                $body['cardId'],
                $body['position']
            );

            $response->getBody()->write(json_encode($gameState));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
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

            $gameState = GameEngine::getInstance()->applyMutation(
                $gameId,
                $body['playerId'],
                $body['creatureId'],
                $body['mutationCardId']
            );

            $response->getBody()->write(json_encode($gameState));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }
}

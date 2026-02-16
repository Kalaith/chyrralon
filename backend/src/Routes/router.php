<?php

use Chyrralon\Core\Router;
use Chyrralon\Controllers\AuthController;
use Chyrralon\Controllers\GameController;
use Chyrralon\Controllers\CardController;
use Chyrralon\Controllers\HealthController;
use Chyrralon\Middleware\WebHatcheryJwtMiddleware;

return function (Router $router): void {
    $api = '/api';

    // Auth session
    $router->get($api . '/auth/session', [AuthController::class, 'session'], [WebHatcheryJwtMiddleware::class]);

    // Public endpoints
    $router->get($api . '/health', [HealthController::class, 'health']);
    $router->get($api . '/cards', [CardController::class, 'getCards']);

    // Game endpoints (protected)
    $router->post($api . '/game/create', [GameController::class, 'create'], [WebHatcheryJwtMiddleware::class]);
    $router->get($api . '/game/{gameId}', [GameController::class, 'get'], [WebHatcheryJwtMiddleware::class]);
    $router->post($api . '/game/{gameId}/phase', [GameController::class, 'processPhase'], [WebHatcheryJwtMiddleware::class]);
    $router->post($api . '/game/{gameId}/summon', [GameController::class, 'summon'], [WebHatcheryJwtMiddleware::class]);
    $router->post($api . '/game/{gameId}/mutate', [GameController::class, 'mutate'], [WebHatcheryJwtMiddleware::class]);
};

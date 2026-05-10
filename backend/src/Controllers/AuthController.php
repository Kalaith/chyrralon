<?php

declare(strict_types=1);

namespace Chyrralon\Controllers;

use Chyrralon\Core\Database;
use Chyrralon\Core\Environment;
use Chyrralon\Http\Response;
use Chyrralon\Http\Request;
use Chyrralon\Models\AuthUser;
use Chyrralon\Repositories\GameRepository;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthController
{
    public static function loginInfo(Request $request, Response $response): Response
    {
        return self::json($response, [
            'success' => true,
            'data' => [
                'login_url' => Environment::required('WEB_HATCHERY_LOGIN_URL'),
            ],
        ]);
    }

    public static function session(Request $request, Response $response): Response
    {
        $authUser = $request->getAttribute('auth_user');
        if (!$authUser instanceof AuthUser) {
            return self::json($response, [
                'success' => false,
                'error' => 'Authentication required',
                'message' => 'Unauthorized',
                'login_url' => Environment::required('WEB_HATCHERY_LOGIN_URL'),
            ], 401);
        }

        self::repository()->upsertPlayer($authUser);

        return self::json($response, [
            'success' => true,
            'data' => [
                'user' => $authUser->toArray(),
            ]
        ]);
    }

    public static function guestSession(Request $request, Response $response): Response
    {
        $secret = Environment::required('JWT_SECRET');
        $issuedAt = time();
        $guestUserId = 'guest_' . bin2hex(random_bytes(16));
        $guestName = 'Guest ' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        $claims = [
            'sub' => $guestUserId,
            'user_id' => $guestUserId,
            'username' => $guestName,
            'display_name' => $guestName,
            'role' => 'guest',
            'roles' => ['guest'],
            'auth_type' => 'guest',
            'is_guest' => true,
            'iat' => $issuedAt,
            'exp' => $issuedAt + (60 * 60 * 24 * 30),
        ];
        $token = JWT::encode($claims, $secret, 'HS256');
        $guestUser = AuthUser::fromArray($claims);
        self::repository()->upsertPlayer($guestUser);

        return self::json($response, [
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => $guestUser->toArray(),
            ],
        ]);
    }

    public static function linkGuest(Request $request, Response $response): Response
    {
        $authUser = $request->getAttribute('auth_user');
        if (!$authUser instanceof AuthUser) {
            return self::json($response, [
                'success' => false,
                'error' => 'Authentication required',
                'message' => 'Unauthorized',
                'login_url' => Environment::required('WEB_HATCHERY_LOGIN_URL'),
            ], 401);
        }

        if ($authUser->isGuest) {
            return self::json($response, [
                'success' => false,
                'error' => 'Linking requires a signed-in non-guest account',
            ], 400);
        }

        $body = $request->getParsedBody();
        $guestToken = $body['guest_token'] ?? null;
        if (!is_string($guestToken) || trim($guestToken) === '') {
            return self::json($response, [
                'success' => false,
                'error' => 'Invalid guest token',
            ], 422);
        }

        try {
            $decodedGuest = JWT::decode($guestToken, new Key(Environment::required('JWT_SECRET'), 'HS256'));
        } catch (\Throwable) {
            return self::json($response, [
                'success' => false,
                'error' => 'Invalid guest token',
            ], 422);
        }

        $guestClaims = (array) $decodedGuest;
        $guestUser = AuthUser::fromArray($guestClaims);
        if (!$guestUser->isGuest || !str_starts_with($guestUser->id, 'guest_')) {
            return self::json($response, [
                'success' => false,
                'error' => 'Guest token is not a guest session',
            ], 422);
        }

        $movedGames = self::repository()->moveGuestGamesToUser($guestUser->id, $authUser);
        if ($movedGames < 1) {
            return self::json($response, [
                'success' => false,
                'error' => 'Guest session has no games to link',
            ], 404);
        }

        return self::json($response, [
            'success' => true,
            'data' => [
                'linked' => true,
                'moved_games' => $movedGames,
                'user' => $authUser->toArray(),
            ],
        ]);
    }

    private static function repository(): GameRepository
    {
        return new GameRepository(Database::getConnection());
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

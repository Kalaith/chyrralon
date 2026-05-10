<?php

declare(strict_types=1);

namespace Chyrralon\Middleware;

use Chyrralon\Core\Environment;
use Chyrralon\Models\AuthUser;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Chyrralon\Http\Response;
use Chyrralon\Http\Request;

class WebHatcheryJwtMiddleware
{
    public function __invoke(Request $request, Response $response, array $routeParams = []): Response|Request|bool
    {
        $authHeader = $request->getHeaderLine('Authorization');
        $token = $authHeader && preg_match('/Bearer\s+(.+)$/i', $authHeader, $matches)
            ? trim((string) $matches[1])
            : '';

        if ($token === '') {
            return $this->unauthorized($response, 'Authorization header missing or invalid');
        }

        try {
            JWT::$leeway = 60;
            $decoded = JWT::decode($token, new Key(Environment::required('JWT_SECRET'), 'HS256'));
            $claims = (array) $decoded;

            $user = AuthUser::fromArray($claims);
            if ($user->id === '') {
                return $this->unauthorized($response, 'Token missing user identifier');
            }

            return $request->withAttribute('auth_user', $user);
        } catch (\Exception $e) {
            error_log('WebHatcheryJwtMiddleware decode failed: ' . $e->getMessage());
            return $this->unauthorized($response, 'Invalid token');
        }
    }

    private function unauthorized(Response $response, string $message): Response
    {
        $payload = [
            'success' => false,
            'error' => 'Authentication required',
            'message' => $message,
            'login_url' => Environment::required('WEB_HATCHERY_LOGIN_URL'),
        ];
        $response->getBody()->write(json_encode($payload));
        return $response
            ->withStatus(401)
            ->withHeader('Content-Type', 'application/json');
    }
}

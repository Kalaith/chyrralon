<?php

declare(strict_types=1);

namespace Chyrralon\Core;

use RuntimeException;

final class Environment
{
    public static function required(string $key): string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if (!is_string($value) || trim($value) === '') {
            throw new RuntimeException('Missing required environment variable: ' . $key);
        }

        return trim($value);
    }

    public static function optional(string $key): ?string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        return trim($value);
    }
}

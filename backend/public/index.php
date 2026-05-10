<?php

declare(strict_types=1);

$autoloadCandidates = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../../../vendor/autoload.php',
    __DIR__ . '/../../../../vendor/autoload.php',
];

$autoloadPath = null;
foreach ($autoloadCandidates as $candidate) {
    if (file_exists($candidate)) {
        $autoloadPath = $candidate;
        break;
    }
}

if ($autoloadPath === null) {
    throw new RuntimeException('Composer autoload.php not found from ' . __DIR__);
}

$loader = require $autoloadPath;
$projectSrc = realpath(__DIR__ . '/../src');
if ($projectSrc !== false && $loader instanceof \Composer\Autoload\ClassLoader) {
    $loader->addPsr4('Chyrralon\\', $projectSrc . DIRECTORY_SEPARATOR, true);
}

// Load environment variables from .env file if it exists
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim(trim($value), "\"'");
    }
}

use Chyrralon\Core\Router;
use Chyrralon\Core\Environment;

foreach ([
    'DB_HOST',
    'DB_PORT',
    'DB_NAME',
    'DB_USER',
    'DB_PASSWORD',
    'JWT_SECRET',
    'WEB_HATCHERY_LOGIN_URL',
    'CORS_ALLOWED_ORIGINS',
] as $requiredEnv) {
    Environment::required($requiredEnv);
}

$router = new Router();

// Set base path for subdirectory deployment
if (isset($_ENV['APP_BASE_PATH']) && $_ENV['APP_BASE_PATH']) {
    $router->setBasePath(rtrim($_ENV['APP_BASE_PATH'], '/'));
} else {
    $requestPath = $_SERVER['REQUEST_URI'] ?? '';
    $requestPath = parse_url($requestPath, PHP_URL_PATH) ?? '';
    $apiPos = strpos($requestPath, '/api');
    if ($apiPos !== false) {
        $basePath = substr($requestPath, 0, $apiPos);
        if ($basePath !== '') {
            $router->setBasePath($basePath);
        }
    } elseif (isset($_SERVER['SCRIPT_NAME'])) {
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $basePath = str_replace('/public/index.php', '', $scriptName);
        if ($basePath !== $scriptName && $basePath !== '') {
            $router->setBasePath($basePath);
        }
    }
}

// Load routes
(require __DIR__ . '/../src/Routes/router.php')($router);

// Run router
$router->handle();

<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$autoloadCandidates = [
    $root . '/vendor/autoload.php',
    dirname($root, 2) . '/vendor/autoload.php',
    dirname($root, 3) . '/vendor/autoload.php',
];

foreach ($autoloadCandidates as $autoload) {
    if (file_exists($autoload)) {
        require $autoload;
        break;
    }
}

spl_autoload_register(static function (string $class) use ($root): void {
    $prefix = 'Chyrralon\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = $root . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($path)) {
        require_once $path;
    }
});

$assertions = 0;

$assert = static function (bool $condition, string $message) use (&$assertions): void {
    $assertions++;
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

$routeSource = file_get_contents($root . '/src/Routes/router.php');
$middlewareSource = file_get_contents($root . '/src/Middleware/WebHatcheryJwtMiddleware.php');
$engineSource = file_get_contents($root . '/src/Services/GameEngine.php');
$authSource = file_get_contents($root . '/src/Controllers/AuthController.php');
$repositorySource = file_get_contents($root . '/src/Repositories/GameRepository.php');
$htaccessSource = file_get_contents($root . '/public/.htaccess');

$assert(is_string($routeSource), 'Route source is readable.');
$assert(str_contains($routeSource, '/auth/login-info'), 'login-info route is registered.');
$assert(!str_contains($routeSource, '/auth/callback'), 'Legacy callback route must not be registered.');
$assert(str_contains($routeSource, '[WebHatcheryJwtMiddleware::class]'), 'Game routes must use JWT middleware.');

$assert(is_string($middlewareSource), 'Middleware source is readable.');
$assert(!str_contains($middlewareSource, '31536000'), 'JWT leeway must not bypass expiration for a year.');
$assert(!str_contains($middlewareSource, 'getQueryParams'), 'JWTs must not be accepted from query params.');
$assert(str_contains($middlewareSource, "Environment::required('WEB_HATCHERY_LOGIN_URL')"), '401s must include configured login URL.');

$assert(is_string($engineSource), 'Game engine source is readable.');
$assert(!str_contains($engineSource, 'storage/games'), 'Game engine must not persist to JSON files.');
$assert(!str_contains($engineSource, 'file_put_contents'), 'Game engine must not write file saves.');

$assert(is_string($authSource), 'Auth controller source is readable.');
$assert(str_contains($authSource, 'guest_token'), 'Guest linking must validate a guest token.');
$assert(!str_contains($authSource, 'guest_user_id'), 'Guest linking must not expose or accept caller-supplied guest ids.');

$assert(is_string($repositorySource), 'Repository source is readable.');
$assert(str_contains($repositorySource, 'prepare('), 'Repository must use prepared statements.');
$assert(str_contains($repositorySource, 'owner_auth_user_id'), 'Repository must scope games by owner.');

$assert(is_string($htaccessSource), '.htaccess is readable.');
$assert(!str_contains($htaccessSource, 'Access-Control-Allow-Origin "*"'), '.htaccess must not set wildcard CORS.');
$assert(!file_exists($root . '/public/cards.php'), 'Legacy direct cards endpoint must not exist.');
$assert(!file_exists($root . '/public/test_phases.php'), 'Public test harness must not be deployed.');

$storageFiles = glob($root . '/storage/games/*.json') ?: [];
$assert($storageFiles === [], 'Legacy JSON game saves must not be tracked or used.');

$migrationPath = $root . '/database/001_create_players_and_games.sql';
$migration = file_get_contents($migrationPath);
$assert(is_string($migration), 'Database migration exists.');
$assert(str_contains($migration, 'CREATE TABLE IF NOT EXISTS chyrralon_players'), 'Players migration exists.');
$assert(str_contains($migration, 'CREATE TABLE IF NOT EXISTS chyrralon_games'), 'Games migration exists.');

$user = Chyrralon\Models\AuthUser::fromArray([
    'sub' => 'guest_test',
    'username' => 'Guest Test',
    'display_name' => 'Guest Test',
    'roles' => ['guest'],
    'auth_type' => 'guest',
    'is_guest' => true,
]);
$assert($user->id === 'guest_test', 'AuthUser maps sub to id.');
$assert($user->isGuest, 'AuthUser marks guest sessions.');
$assert($user->toArray()['role'] === 'guest', 'AuthUser serializes role.');

echo sprintf('Chyrralon backend standards tests passed (%d assertions).%s', $assertions, PHP_EOL);

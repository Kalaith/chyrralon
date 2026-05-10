<?php

declare(strict_types=1);

use Chyrralon\Core\Environment;

$root = dirname(__DIR__);
$autoloadCandidates = [
    $root . '/vendor/autoload.php',
    dirname($root, 2) . '/vendor/autoload.php',
    dirname($root, 3) . '/vendor/autoload.php',
];

$loader = null;
foreach ($autoloadCandidates as $autoload) {
    if (file_exists($autoload)) {
        $loader = require $autoload;
        break;
    }
}

if ($loader === null) {
    throw new RuntimeException('Composer autoload.php not found from ' . __DIR__);
}

$projectSrc = realpath($root . '/src');
if ($projectSrc !== false && $loader instanceof \Composer\Autoload\ClassLoader) {
    $loader->addPsr4('Chyrralon\\', $projectSrc . DIRECTORY_SEPARATOR, true);
}

$envFile = $root . '/.env';
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

$migrationFiles = glob($root . '/database/*.sql') ?: [];
sort($migrationFiles);

if ($migrationFiles === []) {
    throw new RuntimeException('No migration files found in backend/database.');
}

$databaseName = Environment::required('DB_NAME');
if (!preg_match('/^[A-Za-z0-9_]+$/', $databaseName)) {
    throw new RuntimeException('DB_NAME may only contain letters, numbers, and underscores.');
}

$serverDsn = sprintf(
    'mysql:host=%s;port=%s;charset=utf8mb4',
    Environment::required('DB_HOST'),
    Environment::required('DB_PORT')
);
$pdo = new PDO($serverDsn, Environment::required('DB_USER'), Environment::required('DB_PASSWORD'), [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

$quotedDatabaseName = '`' . str_replace('`', '``', $databaseName) . '`';
$pdo->exec('CREATE DATABASE IF NOT EXISTS ' . $quotedDatabaseName . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$pdo->exec('USE ' . $quotedDatabaseName);

foreach ($migrationFiles as $migrationFile) {
    $sql = file_get_contents($migrationFile);
    if (!is_string($sql) || trim($sql) === '') {
        continue;
    }

    $pdo->exec($sql);
    echo 'Applied ' . basename($migrationFile) . PHP_EOL;
}

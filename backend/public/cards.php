<?php

header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

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

try {
    $cards = \Chyrralon\Data\SampleCards::getAllCards();
    $cardsArray = array_map(fn($card) => $card->toArray(), $cards);
    echo json_encode(['cards' => $cardsArray]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

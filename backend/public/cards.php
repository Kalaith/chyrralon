<?php

header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require __DIR__ . '/../vendor/autoload.php';

try {
    $cards = \Chyrralon\Data\SampleCards::getAllCards();
    $cardsArray = array_map(fn($card) => $card->toArray(), $cards);
    echo json_encode(['cards' => $cardsArray]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
<?php

namespace Chyrralon\Controllers;

use Chyrralon\Http\Response;
use Chyrralon\Http\Request;
use Chyrralon\Data\SampleCards;

class CardController
{
    public static function getCards(Request $request, Response $response): Response
    {
        try {
            $cards = SampleCards::getAllCards();
            $cardsArray = array_map(fn($card) => $card->toArray(), $cards);
            $response->getBody()->write(json_encode(['cards' => $cardsArray]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}

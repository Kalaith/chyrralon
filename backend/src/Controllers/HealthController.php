<?php

namespace Chyrralon\Controllers;

use Chyrralon\Http\Response;
use Chyrralon\Http\Request;

class HealthController
{
    public static function health(Request $request, Response $response): Response
    {
        $data = ['status' => 'ok', 'message' => 'Chyrralon API is running'];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }
}

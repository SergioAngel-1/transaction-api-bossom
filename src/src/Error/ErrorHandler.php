<?php

namespace App\Error;

use Psr\Http\Message\ResponseInterface as Response;

class ErrorHandler
{
    public static function jsonResponse(Response $response, string $message, int $statusCode = 400): Response
    {
        $response->getBody()->write(json_encode([
            'status' => 'error',
            'message' => $message
        ]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }

    public static function validationErrors(Response $response, array $errors): Response
    {
        $response->getBody()->write(json_encode([
            'status' => 'error',
            'message' => 'Validation failed',
            'errors' => $errors
        ]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(422);
    }

    public static function successResponse(Response $response, $data = null, int $statusCode = 200): Response
    {
        $responseData = ['status' => 'success'];
        
        if ($data !== null) {
            $responseData['data'] = $data;
        }

        $response->getBody()->write(json_encode($responseData));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }
}

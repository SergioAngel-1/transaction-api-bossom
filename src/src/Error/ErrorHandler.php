<?php

declare(strict_types=1);

namespace App\Error;

use Psr\Http\Message\ResponseInterface as Response;

class ErrorHandler
{
    private const CONTENT_TYPE = 'application/json';
    private const DEFAULT_ERROR_CODE = 400;
    private const VALIDATION_ERROR_CODE = 422;

    public static function jsonResponse(Response $response, string $message, int $statusCode = self::DEFAULT_ERROR_CODE): Response
    {
        $response->getBody()->write(json_encode([
            'status' => 'error',
            'message' => $message
        ], JSON_THROW_ON_ERROR));

        return $response
            ->withHeader('Content-Type', self::CONTENT_TYPE)
            ->withStatus($statusCode);
    }

    public static function validationErrors(Response $response, array $errors): Response
    {
        $response->getBody()->write(json_encode([
            'status' => 'error',
            'message' => 'Validation failed',
            'errors' => $errors
        ], JSON_THROW_ON_ERROR));

        return $response
            ->withHeader('Content-Type', self::CONTENT_TYPE)
            ->withStatus(self::VALIDATION_ERROR_CODE);
    }

    public static function successResponse(Response $response, array $data = [], int $statusCode = 200): Response
    {
        $responseData = ['status' => 'success'];
        
        if (!empty($data)) {
            $responseData['data'] = $data;
        }

        $response->getBody()->write(json_encode($responseData, JSON_THROW_ON_ERROR));

        return $response
            ->withHeader('Content-Type', self::CONTENT_TYPE)
            ->withStatus($statusCode);
    }
}

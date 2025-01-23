<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Ramsey\Uuid\Uuid;
use App\Models\Transaction;
use App\Validation\TransactionValidator;
use App\Error\ErrorHandler;

// Create Transaction
$app->post('/api/transactions', function (Request $request, Response $response) {
    try {
        // Get and validate JSON data
        $data = $request->getParsedBody();
        if (!is_array($data)) {
            return ErrorHandler::validationErrors($response, ['json' => 'Invalid JSON format']);
        }
        
        error_log("Received POST request with Content-Type: " . $request->getHeaderLine('Content-Type'));
        error_log("Received data: " . json_encode($data));
        
        // Validate input
        $validator = new TransactionValidator();
        if (!$validator->validateCreate($data)) {
            error_log("Validation failed: " . json_encode($validator->getErrors()));
            return ErrorHandler::validationErrors($response, $validator->getErrors());
        }

        // Generate trace number
        $data['traceNumber'] = str_replace('-', '', Uuid::uuid4()->toString());
        
        // Create transaction
        $transactionModel = new Transaction();
        $result = $transactionModel->create($data);

        error_log("Transaction created successfully: " . json_encode($result));

        return ErrorHandler::successResponse($response, [
            'transaction' => $result,
            'message' => 'Transaction created successfully'
        ], 201);
    } catch (\PDOException $e) {
        error_log("Database error in POST /api/transactions: " . $e->getMessage());
        return ErrorHandler::jsonResponse(
            $response,
            'Database error occurred',
            500
        );
    } catch (\RuntimeException $e) {
        error_log("Runtime error in POST /api/transactions: " . $e->getMessage());
        return ErrorHandler::jsonResponse(
            $response,
            $e->getMessage(),
            400
        );
    } catch (\Exception $e) {
        error_log("Unexpected error in POST /api/transactions: " . $e->getMessage());
        return ErrorHandler::jsonResponse(
            $response,
            'An unexpected error occurred',
            500
        );
    }
});

// Get Transactions
$app->get('/api/transactions', function (Request $request, Response $response) {
    try {
        $queryParams = $request->getQueryParams();
        error_log("Received GET request with params: " . json_encode($queryParams));
        
        // Validate query parameters
        $validator = new TransactionValidator();
        if (!$validator->validateGetTransactions($queryParams)) {
            error_log("Validation failed: " . json_encode($validator->getErrors()));
            return ErrorHandler::validationErrors($response, $validator->getErrors());
        }

        // Set defaults and validate pagination parameters
        $page = isset($queryParams['page']) ? max(1, (int)$queryParams['page']) : 1;
        $limit = isset($queryParams['limit']) ? min(100, max(1, (int)$queryParams['limit'])) : 10;
        $offset = ($page - 1) * $limit;

        error_log("Processing request with page: $page, limit: $limit, offset: $offset");

        // Prepare filters
        $filters = [
            'startDate' => $queryParams['startDate'] ?? null,
            'endDate' => $queryParams['endDate'] ?? null
        ];

        // Get transactions
        $transactionModel = new Transaction();
        $result = $transactionModel->getAll($filters, $limit, $offset);
        
        error_log("Successfully retrieved transactions: " . json_encode($result));

        return ErrorHandler::successResponse($response, [
            'transactions' => $result['data'],
            'pagination' => [
                'currentPage' => $page,
                'perPage' => $limit,
                'totalItems' => $result['meta']['total'],
                'totalPages' => ceil($result['meta']['total'] / $limit)
            ]
        ]);
    } catch (\PDOException $e) {
        error_log("Database error in GET /api/transactions: " . $e->getMessage());
        return ErrorHandler::jsonResponse(
            $response,
            'Database error occurred',
            500
        );
    } catch (\RuntimeException $e) {
        error_log("Runtime error in GET /api/transactions: " . $e->getMessage());
        return ErrorHandler::jsonResponse(
            $response,
            $e->getMessage(),
            400
        );
    } catch (\Exception $e) {
        error_log("Unexpected error in GET /api/transactions: " . $e->getMessage());
        return ErrorHandler::jsonResponse(
            $response,
            'An unexpected error occurred',
            500
        );
    }
});

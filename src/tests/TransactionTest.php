<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class TransactionTest extends TestCase
{
    private $client;
    private $baseUrl = 'http://nginx:80';

    protected function setUp(): void
    {
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'http_errors' => false
        ]);
    }

    public function testGetTransactionsReturnsSuccessfulResponse()
    {
        $response = $this->client->get('/api/transactions');
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getBody(), true);
        $this->assertIsArray($data);
        $this->assertEquals('success', $data['status']);
        $this->assertArrayHasKey('data', $data);
        
        $responseData = $data['data'];
        $this->assertArrayHasKey('transactions', $responseData);
        $this->assertArrayHasKey('pagination', $responseData);
        
        $this->assertArrayHasKey('currentPage', $responseData['pagination']);
        $this->assertArrayHasKey('perPage', $responseData['pagination']);
        $this->assertArrayHasKey('totalItems', $responseData['pagination']);
        $this->assertArrayHasKey('totalPages', $responseData['pagination']);
    }

    public function testGetTransactionsWithPagination()
    {
        $response = $this->client->get('/api/transactions?page=1&limit=5');
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getBody(), true);
        $this->assertEquals('success', $data['status']);
        $this->assertArrayHasKey('data', $data);
        
        $responseData = $data['data'];
        $this->assertArrayHasKey('transactions', $responseData);
        $this->assertLessThanOrEqual(5, count($responseData['transactions']));
    }

    public function testCreateTransactionSuccessfully()
    {
        $transactionData = [
            'accountNumberFrom' => '123456789',
            'accountTypeFrom' => 'CHECKING',
            'accountNumberTo' => '987654321',
            'accountTypeTo' => 'SAVINGS',
            'amount' => 100.50,
            'memo' => 'Test transaction'
        ];

        $response = $this->client->post('/api/transactions', [
            'json' => $transactionData,
            'headers' => ['Content-Type' => 'application/json']
        ]);

        $this->assertEquals(201, $response->getStatusCode());
        
        $data = json_decode($response->getBody(), true);
        $this->assertIsArray($data);
        $this->assertEquals('success', $data['status']);
        $this->assertArrayHasKey('data', $data);
        
        $responseData = $data['data'];
        $this->assertArrayHasKey('transaction', $responseData);
        $this->assertArrayHasKey('message', $responseData);
        
        $transaction = $responseData['transaction'];
        $this->assertArrayHasKey('transactionID', $transaction);
        $this->assertEquals($transactionData['accountNumberFrom'], $transaction['accountNumberFrom']);
        $this->assertEquals($transactionData['accountTypeFrom'], $transaction['accountTypeFrom']);
        $this->assertEquals($transactionData['accountNumberTo'], $transaction['accountNumberTo']);
        $this->assertEquals($transactionData['accountTypeTo'], $transaction['accountTypeTo']);
        $this->assertEquals($transactionData['amount'], $transaction['amount']);
        $this->assertEquals($transactionData['memo'], $transaction['memo']);
    }

    public function testCreateTransactionValidationError()
    {
        $invalidData = [
            'accountNumberFrom' => '123', // Invalid: too short
            'accountTypeFrom' => 'INVALID_TYPE',
            'accountNumberTo' => '987654321',
            'accountTypeTo' => 'SAVINGS',
            'amount' => -100 // Invalid: negative amount
        ];

        $response = $this->client->post('/api/transactions', [
            'json' => $invalidData,
            'headers' => ['Content-Type' => 'application/json']
        ]);

        $this->assertEquals(422, $response->getStatusCode());
        
        $data = json_decode($response->getBody(), true);
        $this->assertEquals('error', $data['status']);
        $this->assertArrayHasKey('errors', $data);
    }

    public function testGetTransactionsWithDateFilter()
    {
        $response = $this->client->get('/api/transactions?startDate=2025-01-01&endDate=2025-12-31');
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getBody(), true);
        $this->assertEquals('success', $data['status']);
        $this->assertArrayHasKey('data', $data);
        
        $responseData = $data['data'];
        $this->assertArrayHasKey('transactions', $responseData);
        $this->assertArrayHasKey('pagination', $responseData);
    }

    public function testCreateTransactionWithInvalidJson()
    {
        $response = $this->client->post('/api/transactions', [
            'body' => 'invalid json',
            'headers' => ['Content-Type' => 'application/json']
        ]);

        $this->assertEquals(422, $response->getStatusCode());
        
        $data = json_decode($response->getBody(), true);
        $this->assertEquals('error', $data['status']);
    }
}

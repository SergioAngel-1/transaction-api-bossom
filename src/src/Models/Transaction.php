<?php

namespace App\Models;

use App\Database\DatabaseConnection;
use PDO;
use PDOException;

class Transaction
{
    private $db;

    public function __construct()
    {
        try {
            $this->db = DatabaseConnection::getInstance()->getConnection();
        } catch (\Exception $e) {
            error_log("Failed to get database connection: " . $e->getMessage());
            throw $e;
        }
    }

    public function create(array $data): array
    {
        try {
            $sql = "INSERT INTO transactions (
                account_number_from, account_type_from,
                account_number_to, account_type_to,
                trace_number, amount, memo
            ) VALUES (
                :account_number_from, :account_type_from,
                :account_number_to, :account_type_to,
                :trace_number, :amount, :memo
            ) RETURNING 
                transaction_id,
                account_number_from,
                account_type_from,
                account_number_to,
                account_type_to,
                trace_number,
                amount,
                creation_date,
                memo";

            $stmt = $this->db->prepare($sql);
            
            $params = [
                ':account_number_from' => $data['accountNumberFrom'],
                ':account_type_from' => strtoupper($data['accountTypeFrom']),
                ':account_number_to' => $data['accountNumberTo'],
                ':account_type_to' => strtoupper($data['accountTypeTo']),
                ':trace_number' => $data['traceNumber'],
                ':amount' => $data['amount'],
                ':memo' => $data['memo']
            ];

            error_log("Executing create transaction with params: " . json_encode($params));
            
            $stmt->execute($params);
            $result = $stmt->fetch();
            
            if (!$result) {
                throw new \Exception("Failed to create transaction: No data returned");
            }

            // Convert snake_case to camelCase for response
            return [
                'transactionID' => $result['transaction_id'],
                'accountNumberFrom' => $result['account_number_from'],
                'accountTypeFrom' => $result['account_type_from'],
                'accountNumberTo' => $result['account_number_to'],
                'accountTypeTo' => $result['account_type_to'],
                'traceNumber' => $result['trace_number'],
                'amount' => $result['amount'],
                'creationDate' => $result['creation_date'],
                'memo' => $result['memo']
            ];
        } catch (PDOException $e) {
            error_log("Database error in create transaction: " . $e->getMessage());
            // Check for unique constraint violation
            if ($e->getCode() == '23505') {
                throw new \Exception("Duplicate trace number detected");
            }
            // Check for check constraint violations
            if ($e->getCode() == '23514') {
                throw new \Exception("Invalid data: Check constraints failed");
            }
            throw new \Exception("Failed to create transaction: " . $e->getMessage());
        }
    }

    public function getAll(array $filters = [], int $limit = 10, int $offset = 0): array
    {
        try {
            $where = [];
            $params = [];

            if (!empty($filters['startDate'])) {
                $where[] = "creation_date >= :start_date";
                $params[':start_date'] = $filters['startDate'];
            }

            if (!empty($filters['endDate'])) {
                $where[] = "creation_date <= :end_date";
                $params[':end_date'] = $filters['endDate'];
            }

            $whereClause = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

            // Get total count
            $countSql = "SELECT COUNT(*) FROM transactions " . $whereClause;
            error_log("Executing count query: " . $countSql);
            
            $stmt = $this->db->prepare($countSql);
            $stmt->execute($params);
            $totalCount = (int)$stmt->fetchColumn();

            error_log("Total count: " . $totalCount);

            // Get paginated results
            $sql = "SELECT 
                        transaction_id,
                        account_number_from,
                        account_type_from,
                        account_number_to,
                        account_type_to,
                        trace_number,
                        amount,
                        creation_date,
                        memo
                    FROM transactions 
                    {$whereClause} 
                    ORDER BY creation_date DESC 
                    LIMIT :limit OFFSET :offset";

            error_log("Executing select query: " . $sql);
            
            $stmt = $this->db->prepare($sql);
            
            // Bind the parameters
            foreach ($params as $key => &$value) {
                $stmt->bindValue($key, $value);
            }
            
            // Bind limit and offset separately to avoid type issues
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

            $stmt->execute();
            $transactions = $stmt->fetchAll();

            // Convert snake_case to camelCase for each transaction
            $formattedTransactions = array_map(function($transaction) {
                return [
                    'transactionID' => $transaction['transaction_id'],
                    'accountNumberFrom' => $transaction['account_number_from'],
                    'accountTypeFrom' => $transaction['account_type_from'],
                    'accountNumberTo' => $transaction['account_number_to'],
                    'accountTypeTo' => $transaction['account_type_to'],
                    'traceNumber' => $transaction['trace_number'],
                    'amount' => $transaction['amount'],
                    'creationDate' => $transaction['creation_date'],
                    'memo' => $transaction['memo']
                ];
            }, $transactions);

            error_log("Found " . count($transactions) . " transactions");

            return [
                'data' => $formattedTransactions,
                'meta' => [
                    'total' => $totalCount,
                    'limit' => $limit,
                    'offset' => $offset
                ]
            ];
        } catch (PDOException $e) {
            error_log("Database error in getAll: " . $e->getMessage());
            throw new \Exception("Failed to retrieve transactions: " . $e->getMessage());
        } catch (\Exception $e) {
            error_log("General error in getAll: " . $e->getMessage());
            throw $e;
        }
    }
}

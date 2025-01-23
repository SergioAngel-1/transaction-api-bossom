<?php

namespace App\Validation;

class TransactionValidator
{
    private array $errors = [];
    private const VALID_ACCOUNT_TYPES = ['CHECKING', 'SAVINGS', 'CREDIT', 'INVESTMENT'];

    public function validateCreate(?array $data): bool
    {
        $this->errors = [];

        if ($data === null) {
            $this->errors['json'] = 'Invalid JSON data provided. Make sure to send valid JSON with Content-Type: application/json';
            return false;
        }

        // Required fields
        $requiredFields = [
            'accountNumberFrom' => 'Account number from is required',
            'accountTypeFrom' => 'Account type from is required',
            'accountNumberTo' => 'Account number to is required',
            'accountTypeTo' => 'Account type to is required',
            'amount' => 'Amount is required',
            'memo' => 'Memo is required'
        ];

        foreach ($requiredFields as $field => $message) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $this->errors[$field] = $message;
            }
        }

        // Validate account numbers (if provided)
        if (isset($data['accountNumberFrom'])) {
            if (!preg_match('/^\d{9,12}$/', $data['accountNumberFrom'])) {
                $this->errors['accountNumberFrom'] = 'Account number must be between 9 and 12 digits';
            }
        }

        if (isset($data['accountNumberTo'])) {
            if (!preg_match('/^\d{9,12}$/', $data['accountNumberTo'])) {
                $this->errors['accountNumberTo'] = 'Account number must be between 9 and 12 digits';
            }
        }

        // Validate account types (if provided)
        if (isset($data['accountTypeFrom'])) {
            if (!in_array(strtoupper($data['accountTypeFrom']), self::VALID_ACCOUNT_TYPES)) {
                $this->errors['accountTypeFrom'] = 'Invalid account type. Must be one of: ' . implode(', ', self::VALID_ACCOUNT_TYPES);
            }
        }

        if (isset($data['accountTypeTo'])) {
            if (!in_array(strtoupper($data['accountTypeTo']), self::VALID_ACCOUNT_TYPES)) {
                $this->errors['accountTypeTo'] = 'Invalid account type. Must be one of: ' . implode(', ', self::VALID_ACCOUNT_TYPES);
            }
        }

        // Validate amount (if provided)
        if (isset($data['amount'])) {
            if (!is_numeric($data['amount'])) {
                $this->errors['amount'] = 'Amount must be a number';
            } elseif ($data['amount'] <= 0) {
                $this->errors['amount'] = 'Amount must be greater than 0';
            } elseif ($data['amount'] > 999999999.99) {
                $this->errors['amount'] = 'Amount exceeds maximum limit';
            }
        }

        // Memo validation
        if (!empty($data['memo']) && strlen($data['memo']) > 255) {
            $this->errors['memo'] = 'Memo must not exceed 255 characters';
        }

        return empty($this->errors);
    }

    public function validateGetTransactions(?array $params): bool
    {
        $this->errors = [];

        if ($params === null) {
            return true; // No parameters is valid for GET request
        }

        // Validate date format if provided
        if (!empty($params['startDate'])) {
            if (!$this->isValidDate($params['startDate'])) {
                $this->errors['startDate'] = 'Invalid date format. Use YYYY-MM-DD';
            }
        }

        if (!empty($params['endDate'])) {
            if (!$this->isValidDate($params['endDate'])) {
                $this->errors['endDate'] = 'Invalid date format. Use YYYY-MM-DD';
            }
        }

        // Validate date range
        if (empty($this->errors['startDate']) && empty($this->errors['endDate'])) {
            if (isset($params['startDate']) && isset($params['endDate'])) {
                if (strtotime($params['startDate']) > strtotime($params['endDate'])) {
                    $this->errors['dateRange'] = 'Start date cannot be after end date';
                }
            }
        }

        // Validate pagination parameters if provided
        if (isset($params['page'])) {
            if (!is_numeric($params['page']) || $params['page'] < 1) {
                $this->errors['page'] = 'Page must be a positive number';
            }
        }

        if (isset($params['limit'])) {
            if (!is_numeric($params['limit']) || $params['limit'] < 1 || $params['limit'] > 100) {
                $this->errors['limit'] = 'Limit must be between 1 and 100';
            }
        }

        return empty($this->errors);
    }

    private function isValidDate(string $date): bool
    {
        $format = 'Y-m-d';
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}

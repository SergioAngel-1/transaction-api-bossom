<?php

declare(strict_types=1);

namespace App\Validation;

class TransactionValidator
{
    private array $errors = [];
    private const VALID_ACCOUNT_TYPES = ['CHECKING', 'SAVINGS', 'CREDIT', 'INVESTMENT'];
    private const MIN_ACCOUNT_LENGTH = 9;
    private const MAX_ACCOUNT_LENGTH = 12;
    private const MAX_AMOUNT = 999999999.99;
    private const MAX_MEMO_LENGTH = 255;
    private const DATE_FORMAT = 'Y-m-d';

    public function validateCreate(?array $data): bool
    {
        $this->errors = [];

        if ($data === null) {
            $this->errors['json'] = 'Invalid JSON data provided. Make sure to send valid JSON with Content-Type: application/json';
            return false;
        }

        // Required fields validation
        if (!$this->validateRequiredFields($data)) {
            return false;
        }

        // Account numbers validation
        if (isset($data['accountNumberFrom'])) {
            $this->validateAccountNumber($data['accountNumberFrom'], 'accountNumberFrom');
        }

        if (isset($data['accountNumberTo'])) {
            $this->validateAccountNumber($data['accountNumberTo'], 'accountNumberTo');
        }

        // Account types validation
        if (isset($data['accountTypeFrom'])) {
            $this->validateAccountType($data['accountTypeFrom'], 'accountTypeFrom');
        }

        if (isset($data['accountTypeTo'])) {
            $this->validateAccountType($data['accountTypeTo'], 'accountTypeTo');
        }

        // Amount validation
        if (isset($data['amount'])) {
            $this->validateAmount($data['amount']);
        }

        // Memo validation
        if (!empty($data['memo'])) {
            $this->validateMemo($data['memo']);
        }

        return empty($this->errors);
    }

    public function validateGetTransactions(?array $params): bool
    {
        $this->errors = [];

        if ($params === null) {
            return true; // No parameters is valid for GET request
        }

        // Date validations
        if (!empty($params['startDate'])) {
            $this->validateDate($params['startDate'], 'startDate');
        }

        if (!empty($params['endDate'])) {
            $this->validateDate($params['endDate'], 'endDate');
        }

        // Date range validation
        if (empty($this->errors['startDate']) && empty($this->errors['endDate'])) {
            if (isset($params['startDate'], $params['endDate'])) {
                $this->validateDateRange($params['startDate'], $params['endDate']);
            }
        }

        // Pagination validation
        if (isset($params['page'])) {
            $this->validatePaginationParam((int)$params['page'], 'page', 1);
        }

        if (isset($params['limit'])) {
            $this->validatePaginationParam((int)$params['limit'], 'limit', 1, 100);
        }

        return empty($this->errors);
    }

    private function validateRequiredFields(array $data): bool
    {
        $requiredFields = [
            'accountNumberFrom' => 'Account number from is required',
            'accountTypeFrom' => 'Account type from is required',
            'accountNumberTo' => 'Account number to is required',
            'accountTypeTo' => 'Account type to is required',
            'amount' => 'Amount is required',
            'memo' => 'Memo is required'
        ];

        $isValid = true;
        foreach ($requiredFields as $field => $message) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $this->errors[$field] = $message;
                $isValid = false;
            }
        }

        return $isValid;
    }

    private function validateAccountNumber(string $accountNumber, string $field): void
    {
        if (!preg_match('/^\d{' . self::MIN_ACCOUNT_LENGTH . ',' . self::MAX_ACCOUNT_LENGTH . '}$/', $accountNumber)) {
            $this->errors[$field] = sprintf(
                'Account number must be between %d and %d digits',
                self::MIN_ACCOUNT_LENGTH,
                self::MAX_ACCOUNT_LENGTH
            );
        }
    }

    private function validateAccountType(string $accountType, string $field): void
    {
        if (!in_array(strtoupper($accountType), self::VALID_ACCOUNT_TYPES, true)) {
            $this->errors[$field] = 'Invalid account type. Must be one of: ' . implode(', ', self::VALID_ACCOUNT_TYPES);
        }
    }

    private function validateAmount(mixed $amount): void
    {
        if (!is_numeric($amount)) {
            $this->errors['amount'] = 'Amount must be a number';
        } elseif ((float)$amount <= 0) {
            $this->errors['amount'] = 'Amount must be greater than 0';
        } elseif ((float)$amount > self::MAX_AMOUNT) {
            $this->errors['amount'] = 'Amount exceeds maximum limit';
        }
    }

    private function validateMemo(string $memo): void
    {
        if (strlen($memo) > self::MAX_MEMO_LENGTH) {
            $this->errors['memo'] = sprintf('Memo must not exceed %d characters', self::MAX_MEMO_LENGTH);
        }
    }

    private function validateDate(string $date, string $field): void
    {
        if (!$this->isValidDate($date)) {
            $this->errors[$field] = 'Invalid date format. Use YYYY-MM-DD';
        }
    }

    private function validateDateRange(string $startDate, string $endDate): void
    {
        if (strtotime($startDate) > strtotime($endDate)) {
            $this->errors['dateRange'] = 'Start date cannot be after end date';
        }
    }

    private function validatePaginationParam(int $value, string $field, int $min, ?int $max = null): void
    {
        if ($value < $min) {
            $this->errors[$field] = ucfirst($field) . ' must be greater than or equal to ' . $min;
        } elseif ($max !== null && $value > $max) {
            $this->errors[$field] = ucfirst($field) . ' must be less than or equal to ' . $max;
        }
    }

    private function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat(self::DATE_FORMAT, $date);
        return $d && $d->format(self::DATE_FORMAT) === $date;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}

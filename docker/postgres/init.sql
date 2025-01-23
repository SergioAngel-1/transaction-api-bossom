-- Drop table if exists
DROP TABLE IF EXISTS transactions;

-- Create transactions table
CREATE TABLE transactions (
    transaction_id SERIAL PRIMARY KEY,
    account_number_from VARCHAR(12) NOT NULL CHECK (account_number_from ~ '^\d{9,12}$'),
    account_type_from VARCHAR(20) NOT NULL CHECK (account_type_from IN ('CHECKING', 'SAVINGS', 'CREDIT', 'INVESTMENT')),
    account_number_to VARCHAR(12) NOT NULL CHECK (account_number_to ~ '^\d{9,12}$'),
    account_type_to VARCHAR(20) NOT NULL CHECK (account_type_to IN ('CHECKING', 'SAVINGS', 'CREDIT', 'INVESTMENT')),
    trace_number VARCHAR(32) NOT NULL UNIQUE CHECK (trace_number ~ '^[a-zA-Z0-9]+$'),
    amount DECIMAL(15,2) NOT NULL CHECK (amount > 0),
    creation_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    memo TEXT
);

-- Create index for common queries
CREATE INDEX idx_transactions_creation_date ON transactions(creation_date);
CREATE INDEX idx_transactions_trace_number ON transactions(trace_number);

-- Create index for account lookups
CREATE INDEX idx_transactions_account_from ON transactions(account_number_from, account_type_from);
CREATE INDEX idx_transactions_account_to ON transactions(account_number_to, account_type_to);

-- Insert a single example transaction
INSERT INTO transactions (
    account_number_from,
    account_type_from,
    account_number_to,
    account_type_to,
    trace_number,
    amount,
    memo
) VALUES (
    '123456789',
    'CHECKING',
    '987654321',
    'SAVINGS',
    'EXAMPLE123456789',
    1000.00,
    'Initial example transaction'
);

# PHP Transaction API

A robust RESTful API for managing financial transactions built with PHP, Slim Framework, and PostgreSQL.

## Features

### Transaction Management
- Create new transactions with validation
- Retrieve transactions with pagination
- Filter transactions by date range

### Data Validation
- Account numbers must be 9-12 digits
- Account types limited to: CHECKING, SAVINGS, CREDIT, INVESTMENT
- Unique alphanumeric trace numbers
- Positive transaction amounts
- Required fields validation

### Database
- PostgreSQL with proper constraints and indexes
- Optimized queries for performance
- Transaction trace number uniqueness
- Data integrity checks at database level

### Error Handling
- Standardized error responses
- Validation error messages
- Database error handling
- Proper HTTP status codes

## API Endpoints

### Create Transaction
```
POST /api/transactions

Request Body:
{
    "accountNumberFrom": "123456789",
    "accountTypeFrom": "CHECKING",
    "accountNumberTo": "987654321",
    "accountTypeTo": "SAVINGS",
    "amount": 100.50,
    "memo": "Payment for services"
}
```

### Get Transactions
```
GET /api/transactions?page=1&limit=10&startDate=2025-01-01&endDate=2025-12-31
```

## Setup

### Prerequisites
- Docker
- Docker Compose
- Git

### Installation Steps

1. Clone the repository
```bash
git clone [repository-url]
cd php-transaction-api
```

2. Copy environment files (if needed)
```bash
cp .env.example .env
```

3. Build and start the containers
```bash
docker-compose up -d --build
```

4. Install Composer dependencies
```bash
docker-compose exec php composer install
```

5. Verify permissions (if needed)
```bash
docker-compose exec php chmod -R 777 /var/www/html/logs
```

6. Verify the installation
```bash
# Check container status
docker-compose ps

# Check PHP logs
docker-compose logs php

# Check database connection
docker-compose exec php php -r "try { new PDO('pgsql:host=postgres;dbname=transactions_db', 'app_user', 'app_password'); echo 'Connected successfully!'; } catch(PDOException \$e) { echo \$e->getMessage(); }"
```

The API will be available at:
```
http://localhost:8080
```

### Testing the Installation

1. Test the GET endpoint
```bash
curl http://localhost:8080/api/transactions
```

2. Test the POST endpoint
```bash
curl -X POST http://localhost:8080/api/transactions \
  -H "Content-Type: application/json" \
  -d '{
    "accountNumberFrom": "123456789",
    "accountTypeFrom": "CHECKING",
    "accountNumberTo": "987654321",
    "accountTypeTo": "SAVINGS",
    "amount": 100.50,
    "memo": "Test transaction"
  }'
```

### Troubleshooting

If you encounter any issues:

1. Check container logs
```bash
docker-compose logs
```

2. Verify database connection
```bash
docker-compose exec postgres psql -U app_user -d transactions_db -c "\dt"
```

3. Check PHP configuration
```bash
docker-compose exec php php -i
```

4. Common Issues:
   - Permission errors: Run the chmod command from step 5
   - Database connection issues: Ensure PostgreSQL container is running
   - Composer errors: Try removing vendor directory and running composer install again

## Requirements
- Docker
- Docker Compose
- PHP 8.2
- PostgreSQL 15
- Composer

## Project Structure
```
.
├── docker/
│   ├── nginx/
│   ├── php/
│   └── postgres/
├── src/
│   ├── config/
│   └── src/
│       ├── Database/
│       ├── Error/
│       ├── Models/
│       └── Validation/
├── docker-compose.yml
└── README.md
```

## Database Schema

### transactions
- `transactionID`: SERIAL PRIMARY KEY
- `accountNumberFrom`: VARCHAR(12)
- `accountTypeFrom`: VARCHAR(20)
- `accountNumberTo`: VARCHAR(12)
- `accountTypeTo`: VARCHAR(20)
- `traceNumber`: VARCHAR(32) UNIQUE
- `amount`: DECIMAL(15,2)
- `creationDate`: TIMESTAMP
- `memo`: TEXT

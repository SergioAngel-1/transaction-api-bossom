# PHP Transaction API

A RESTful API for managing financial transactions, built with PHP 8.2, Slim Framework, and PostgreSQL.

## Features

- Create and query financial transactions
- Robust data validation
- Consistent error handling
- PostgreSQL database with optimized indexes
- Clear and concise API documentation
- Unit testing
- Docker containers for development

## Requirements

- Docker
- Docker Compose
- Git

## Project Structure

```
php-transaction-api/
├── docker/
│   ├── nginx/
│   │   └── default.conf
│   ├── php/
│   │   └── Dockerfile
│   └── postgres/
│       └── init.sql
├── src/
│   ├── src/
│   │   ├── Database/
│   │   ├── Error/
│   │   ├── Models/
│   │   └── Validation/
│   └── tests/
├── .gitignore
├── docker-compose.yml
└── README.md
```

## Installation

1. Clone the repository
```bash
git clone [REPOSITORY_URL]
cd php-transaction-api
```

2. Build and start containers
```bash
docker-compose up -d --build
```

3. Install Composer dependencies
```bash
docker-compose exec php composer install
```

4. Verify installation
```bash
# Check container status
docker-compose ps
```

## API Endpoints

Default server: `http://localhost:8080`

### Create Transaction

```http
POST /api/transactions
Content-Type: application/json

{
    "accountNumberFrom": "123456789",
    "accountTypeFrom": "CHECKING",
    "accountNumberTo": "987654321",
    "accountTypeTo": "SAVINGS",
    "amount": 1000.00,
    "memo": "Example transfer"
}
```

### Get Transactions

```http
GET /api/transactions
```

Optional parameters:
- `page`: Page number (default: 1)
- `limit`: Records per page (default: 10, max: 100)
- `startDate`: Start date (YYYY-MM-DD)
- `endDate`: End date (YYYY-MM-DD)

## Validations

### Account Numbers
- Must be between 9 and 12 digits
- Only numeric characters allowed

### Account Types
Allowed values:
- CHECKING
- SAVINGS
- CREDIT
- INVESTMENT

### Amount
- Must be greater than 0
- Cannot exceed 999,999,999.99

### Memo
- Maximum 255 characters

## Database

The database includes a default example transaction:
- From account: 123456789 (CHECKING)
- To account: 987654321 (SAVINGS)
- Amount: $1,000.00
- Trace number: EXAMPLE123456789
- Memo: Initial example transaction

## Testing

To run tests:
```bash
docker-compose exec php ./vendor/bin/phpunit
```

## Error Handling

The API returns consistent error responses in the following format:

```json
{
    "status": "error",
    "message": "Error message",
    "errors": {
        "field": "Error description"
    }
}
```

HTTP status codes used:
- 200: Success
- 201: Created
- 400: Bad Request
- 422: Validation Error
- 500: Internal Server Error

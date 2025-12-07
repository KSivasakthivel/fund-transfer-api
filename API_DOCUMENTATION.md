# API Documentation

## Base URL
```
http://localhost:8000/api/v1
```

## Authentication
Currently, the API does not require authentication. In production, implement JWT or OAuth2.

## Common Response Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created successfully |
| 400 | Bad request - validation failed |
| 404 | Resource not found |
| 422 | Unprocessable entity - business rule violation |
| 500 | Internal server error |

## Endpoints

### 1. Create Transfer

**Endpoint:** `POST /transfers`

**Description:** Transfer funds from one account to another.

**Request Body:**
```json
{
  "sourceAccountNumber": "string (10-20 chars, required)",
  "destinationAccountNumber": "string (10-20 chars, required)",
  "amount": "string (decimal, required)",
  "description": "string (max 500 chars, optional)"
}
```

**Example Request:**
```bash
curl -X POST http://localhost:8000/api/v1/transfers \
  -H "Content-Type: application/json" \
  -d '{
    "sourceAccountNumber": "ACC1000000001",
    "destinationAccountNumber": "ACC1000000002",
    "amount": "150.00",
    "description": "Payment for invoice #1234"
  }'
```

**Success Response (201):**
```json
{
  "success": true,
  "data": {
    "referenceNumber": "TXN20231201120000ABC123",
    "status": "completed",
    "sourceAccountNumber": "ACC1000000001",
    "destinationAccountNumber": "ACC1000000002",
    "amount": "150.00",
    "currency": "USD",
    "description": "Payment for invoice #1234",
    "createdAt": "2023-12-01 12:00:00",
    "completedAt": "2023-12-01 12:00:01"
  }
}
```

**Error Responses:**

Validation Error (400):
```json
{
  "error": "Validation failed",
  "details": {
    "amount": "Amount must be positive"
  }
}
```

Business Rule Violation (422):
```json
{
  "error": "Insufficient funds in source account"
}
```

### 2. Get Transfer by Reference Number

**Endpoint:** `GET /transfers/{referenceNumber}`

**Description:** Retrieve details of a specific transfer.

**Example Request:**
```bash
curl http://localhost:8000/api/v1/transfers/TXN20231201120000ABC123
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "referenceNumber": "TXN20231201120000ABC123",
    "status": "completed",
    "sourceAccountNumber": "ACC1000000001",
    "destinationAccountNumber": "ACC1000000002",
    "amount": "150.00",
    "currency": "USD",
    "description": "Payment for invoice #1234",
    "createdAt": "2023-12-01 12:00:00",
    "completedAt": "2023-12-01 12:00:01"
  }
}
```

**Error Response (404):**
```json
{
  "error": "Transaction not found"
}
```

### 3. Get Account Transactions

**Endpoint:** `GET /transfers/account/{accountNumber}`

**Description:** Retrieve transaction history for an account.

**Query Parameters:**
- `limit` (optional, default: 50, max: 100): Number of transactions to return

**Example Request:**
```bash
curl http://localhost:8000/api/v1/transfers/account/ACC1000000001?limit=20
```

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "referenceNumber": "TXN20231201120000ABC123",
      "status": "completed",
      "sourceAccountNumber": "ACC1000000001",
      "destinationAccountNumber": "ACC1000000002",
      "amount": "150.00",
      "currency": "USD",
      "description": "Payment for invoice #1234",
      "createdAt": "2023-12-01 12:00:00",
      "completedAt": "2023-12-01 12:00:01"
    }
  ],
  "count": 1
}
```

### 4. Get Account Details

**Endpoint:** `GET /accounts/{accountNumber}`

**Description:** Retrieve account information including current balance.

**Example Request:**
```bash
curl http://localhost:8000/api/v1/accounts/ACC1000000001
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "accountNumber": "ACC1000000001",
    "holderName": "Alice Johnson",
    "balance": "4850.00",
    "currency": "USD",
    "status": "active",
    "createdAt": "2023-12-01 10:00:00"
  }
}
```

### 5. Get Account Balance

**Endpoint:** `GET /accounts/{accountNumber}/balance`

**Description:** Retrieve only the current balance (faster, uses cache).

**Example Request:**
```bash
curl http://localhost:8000/api/v1/accounts/ACC1000000001/balance
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "accountNumber": "ACC1000000001",
    "balance": "4850.00"
  }
}
```

### 6. List Active Accounts

**Endpoint:** `GET /accounts`

**Description:** Retrieve all active accounts.

**Example Request:**
```bash
curl http://localhost:8000/api/v1/accounts
```

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "accountNumber": "ACC1000000001",
      "holderName": "Alice Johnson",
      "balance": "5000.00",
      "currency": "USD",
      "status": "active",
      "createdAt": "2023-12-01 10:00:00"
    }
  ],
  "count": 5
}
```

## Business Rules

### Transfer Constraints
- Source and destination accounts must be different
- Amount must be positive and greater than zero
- Maximum transfer amount: $1,000,000.00
- Amount must have at most 2 decimal places
- Both accounts must be active
- Both accounts must use the same currency
- Source account must have sufficient funds

### Transaction States
- `pending`: Transaction initiated but not completed
- `completed`: Transfer successful
- `failed`: Transfer failed (with failure reason)
- `reversed`: Transaction was reversed (not yet implemented)

### Account States
- `active`: Account can send and receive funds
- `suspended`: Account temporarily locked
- `closed`: Account permanently closed

## Error Handling

All errors follow this format:
```json
{
  "error": "Error message describing what went wrong"
}
```

For validation errors:
```json
{
  "error": "Validation failed",
  "details": {
    "field1": "Error message for field1",
    "field2": "Error message for field2"
  }
}
```

## Rate Limiting

Not currently implemented. In production, implement rate limiting to prevent API abuse.

Recommended limits:
- 100 requests per minute per IP
- 1000 requests per hour per IP
- Special limits for transfer endpoints (e.g., 10 transfers per minute)

## Idempotency

Not currently implemented. In production, support idempotency keys to prevent duplicate transfers:

```bash
curl -X POST http://localhost:8000/api/v1/transfers \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: unique-key-12345" \
  -d '{ ... }'
```

## Testing the API

### Using cURL

```bash
# Create a transfer
curl -X POST http://localhost:8000/api/v1/transfers \
  -H "Content-Type: application/json" \
  -d '{
    "sourceAccountNumber": "ACC1000000001",
    "destinationAccountNumber": "ACC1000000002",
    "amount": "100.00",
    "description": "Test transfer"
  }'

# Get transfer details
curl http://localhost:8000/api/v1/transfers/TXN20231201120000ABC123

# Check account balance
curl http://localhost:8000/api/v1/accounts/ACC1000000001/balance
```

### Using Postman

Import the following collection structure:

1. Create Transfer: `POST http://localhost:8000/api/v1/transfers`
2. Get Transfer: `GET http://localhost:8000/api/v1/transfers/{{referenceNumber}}`
3. Get Account: `GET http://localhost:8000/api/v1/accounts/{{accountNumber}}`
4. Get Balance: `GET http://localhost:8000/api/v1/accounts/{{accountNumber}}/balance`

## Performance Considerations

### Caching
- Account data cached in Redis for 5 minutes
- Balance queries use cache when available
- Cache automatically invalidated after transfers

### Database Optimization
- Indexed columns: account_number, status, reference_number, created_at
- Pessimistic locking prevents race conditions
- Connection pooling for concurrent requests

### Best Practices
- Use the balance endpoint for frequent balance checks (uses cache)
- Implement retry logic for transient failures
- Handle 422 errors gracefully (business rule violations)
- Log all transfer attempts for audit purposes

# Fund Transfer API

A secure, production-ready fund transfer API built with **PHP 8.2**, **Symfony 7.0**, **MySQL 8.0**, and **Redis** for high-load scenarios. This application demonstrates modern PHP development practices with comprehensive testing, transaction integrity, and proper error handling.

## 🎯 Project Overview

This API provides secure fund transfer functionality between accounts with:
- **Transaction Integrity**: ACID-compliant transfers with pessimistic locking
- **High Performance**: Redis caching layer for frequently accessed data
- **Reliability**: Automatic retry mechanism with exponential backoff
- **Observability**: Comprehensive logging using Monolog
- **Quality Assurance**: Full test coverage with unit and integration tests

## 🚀 Quick Start

### Prerequisites

- Docker & Docker Compose
- Git

### Installation

1. **Clone the repository**
   ```bash
   git clone <your-repo-url>
   cd finserve
   ```

2. **Start the Docker containers**
   ```bash
   docker-compose up -d
   ```

3. **Install dependencies**
   ```bash
   docker-compose exec php composer install
   ```

4. **Run database migrations**
   ```bash
   docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction
   ```

5. **Seed test accounts**
   ```bash
   docker-compose exec php php bin/console app:seed-accounts
   ```

6. **Verify the installation**
   ```bash
   curl http://localhost:8000/health
   ```

The API will be available at `http://localhost:8000`

## 📋 API Endpoints

### Health Check
```http
GET /health
```

**Response:**
```json
{
  "status": "ok",
  "timestamp": "2023-12-01 12:00:00"
}
```

### Create Transfer
```http
POST /api/v1/transfers
Content-Type: application/json

{
  "sourceAccountNumber": "ACC1000000001",
  "destinationAccountNumber": "ACC1000000002",
  "amount": "250.00",
  "description": "Payment for services"
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "data": {
    "referenceNumber": "TXN20231201120000ABC123",
    "status": "completed",
    "sourceAccountNumber": "ACC1000000001",
    "destinationAccountNumber": "ACC1000000002",
    "amount": "250.00",
    "currency": "USD",
    "description": "Payment for services",
    "createdAt": "2023-12-01 12:00:00",
    "completedAt": "2023-12-01 12:00:01"
  }
}
```

**Error Response (422 Unprocessable Entity):**
```json
{
  "error": "Insufficient funds in source account"
}
```

### Get Transfer Details
```http
GET /api/v1/transfers/{referenceNumber}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "referenceNumber": "TXN20231201120000ABC123",
    "status": "completed",
    "sourceAccountNumber": "ACC1000000001",
    "destinationAccountNumber": "ACC1000000002",
    "amount": "250.00",
    "currency": "USD",
    "description": "Payment for services",
    "createdAt": "2023-12-01 12:00:00",
    "completedAt": "2023-12-01 12:00:01"
  }
}
```

### Get Account Transactions
```http
GET /api/v1/transfers/account/{accountNumber}?limit=50
```

**Response:**
```json
{
  "success": true,
  "data": [...],
  "count": 10
}
```

### Get Account Details
```http
GET /api/v1/accounts/{accountNumber}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "accountNumber": "ACC1000000001",
    "holderName": "Alice Johnson",
    "balance": "4750.00",
    "currency": "USD",
    "status": "active",
    "createdAt": "2023-12-01 10:00:00"
  }
}
```

### Get Account Balance
```http
GET /api/v1/accounts/{accountNumber}/balance
```

**Response:**
```json
{
  "success": true,
  "data": {
    "accountNumber": "ACC1000000001",
    "balance": "4750.00"
  }
}
```

### List All Active Accounts
```http
GET /api/v1/accounts
```

**Response:**
```json
{
  "success": true,
  "data": [...],
  "count": 5
}
```

## 🧪 Running Tests

### Run all tests
```bash
docker-compose exec php php bin/phpunit
```

### Run specific test suites
```bash
# Unit tests only
docker-compose exec php php bin/phpunit tests/Entity
docker-compose exec php php bin/phpunit tests/Service

# Integration tests
docker-compose exec php php bin/phpunit tests/Integration
```

### Test Coverage
The test suite includes:
- **Unit Tests**: Entity business logic, service validation
- **Integration Tests**: End-to-end API testing with database transactions
- **Edge Cases**: Insufficient funds, invalid accounts, concurrent transfers

## 🏗️ Architecture & Design Decisions

### Domain-Driven Design
- **Entities**: Rich domain models with business logic (`Account`, `Transaction`)
- **Repositories**: Data access abstraction
- **Services**: Application business logic (`FundTransferService`, `CacheService`)
- **DTOs**: Request/Response data transfer objects

### Transaction Integrity
- **Pessimistic Locking**: Prevents race conditions during concurrent transfers
- **ACID Compliance**: Database transactions ensure consistency
- **Retry Mechanism**: Automatic retry with exponential backoff on lock timeouts
- **Optimistic Locking**: Version field for detecting concurrent modifications

### High Load Handling
- **Redis Caching**: Caches account data and balances (5-minute TTL)
- **Database Indexing**: Optimized queries with indexes on frequently accessed columns
- **Connection Pooling**: Docker services configured for concurrent connections
- **Graceful Degradation**: Falls back to database if cache fails

### Error Handling
- **Domain Exceptions**: Business rule violations (insufficient funds, inactive accounts)
- **Runtime Exceptions**: System errors with proper logging
- **Validation**: Request validation using Symfony Validator
- **Comprehensive Logging**: All operations logged with context for debugging

### Security Considerations
- **Input Validation**: All requests validated with constraints
- **SQL Injection Protection**: Doctrine ORM with parameterized queries
- **Amount Precision**: BCMath for accurate decimal arithmetic
- **Transfer Limits**: Maximum transfer amount validation
- **Account Status**: Only active accounts can perform transfers

## 📊 Database Schema

### Accounts Table
- `id`: Primary key
- `account_number`: Unique account identifier (indexed)
- `holder_name`: Account holder name
- `balance`: Current balance (DECIMAL 15,2)
- `currency`: ISO currency code
- `status`: Account status (active/suspended/closed, indexed)
- `version`: Optimistic locking version
- `created_at`, `updated_at`: Timestamps

### Transactions Table
- `id`: Primary key
- `reference_number`: Unique transaction reference (indexed)
- `source_account_id`: Foreign key to accounts
- `destination_account_id`: Foreign key to accounts
- `amount`: Transfer amount (DECIMAL 15,2)
- `currency`: ISO currency code
- `status`: Transaction status (pending/completed/failed/reversed, indexed)
- `description`: Optional description
- `failure_reason`: Error details if failed
- `metadata`: JSON field for additional data
- `created_at`, `completed_at`: Timestamps (indexed)

## 🔧 Technology Stack

- **PHP 8.2**: Modern PHP with strong typing and attributes
- **Symfony 7.0**: Latest Symfony framework with best practices
- **Doctrine ORM 2.17**: Database abstraction and migrations
- **MySQL 8.0**: Reliable RDBMS with ACID guarantees
- **Redis 7**: In-memory caching for performance
- **PHPUnit 10.5**: Comprehensive testing framework
- **Monolog**: Structured logging
- **Docker**: Containerized development environment

## 📝 Development Process & Best Practices

### Code Organization
- PSR-4 autoloading
- Strict type declarations
- Constructor property promotion (PHP 8)
- Attribute-based routing and ORM mapping
- Dependency injection throughout

### Testing Strategy
- Test-driven development principles
- Mocked dependencies in unit tests
- Real database transactions in integration tests
- Clean database state for each test

### Performance Optimization
- Query optimization with proper indexes
- N+1 query prevention
- Strategic caching with invalidation
- Efficient database transactions

### Logging & Monitoring
- Request/response logging
- Error tracking with stack traces
- Business event logging (transfers completed/failed)
- Performance metrics (Redis cache hit/miss)

## 🚀 Production Considerations

### Before Deploying to Production

1. **Environment Variables**
   - Change `APP_SECRET` to a secure random value
   - Update database credentials
   - Configure Redis connection string

2. **Security**
   - Enable HTTPS
   - Add API authentication (JWT, OAuth2)
   - Implement rate limiting
   - Add CORS configuration if needed

3. **Performance**
   - Enable OPcache
   - Configure Redis persistence
   - Set up database replication
   - Implement queue system for async processing

4. **Monitoring**
   - Set up application monitoring (New Relic, Datadog)
   - Configure log aggregation (ELK stack)
   - Add health check endpoints for load balancer
   - Set up alerts for errors and performance issues

5. **Scaling**
   - Horizontal scaling with load balancer
   - Database read replicas
   - Redis cluster for high availability
   - Consider event sourcing for audit trail

## 🛠️ Additional Features to Implement

Plan to add in future:

- **API Authentication**: JWT or OAuth2 for secure access
- **Rate Limiting**: Prevent API abuse
- **Webhooks**: Notify external systems of transfer events
- **Transaction Reversal**: Ability to reverse completed transfers
- **Multi-Currency Support**: Real-time exchange rates
- **Bulk Transfers**: Process multiple transfers in one request
- **Scheduled Transfers**: Future-dated transactions
- **Transaction Fees**: Configurable fee structure
- **Account Types**: Different account types with varying rules
- **KYC Integration**: Identity verification
- **Fraud Detection**: Pattern analysis and risk scoring
- **Audit Trail**: Complete history of all account changes
- **API Versioning**: Support multiple API versions
- **GraphQL API**: Alternative to REST
- **Idempotency Keys**: Prevent duplicate transactions

### Key Prompts Used:
1. "Create a secure fund transfer API with Symfony, MySQL, and Redis with transaction integrity"
2. "Implement pessimistic locking for concurrent transfer protection"
3. "Add comprehensive error handling and logging for production readiness"
4. "Write integration tests for fund transfer API endpoints"
5. "Create detailed README with API documentation and setup instructions"

## 📄 License

MIT License

## 👤 Author

Sivasakthivel Kandavel(Technical Lead)

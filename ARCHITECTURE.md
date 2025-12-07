# ARCHITECTURE.md

## System Architecture

### Overview
This application follows a layered architecture pattern with clear separation of concerns:

```
┌─────────────────────────────────────────┐
│          Presentation Layer             │
│  (Controllers, Request/Response DTOs)   │
└─────────────────┬───────────────────────┘
                  │
┌─────────────────▼───────────────────────┐
│         Application Layer               │
│    (Services, Business Logic)           │
└─────────────────┬───────────────────────┘
                  │
┌─────────────────▼───────────────────────┐
│           Domain Layer                  │
│     (Entities, Value Objects)           │
└─────────────────┬───────────────────────┘
                  │
┌─────────────────▼───────────────────────┐
│       Infrastructure Layer              │
│  (Repositories, Database, Cache)        │
└─────────────────────────────────────────┘
```

## Components

### 1. Controllers (`src/Controller/`)
Responsible for:
- HTTP request handling
- Input validation
- Response formatting
- Error handling

**Key Controllers:**
- `TransferController`: Manages fund transfer operations
- `AccountController`: Manages account queries
- `HealthController`: Health check endpoint

### 2. Services (`src/Service/`)
Contain business logic and orchestrate operations:

**FundTransferService:**
- Coordinates fund transfers between accounts
- Manages database transactions
- Implements retry logic with exponential backoff
- Handles pessimistic locking
- Validates business rules
- Logs all operations

**CacheService:**
- Manages Redis cache operations
- Implements cache-aside pattern
- Handles cache invalidation
- Graceful degradation on cache failures

### 3. Entities (`src/Entity/`)
Domain models with business logic:

**Account:**
- Encapsulates account data and behavior
- Methods: `debit()`, `credit()`, `isActive()`
- Business rules: Balance validation, status checks
- Optimistic locking with version field

**Transaction:**
- Represents a fund transfer
- Lifecycle methods: `markAsCompleted()`, `markAsFailed()`
- Immutable reference number generation

### 4. Repositories (`src/Repository/`)
Data access layer:
- Query abstractions
- Custom query methods
- No business logic

### 5. DTOs (`src/DTO/`)
Data transfer objects for API:
- Request validation
- Response serialization
- Decoupling from domain models

## Design Patterns

### 1. Repository Pattern
```php
interface AccountRepository {
    public function findByAccountNumber(string $accountNumber): ?Account;
    public function save(Account $account, bool $flush = false): void;
}
```

Benefits:
- Abstracts data access
- Testable with mocks
- Centralized query logic

### 2. Service Layer Pattern
```php
class FundTransferService {
    public function transfer(
        string $source,
        string $destination,
        string $amount
    ): Transaction;
}
```

Benefits:
- Encapsulates business logic
- Reusable across controllers
- Transaction management

### 3. DTO Pattern
```php
class TransferRequest {
    public string $sourceAccountNumber;
    public string $destinationAccountNumber;
    public string $amount;
}
```

Benefits:
- Input validation
- API versioning support
- Decoupling

### 4. Cache-Aside Pattern
```php
// Try cache first
$account = $cache->get($accountNumber);
if (!$account) {
    // Cache miss: load from database
    $account = $repository->find($accountNumber);
    $cache->set($accountNumber, $account);
}
```

Benefits:
- Reduces database load
- Lazy loading
- Flexible cache strategy

## Transaction Integrity

### Pessimistic Locking
```php
$account = $entityManager->createQueryBuilder()
    ->select('a')
    ->from(Account::class, 'a')
    ->where('a.accountNumber = :number')
    ->setParameter('number', $accountNumber)
    ->getQuery()
    ->setLockMode(LockMode::PESSIMISTIC_WRITE)
    ->getOneOrNullResult();
```

**Why:** Prevents concurrent modifications during transfers

**Flow:**
1. Begin transaction
2. Lock source account (blocks other transactions)
3. Lock destination account
4. Validate and execute transfer
5. Commit or rollback

### Optimistic Locking
```php
#[ORM\Version]
#[ORM\Column(type: Types::INTEGER)]
private int $version = 0;
```

**Why:** Detects concurrent modifications

**Flow:**
1. Load entity with version
2. Modify entity
3. Save with version check
4. If version changed: throw exception

### Retry Mechanism
```php
$retryCount = 0;
while ($retryCount < MAX_RETRY_ATTEMPTS) {
    try {
        // Attempt transfer
        return $transaction;
    } catch (LockWaitTimeoutException $e) {
        $retryCount++;
        usleep(100000 * pow(2, $retryCount - 1)); // Exponential backoff
    }
}
```

**Why:** Handles temporary lock contention

## Caching Strategy

### Cache Layers
1. **Application Cache (Redis)**
   - Account data: 5-minute TTL
   - Balance data: 5-minute TTL
   
2. **ORM Cache (Doctrine)**
   - Query result cache
   - Metadata cache

### Cache Invalidation
```php
// After transfer
$cacheService->invalidateAccountCache($sourceAccountNumber);
$cacheService->invalidateAccountCache($destinationAccountNumber);
```

**Strategy:** Write-through with immediate invalidation

### Cache Keys
```
account:{accountNumber}
account:balance:{accountNumber}
```

## Error Handling Strategy

### Error Hierarchy
```
Exception
├── DomainException (Business rule violations)
│   ├── Insufficient funds
│   ├── Invalid account status
│   └── Currency mismatch
│
└── RuntimeException (System errors)
    ├── Lock timeout
    ├── Database connection failed
    └── Cache unavailable
```

### HTTP Status Mapping
- `400`: Validation errors (malformed request)
- `404`: Resource not found
- `422`: Business rule violation
- `500`: System error

### Logging Levels
- `INFO`: Successful operations
- `WARNING`: Business rule violations
- `ERROR`: System errors with stack traces

## Database Schema Design

### Indexing Strategy
```sql
-- Frequently queried fields
INDEX idx_account_number ON accounts(account_number)
INDEX idx_status ON accounts(status)

-- Transaction lookups
INDEX idx_reference_number ON transactions(reference_number)
INDEX idx_status ON transactions(status)
INDEX idx_created_at ON transactions(created_at)
```

### Foreign Key Constraints
```sql
FOREIGN KEY (source_account_id) REFERENCES accounts(id)
FOREIGN KEY (destination_account_id) REFERENCES accounts(id)
```

**Why:** Referential integrity, cascade options

### Precision for Money
```sql
DECIMAL(15, 2) -- Up to 999,999,999,999.99
```

**Why:** Exact decimal arithmetic (no floating-point errors)

## Security Considerations

### Current Implementation
1. **Input Validation**: All requests validated
2. **SQL Injection Protection**: Parameterized queries
3. **Business Rules**: Transfer limits, status checks
4. **Decimal Precision**: BCMath for accurate calculations

### Production Additions Needed
1. **Authentication**: JWT or OAuth2
2. **Authorization**: Role-based access control
3. **Rate Limiting**: Prevent abuse
4. **Audit Logging**: Complete audit trail
5. **Encryption**: Sensitive data encryption
6. **HTTPS**: TLS/SSL certificates
7. **API Keys**: Client identification
8. **IP Whitelisting**: Restrict access

## Scalability Considerations

### Horizontal Scaling
- **Stateless API**: Can run multiple instances
- **Load Balancer**: Distribute requests
- **Session Management**: Redis for shared sessions

### Database Scaling
- **Read Replicas**: Distribute read load
- **Sharding**: Partition by account number range
- **Connection Pooling**: Efficient connection reuse

### Cache Scaling
- **Redis Cluster**: High availability
- **Cache Partitioning**: Distribute cache load
- **CDN**: Static asset delivery

### Performance Optimization
1. **Query Optimization**: Proper indexes
2. **N+1 Prevention**: Eager loading
3. **Batch Operations**: Bulk inserts
4. **Async Processing**: Queue system for heavy operations
5. **Database Connection Pooling**: Reuse connections

## Testing Strategy

### Unit Tests
- **Entities**: Business logic in domain models
- **Services**: Service layer logic with mocked dependencies
- **Isolation**: No database or external dependencies

### Integration Tests
- **End-to-End**: Full request/response cycle
- **Real Database**: Test with actual database
- **Clean State**: Fresh database for each test
- **Edge Cases**: Test failure scenarios

### Test Coverage Goals
- **Entities**: 100% (critical business logic)
- **Services**: 90%+ (core application logic)
- **Controllers**: 80%+ (request handling)
- **Overall**: 85%+ coverage

## Monitoring & Observability

### Metrics to Track
1. **Business Metrics**
   - Transfer volume
   - Success/failure rates
   - Average transfer amount
   - Peak transfer times

2. **Technical Metrics**
   - Response times
   - Error rates
   - Cache hit/miss ratio
   - Database connection pool usage
   - Lock wait times

3. **Infrastructure Metrics**
   - CPU usage
   - Memory usage
   - Disk I/O
   - Network traffic

### Logging Strategy
- **Structured Logging**: JSON format
- **Correlation IDs**: Track request across services
- **Context**: Include relevant metadata
- **Log Levels**: Appropriate severity

## Future Improvements

### Phase 1: Production Readiness
1. API authentication (JWT)
2. Rate limiting
3. Comprehensive monitoring
4. API documentation (OpenAPI/Swagger)

### Phase 2: Enhanced Features
1. Multi-currency support
2. Transaction reversal
3. Scheduled transfers
4. Webhooks

### Phase 3: Advanced Capabilities
1. Event sourcing
2. CQRS pattern
3. Microservices architecture
4. Real-time notifications

### Phase 4: AI/ML Integration
1. Fraud detection
2. Risk scoring
3. Predictive analytics
4. Anomaly detection

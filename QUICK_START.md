# Quick Reference Guide

## 🚀 Getting Started in 60 Seconds

```bash
# Windows (PowerShell)
.\setup.ps1

# Linux/Mac
chmod +x setup.sh && ./setup.sh
```

That's it! API will be running at `http://localhost:8000`

## 📍 Key Endpoints

```bash
# Health Check
curl http://localhost:8000/health

# Create Transfer
curl -X POST http://localhost:8000/api/v1/transfers \
  -H "Content-Type: application/json" \
  -d '{"sourceAccountNumber":"ACC1000000001","destinationAccountNumber":"ACC1000000002","amount":"100.00"}'

# Check Balance
curl http://localhost:8000/api/v1/accounts/ACC1000000001/balance
```

## 🧪 Test Accounts

| Account Number  | Holder Name    | Balance    |
|----------------|----------------|------------|
| ACC1000000001  | Alice Johnson  | $5,000.00  |
| ACC1000000002  | Bob Smith      | $3,000.00  |
| ACC1000000003  | Charlie Brown  | $10,000.00 |
| ACC1000000004  | Diana Prince   | $2,500.00  |
| ACC1000000005  | Eve Anderson   | $7,500.00  |

## ⚡ Common Commands

```bash
# Run tests
docker-compose exec php php bin/phpunit

# View logs
docker-compose logs -f php

# Access PHP container
docker-compose exec php bash

# Stop containers
docker-compose stop

# Restart
docker-compose restart

# Clean up
docker-compose down -v
```

## 🔍 Project Structure Quick View

```
src/
├── Controller/     # API endpoints
├── Service/        # Business logic
├── Entity/         # Domain models
├── Repository/     # Data access
└── DTO/           # Request/Response objects

tests/
├── Entity/        # Unit tests
├── Service/       # Unit tests
└── Integration/   # API tests
```

## 📖 Documentation Files

- `README.md` - Main documentation and setup guide
- `API_DOCUMENTATION.md` - Complete API reference
- `ARCHITECTURE.md` - System design and patterns
- `PROJECT_SUMMARY.md` - Feature overview
- `CHECKLIST.md` - Submission checklist

## 🐛 Troubleshooting

### Port Already in Use
```bash
# Change ports in docker-compose.yml
ports:
  - "8001:8000"  # Instead of 8000:8000
```

### Database Connection Failed
```bash
# Wait for database to be ready
docker-compose exec php php bin/console doctrine:database:create
```

### Tests Failing
```bash
# Run migrations for test database
docker-compose exec php php bin/console doctrine:migrations:migrate --env=test
```

## 💡 Tips

1. **Use the setup script** - It handles everything automatically
2. **Check health endpoint** - Verify API is running
3. **Review logs** - Use `docker-compose logs -f` for debugging
4. **Run tests first** - Ensure everything works before making changes
5. **Use sample requests** - See `requests.http` for examples

## 📚 Learn More

- Symfony Documentation: https://symfony.com/doc/current/index.html
- Doctrine ORM: https://www.doctrine-project.org/projects/doctrine-orm/en/current/
- Redis: https://redis.io/documentation

## 🤝 Need Help?

1. Check the documentation files
2. Review the test files for usage examples
3. Check logs: `docker-compose logs -f`
4. Verify Docker is running: `docker ps`

---

**Quick Validation**:
```bash
# Everything working? Run this:
curl http://localhost:8000/health && \
docker-compose exec php php bin/phpunit && \
echo "✅ All systems operational!"
```

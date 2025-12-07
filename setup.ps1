#!/usr/bin/env pwsh
# PowerShell setup script for Fund Transfer API

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Fund Transfer API - Setup Script" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Check if Docker is installed
Write-Host "Checking Docker installation..." -ForegroundColor Yellow
if (!(Get-Command docker -ErrorAction SilentlyContinue)) {
    Write-Host "❌ Docker is not installed. Please install Docker Desktop first." -ForegroundColor Red
    Write-Host "Download from: https://www.docker.com/products/docker-desktop" -ForegroundColor Yellow
    exit 1
}
Write-Host "✅ Docker is installed" -ForegroundColor Green

# Check if Docker Compose is installed
Write-Host "Checking Docker Compose installation..." -ForegroundColor Yellow
if (!(Get-Command docker-compose -ErrorAction SilentlyContinue)) {
    Write-Host "❌ Docker Compose is not installed." -ForegroundColor Red
    exit 1
}
Write-Host "✅ Docker Compose is installed" -ForegroundColor Green
Write-Host ""

# Start Docker containers
Write-Host "Starting Docker containers..." -ForegroundColor Yellow
docker-compose up -d
if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Failed to start Docker containers" -ForegroundColor Red
    exit 1
}
Write-Host "✅ Docker containers started" -ForegroundColor Green
Write-Host ""

# Wait for containers to be ready
Write-Host "Waiting for containers to be ready..." -ForegroundColor Yellow
Start-Sleep -Seconds 10

# Install Composer dependencies
Write-Host "Installing Composer dependencies..." -ForegroundColor Yellow
docker-compose exec -T php composer install --no-interaction --optimize-autoloader
if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Failed to install Composer dependencies" -ForegroundColor Red
    exit 1
}
Write-Host "✅ Dependencies installed" -ForegroundColor Green
Write-Host ""

# Wait for database to be ready
Write-Host "Waiting for database to be ready..." -ForegroundColor Yellow
Start-Sleep -Seconds 5

# Run database migrations
Write-Host "Running database migrations..." -ForegroundColor Yellow
docker-compose exec -T php php bin/console doctrine:migrations:migrate --no-interaction
if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Failed to run migrations" -ForegroundColor Red
    exit 1
}
Write-Host "✅ Migrations completed" -ForegroundColor Green
Write-Host ""

# Seed test accounts
Write-Host "Seeding test accounts..." -ForegroundColor Yellow
docker-compose exec -T php php bin/console app:seed-accounts
if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Failed to seed accounts" -ForegroundColor Red
    exit 1
}
Write-Host "✅ Test accounts seeded" -ForegroundColor Green
Write-Host ""

# Test the API
Write-Host "Testing API health check..." -ForegroundColor Yellow
Start-Sleep -Seconds 2
try {
    $response = Invoke-WebRequest -Uri "http://localhost:8000/health" -Method GET -ErrorAction Stop
    if ($response.StatusCode -eq 200) {
        Write-Host "✅ API is responding correctly" -ForegroundColor Green
    }
} catch {
    Write-Host "⚠️  API health check failed, but setup is complete" -ForegroundColor Yellow
}
Write-Host ""

# Display success message
Write-Host "========================================" -ForegroundColor Green
Write-Host "  ✅ Setup Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "API is available at: http://localhost:8000" -ForegroundColor Cyan
Write-Host "Health check: http://localhost:8000/health" -ForegroundColor Cyan
Write-Host ""
Write-Host "Test Accounts Created:" -ForegroundColor Yellow
Write-Host "  • ACC1000000001 - Alice Johnson   - Balance: $5,000.00" -ForegroundColor White
Write-Host "  • ACC1000000002 - Bob Smith       - Balance: $3,000.00" -ForegroundColor White
Write-Host "  • ACC1000000003 - Charlie Brown   - Balance: $10,000.00" -ForegroundColor White
Write-Host "  • ACC1000000004 - Diana Prince    - Balance: $2,500.00" -ForegroundColor White
Write-Host "  • ACC1000000005 - Eve Anderson    - Balance: $7,500.00" -ForegroundColor White
Write-Host ""
Write-Host "Quick Start Commands:" -ForegroundColor Yellow
Write-Host "  • Run tests:          docker-compose exec php php bin/phpunit" -ForegroundColor White
Write-Host "  • View logs:          docker-compose logs -f" -ForegroundColor White
Write-Host "  • Stop containers:    docker-compose stop" -ForegroundColor White
Write-Host "  • Restart:            docker-compose restart" -ForegroundColor White
Write-Host ""
Write-Host "Try creating a transfer:" -ForegroundColor Yellow
Write-Host '  Invoke-WebRequest -Uri "http://localhost:8000/api/v1/transfers" `' -ForegroundColor Cyan
Write-Host '    -Method POST `' -ForegroundColor Cyan
Write-Host '    -Headers @{"Content-Type"="application/json"} `' -ForegroundColor Cyan
Write-Host '    -Body ''{"sourceAccountNumber":"ACC1000000001","destinationAccountNumber":"ACC1000000002","amount":"100.00","description":"Test transfer"}''' -ForegroundColor Cyan
Write-Host ""

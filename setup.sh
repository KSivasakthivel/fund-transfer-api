#!/bin/bash
# Bash setup script for Fund Transfer API (Linux/Mac)

echo "========================================"
echo "  Fund Transfer API - Setup Script"
echo "========================================"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Check if Docker is installed
echo -e "${YELLOW}Checking Docker installation...${NC}"
if ! command -v docker &> /dev/null; then
    echo -e "${RED}❌ Docker is not installed. Please install Docker first.${NC}"
    echo -e "${YELLOW}Download from: https://www.docker.com/products/docker-desktop${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Docker is installed${NC}"

# Check if Docker Compose is installed
echo -e "${YELLOW}Checking Docker Compose installation...${NC}"
if ! command -v docker-compose &> /dev/null; then
    echo -e "${RED}❌ Docker Compose is not installed.${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Docker Compose is installed${NC}"
echo ""

# Start Docker containers
echo -e "${YELLOW}Starting Docker containers...${NC}"
docker-compose up -d
if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Failed to start Docker containers${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Docker containers started${NC}"
echo ""

# Wait for containers to be ready
echo -e "${YELLOW}Waiting for containers to be ready...${NC}"
sleep 10

# Install Composer dependencies
echo -e "${YELLOW}Installing Composer dependencies...${NC}"
docker-compose exec -T php composer install --no-interaction --optimize-autoloader
if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Failed to install Composer dependencies${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Dependencies installed${NC}"
echo ""

# Wait for database to be ready
echo -e "${YELLOW}Waiting for database to be ready...${NC}"
sleep 5

# Run database migrations
echo -e "${YELLOW}Running database migrations...${NC}"
docker-compose exec -T php php bin/console doctrine:migrations:migrate --no-interaction
if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Failed to run migrations${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Migrations completed${NC}"
echo ""

# Seed test accounts
echo -e "${YELLOW}Seeding test accounts...${NC}"
docker-compose exec -T php php bin/console app:seed-accounts
if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Failed to seed accounts${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Test accounts seeded${NC}"
echo ""

# Test the API
echo -e "${YELLOW}Testing API health check...${NC}"
sleep 2
if curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/health | grep -q "200"; then
    echo -e "${GREEN}✅ API is responding correctly${NC}"
else
    echo -e "${YELLOW}⚠️  API health check failed, but setup is complete${NC}"
fi
echo ""

# Display success message
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  ✅ Setup Complete!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "${CYAN}API is available at: http://localhost:8000${NC}"
echo -e "${CYAN}Health check: http://localhost:8000/health${NC}"
echo ""
echo -e "${YELLOW}Test Accounts Created:${NC}"
echo "  • ACC1000000001 - Alice Johnson   - Balance: \$5,000.00"
echo "  • ACC1000000002 - Bob Smith       - Balance: \$3,000.00"
echo "  • ACC1000000003 - Charlie Brown   - Balance: \$10,000.00"
echo "  • ACC1000000004 - Diana Prince    - Balance: \$2,500.00"
echo "  • ACC1000000005 - Eve Anderson    - Balance: \$7,500.00"
echo ""
echo -e "${YELLOW}Quick Start Commands:${NC}"
echo "  • Run tests:          docker-compose exec php php bin/phpunit"
echo "  • View logs:          docker-compose logs -f"
echo "  • Stop containers:    docker-compose stop"
echo "  • Restart:            docker-compose restart"
echo ""
echo -e "${YELLOW}Try creating a transfer:${NC}"
echo -e "${CYAN}curl -X POST http://localhost:8000/api/v1/transfers \\${NC}"
echo -e "${CYAN}  -H \"Content-Type: application/json\" \\${NC}"
echo -e "${CYAN}  -d '{\"sourceAccountNumber\":\"ACC1000000001\",\"destinationAccountNumber\":\"ACC1000000002\",\"amount\":\"100.00\",\"description\":\"Test transfer\"}'${NC}"
echo ""

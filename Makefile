.PHONY: help install start stop restart logs shell test migrate seed clean

help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Available targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-15s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

install: ## Install dependencies and setup project
	docker-compose up -d
	docker-compose exec php composer install
	@echo "Waiting for database to be ready..."
	@sleep 5
	docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction
	docker-compose exec php php bin/console app:seed-accounts
	@echo ""
	@echo "✅ Installation complete!"
	@echo "API is available at: http://localhost:8000"
	@echo "Health check: http://localhost:8000/health"

start: ## Start all containers
	docker-compose up -d
	@echo "Containers started. API available at http://localhost:8000"

stop: ## Stop all containers
	docker-compose stop

restart: ## Restart all containers
	docker-compose restart

logs: ## Show container logs
	docker-compose logs -f

shell: ## Open PHP container shell
	docker-compose exec php bash

test: ## Run all tests
	docker-compose exec php php bin/phpunit

test-unit: ## Run unit tests only
	docker-compose exec php php bin/phpunit tests/Entity tests/Service

test-integration: ## Run integration tests only
	docker-compose exec php php bin/phpunit tests/Integration

migrate: ## Run database migrations
	docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction

seed: ## Seed test accounts
	docker-compose exec php php bin/console app:seed-accounts

clean: ## Clean up containers and volumes
	docker-compose down -v
	@echo "Cleaned up all containers and volumes"

cache-clear: ## Clear application cache
	docker-compose exec php php bin/console cache:clear

db-create: ## Create database
	docker-compose exec php php bin/console doctrine:database:create --if-not-exists

db-drop: ## Drop database
	docker-compose exec php php bin/console doctrine:database:drop --force --if-exists

fresh: ## Fresh installation (clean + install)
	make clean
	make install

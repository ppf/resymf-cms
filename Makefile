.PHONY: help install test coverage phpstan psalm cs-check cs-fix quality-check quality-fix metrics security clean

# Colors for output
BLUE := \033[0;34m
GREEN := \033[0;32m
YELLOW := \033[0;33m
RED := \033[0;31m
NC := \033[0m # No Color

help: ## Show this help message
	@echo '$(BLUE)Available targets:$(NC)'
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  $(GREEN)%-20s$(NC) %s\n", $$1, $$2}'

install: ## Install all dependencies
	@echo '$(BLUE)Installing dependencies...$(NC)'
	composer install --prefer-dist --no-progress
	@echo '$(GREEN)✓ Dependencies installed$(NC)'

install-dev: ## Install development dependencies
	@echo '$(BLUE)Installing development dependencies...$(NC)'
	composer require --dev phpstan/phpstan phpstan/phpstan-symfony phpstan/phpstan-doctrine \
		friendsofphp/php-cs-fixer vimeo/psalm phploc/phploc squizlabs/php_codesniffer
	@echo '$(GREEN)✓ Development dependencies installed$(NC)'

test: ## Run PHPUnit tests
	@echo '$(BLUE)Running PHPUnit tests...$(NC)'
	@if [ -f "bin/phpunit" ]; then \
		bin/phpunit -c app; \
	else \
		vendor/bin/phpunit -c app; \
	fi
	@echo '$(GREEN)✓ Tests completed$(NC)'

coverage: ## Run tests with coverage report
	@echo '$(BLUE)Running tests with coverage...$(NC)'
	@mkdir -p build/coverage build/logs
	@if [ -f "bin/phpunit" ]; then \
		bin/phpunit -c app --coverage-html build/coverage --coverage-clover build/logs/clover.xml --coverage-text; \
	else \
		vendor/bin/phpunit -c app --coverage-html build/coverage --coverage-clover build/logs/clover.xml --coverage-text; \
	fi
	@echo '$(GREEN)✓ Coverage report generated in build/coverage/$(NC)'

phpstan: ## Run PHPStan static analysis
	@echo '$(BLUE)Running PHPStan analysis...$(NC)'
	@if [ ! -f "vendor/bin/phpstan" ]; then \
		echo '$(YELLOW)PHPStan not installed. Run: make install-dev$(NC)'; \
		exit 1; \
	fi
	@vendor/bin/phpstan analyse --memory-limit=1G
	@echo '$(GREEN)✓ PHPStan analysis completed$(NC)'

psalm: ## Run Psalm static analysis
	@echo '$(BLUE)Running Psalm analysis...$(NC)'
	@if [ ! -f "vendor/bin/psalm" ]; then \
		echo '$(YELLOW)Psalm not installed. Run: make install-dev$(NC)'; \
		exit 1; \
	fi
	@if [ ! -f "psalm.xml" ]; then \
		vendor/bin/psalm --init src 4; \
	fi
	@vendor/bin/psalm --show-info=true
	@echo '$(GREEN)✓ Psalm analysis completed$(NC)'

psalm-security: ## Run Psalm taint analysis for security
	@echo '$(BLUE)Running Psalm security analysis...$(NC)'
	@if [ ! -f "vendor/bin/psalm" ]; then \
		echo '$(YELLOW)Psalm not installed. Run: make install-dev$(NC)'; \
		exit 1; \
	fi
	@vendor/bin/psalm --taint-analysis --report=build/psalm-security.txt
	@echo '$(GREEN)✓ Psalm security analysis completed$(NC)'

cs-check: ## Check code style (dry-run)
	@echo '$(BLUE)Checking code style...$(NC)'
	@if [ ! -f "vendor/bin/php-cs-fixer" ]; then \
		echo '$(YELLOW)PHP-CS-Fixer not installed. Run: make install-dev$(NC)'; \
		exit 1; \
	fi
	@vendor/bin/php-cs-fixer fix --dry-run --diff --verbose
	@echo '$(GREEN)✓ Code style check completed$(NC)'

cs-fix: ## Fix code style issues automatically
	@echo '$(BLUE)Fixing code style...$(NC)'
	@if [ ! -f "vendor/bin/php-cs-fixer" ]; then \
		echo '$(YELLOW)PHP-CS-Fixer not installed. Run: make install-dev$(NC)'; \
		exit 1; \
	fi
	@vendor/bin/php-cs-fixer fix --verbose
	@echo '$(GREEN)✓ Code style fixed$(NC)'

metrics: ## Generate code metrics with PHPLOC
	@echo '$(BLUE)Generating code metrics...$(NC)'
	@if [ ! -f "vendor/bin/phploc" ]; then \
		echo '$(YELLOW)PHPLOC not installed. Run: make install-dev$(NC)'; \
		exit 1; \
	fi
	@mkdir -p build
	@vendor/bin/phploc src --log-json=build/phploc.json
	@echo '$(GREEN)✓ Code metrics generated in build/phploc.json$(NC)'

security: ## Run security checks
	@echo '$(BLUE)Running security checks...$(NC)'
	@echo '$(YELLOW)Composer security audit:$(NC)'
	@composer audit || echo '$(RED)⚠ Vulnerabilities found$(NC)'
	@echo ''
	@echo '$(YELLOW)Outdated dependencies:$(NC)'
	@composer outdated --direct || echo '$(YELLOW)⚠ Some dependencies are outdated$(NC)'
	@echo '$(GREEN)✓ Security checks completed$(NC)'

validate: ## Validate composer.json
	@echo '$(BLUE)Validating composer.json...$(NC)'
	@composer validate --strict
	@echo '$(GREEN)✓ Composer validation passed$(NC)'

quality-check: validate phpstan cs-check test ## Run all quality checks (validate, phpstan, cs-check, test)
	@echo '$(GREEN)✓✓✓ All quality checks passed! ✓✓✓$(NC)'

quality-fix: cs-fix test ## Fix code style and run tests
	@echo '$(GREEN)✓✓ Quality fixes applied and tests passed! ✓✓$(NC)'

full-check: validate phpstan psalm cs-check metrics test security ## Run comprehensive quality analysis
	@echo '$(GREEN)✓✓✓ Full quality analysis completed! ✓✓✓$(NC)'

ci-local: ## Simulate CI pipeline locally
	@echo '$(BLUE)Simulating CI pipeline...$(NC)'
	@echo ''
	@make validate
	@echo ''
	@make phpstan
	@echo ''
	@make cs-check
	@echo ''
	@make coverage
	@echo ''
	@make security
	@echo ''
	@echo '$(GREEN)✓✓✓ CI simulation completed! ✓✓✓$(NC)'

clean: ## Clean build artifacts and caches
	@echo '$(BLUE)Cleaning build artifacts...$(NC)'
	@rm -rf build/
	@rm -f .php-cs-fixer.cache
	@rm -f composer-audit.json outdated-deps.json security-report.json
	@echo '$(GREEN)✓ Build artifacts cleaned$(NC)'

clean-all: clean ## Clean everything including vendor and composer.lock
	@echo '$(BLUE)Cleaning vendor directory...$(NC)'
	@rm -rf vendor/
	@echo '$(GREEN)✓ Full cleanup completed$(NC)'

watch-tests: ## Watch for file changes and run tests
	@echo '$(BLUE)Watching for changes...$(NC)'
	@while true; do \
		find src -name "*.php" | entr -d make test; \
	done

# Docker-specific targets (if using Docker)
docker-test: ## Run tests in Docker container
	@echo '$(BLUE)Running tests in Docker...$(NC)'
	@docker-compose exec php vendor/bin/phpunit -c app

docker-bash: ## Open bash shell in PHP container
	@docker-compose exec php bash

docker-composer: ## Run composer commands in Docker
	@docker-compose exec php composer $(filter-out $@,$(MAKECMDGOALS))

# Prevent make from treating arguments as targets
%:
	@:

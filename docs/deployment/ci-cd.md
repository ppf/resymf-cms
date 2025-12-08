# CI/CD Pipeline

This guide covers the GitHub Actions CI/CD pipeline for ReSymf-CMS.

## Overview

The project uses three GitHub Actions workflows:

| Workflow | File | Purpose |
|----------|------|---------|
| Main Pipeline | `symfony-ci.yml` | Tests, quality checks |
| Security Scan | `security-scan.yml` | Vulnerability scanning |
| Quality Checks | `ci-quality.yml` | Code metrics |

---

## Main Pipeline

**File:** `.github/workflows/symfony-ci.yml`

**Triggers:**
- Push to `master`, `develop`, `claude/**`
- Pull requests to `master`, `develop`
- Manual dispatch

**Environment:**
- PHP 8.5
- MySQL 8.0
- Node.js 20

### Jobs

#### 1. Code Quality & Static Analysis
```yaml
- Composer validation
- PHPStan static analysis
- PHP-CS-Fixer (dry run)
```

#### 2. PHPUnit Tests
```yaml
- MySQL service container
- Migrations and fixtures
- PHPUnit test suites
- Coverage upload to Codecov
```

#### 3. Security Audit
```yaml
- composer audit
- Outdated dependency check
```

#### 4. Doctrine Schema Validation
```yaml
- Schema sync check
- Validates entity mappings
```

#### 5. Lint & Syntax Check
```yaml
- PHP syntax validation
- Twig template linting
- YAML config linting
```

---

## Security Scan

**File:** `.github/workflows/security-scan.yml`

**Schedule:** Weekly (Mondays 00:00 UTC)

### Jobs

1. **Composer Security Audit** - Vulnerability scanning
2. **Dependency Check** - Outdated packages
3. **OWASP Dependency Check** - Industry-standard scanning
4. **PHP Security Checker** - Known vulnerabilities
5. **Psalm Taint Analysis** - Security issues in code

---

## Local Quality Checks

Run these before pushing:

### PHPStan
```bash
docker-compose exec php vendor/bin/phpstan analyse
```

### PHP-CS-Fixer
```bash
# Check only
docker-compose exec php vendor/bin/php-cs-fixer fix --dry-run --diff

# Fix issues
docker-compose exec php vendor/bin/php-cs-fixer fix
```

### PHPUnit
```bash
docker-compose exec php bin/phpunit
```

### Composer Audit
```bash
docker-compose exec php composer audit
```

### Doctrine Validation
```bash
docker-compose exec php php bin/console doctrine:schema:validate
```

### Linting
```bash
# PHP syntax
docker-compose exec php find src -name "*.php" -exec php -l {} \;

# Twig templates
docker-compose exec php php bin/console lint:twig templates/

# YAML configs
docker-compose exec php php bin/console lint:yaml config/
```

---

## Pre-Commit Checks

Run all checks before committing:

```bash
#!/bin/bash
# Save as pre-commit.sh

echo "Running PHPStan..."
docker-compose exec php vendor/bin/phpstan analyse --no-progress

echo "Running PHP-CS-Fixer..."
docker-compose exec php vendor/bin/php-cs-fixer fix --dry-run --diff

echo "Running PHPUnit..."
docker-compose exec php bin/phpunit --stop-on-failure

echo "All checks passed!"
```

---

## Required CI Environment

### Secrets (GitHub Settings)

| Secret | Purpose |
|--------|---------|
| `CODECOV_TOKEN` | Code coverage upload |

### PHP Extensions

```yaml
extensions: mbstring, xml, ctype, iconv, intl, pdo, pdo_mysql, dom, filter, gd, json, zip
```

---

## Workflow Status

Check CI status:
- GitHub: Repository → Actions tab
- Badge in README:
  ```markdown
  ![CI](https://github.com/ppf/resymf-cms/actions/workflows/symfony-ci.yml/badge.svg)
  ```

---

## Debugging Failed Builds

### 1. Check Job Logs
GitHub Actions → Click failed job → View logs

### 2. Common Issues

**PHPStan errors:**
```bash
vendor/bin/phpstan analyse --error-format=table
```

**PHP-CS-Fixer:**
```bash
vendor/bin/php-cs-fixer fix --diff
```

**Test failures:**
```bash
bin/phpunit --stop-on-failure --verbose
```

**Migration issues:**
```bash
php bin/console doctrine:migrations:status
```

### 3. Reproduce Locally

```bash
# Match CI environment
docker-compose exec php composer install
docker-compose exec php bin/phpunit
docker-compose exec php vendor/bin/phpstan analyse
```

---

## Checklist

Before merging PRs:

1. [ ] All CI jobs pass (green checkmark)
2. [ ] No new PHPStan errors
3. [ ] Code style follows PHP-CS-Fixer rules
4. [ ] Tests pass and cover new code
5. [ ] No security vulnerabilities
6. [ ] Schema validates correctly

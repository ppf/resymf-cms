# Symfony 7 Quick Start Guide

**Branch**: `master`
**PHP**: 8.3+
**Symfony**: 7.3.*

> Looking for the legacy CI automation tips? See [`CI_QUICKSTART.md`](CI_QUICKSTART.md).

---

## Prerequisites

- PHP 8.3+ installed
- Composer 2.8+
- MySQL 8.0+ or MariaDB 10.11+
- Git

---

## Docker-based Setup (Recommended)

This is the easiest way to get a full development environment running.

### Prerequisites
- Docker and Docker Compose installed.

### 1. Build and Start Containers
From the repository root, run:
```bash
docker-compose up --build -d
```
This will build the PHP image and start the `php`, `nginx`, `db`, and `mailhog` services in the background.

- **Application**: http://localhost:8088
- **MailHog UI**: http://localhost:8026
- **Database Port**: 33066 (connect with a client like DBeaver or TablePlus)

### 2. Install Dependencies (First time only)
Composer dependencies are installed during the build, but the `vendor` directory is a named volume. If you need to reinstall:
```bash
docker-compose exec php composer install
```

### 3. Create Database and Schema
Run console commands inside the `php` container:
```bash
# Create the database
docker-compose exec php php bin/console doctrine:database:create

# Run migrations
docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction

# Load fixtures
docker-compose exec php php bin/console doctrine:fixtures:load --no-interaction
```

### 4. Stopping the Environment
```bash
docker-compose down
```
To stop and remove the database volume, use `docker-compose down -v`.

### Common Docker Commands
```bash
# Run any Symfony command
docker-compose exec php php bin/console <command>

# Open a shell in the PHP container
docker-compose exec php sh

# View logs for a service
docker-compose logs -f php
docker-compose logs -f nginx
```

---

## Manual Setup

For development without Docker.

### Prerequisites

- PHP 8.3+ installed
- Composer 2.8+
- MySQL 8.0+ or MariaDB 10.11+
- Git

---

### 1. Install Dependencies
```bash
composer install
```

### 2. Configure Database
Edit `.env.local` (create if it doesn't exist):
```env
DATABASE_URL="mysql://root:your_password@127.0.0.1:3306/resymf_cms?serverVersion=8.0&charset=utf8mb4"
```

### 3. Create Database
```bash
php bin/console doctrine:database:create
```

### 4. Run Migrations
```bash
php bin/console doctrine:migrations:migrate
```

### 5. Load Fixtures
```bash
php bin/console doctrine:fixtures:load
```

### 6. Start Development Server
```bash
symfony server:start
# OR
php -S localhost:8000 -t public/
```

Visit: http://localhost:8000

---

## Development Workflow

### Running Tests
```bash
# All tests
bin/phpunit

# Specific test
bin/phpunit tests/Entity/UserTest.php

# With coverage
XDEBUG_MODE=coverage bin/phpunit --coverage-html var/coverage
```

### Code Quality
```bash
# Static analysis (when configured)
vendor/bin/phpstan analyze src

# Code style (when configured)
vendor/bin/php-cs-fixer fix
```

### Clear Cache
```bash
# Dev
bin/console cache:clear

# Prod
bin/console cache:clear --env=prod
```

### Generate Entity
```bash
bin/console make:entity User
```

### Generate Migration
```bash
bin/console make:migration
# OR
bin/console doctrine:migrations:diff
```

---

## Current Status

### Phase 1: Foundation ✅ COMPLETE
- [x] Symfony 7.1.11 skeleton
- [x] Core bundles installed
- [x] Directory structure created
- [x] Database configured

### Phase 2: Entities (NEXT)
- [ ] User entity migration
- [ ] Role entity migration
- [ ] Settings entity migration
- [ ] Page entity migration
- [ ] ... (see MIGRATION_ROADMAP.md)

---

## Key Files

| File | Purpose |
|------|---------|
| `MIGRATION_ROADMAP.md` | Complete 10-phase migration plan |
| `composer.json` | Dependencies |
| `.env` | Environment configuration template |
| `config/packages/` | Bundle configurations |
| `config/routes/` | Routing |
| `src/CmsBundle/` | CMS bundle (legacy port) |
| `src/ProjectManagerBundle/` | Project Manager bundle |
| `migrations/` | Database migrations |
| `tests/` | PHPUnit tests |

---

## Bundle Structure

### CmsBundle
- **Entities**: User, Role, Settings, Page, Category, Theme
- **Features**: Authentication, CMS pages, admin CRUD
- **Routes**: `/admin/`, `/login`, `/{slug}`

### ProjectManagerBundle
- **Entities**: Project, Sprint, Task, Issue, Contact, Company, Document, Term
- **Features**: Scrum management, CRM-lite, file uploads
- **Routes**: `/admin/project/`, `/admin/task/`, etc.

---

## Next Actions

### Week 1 Priority
1. **Export legacy schema**:
   ```bash
   mysqldump --no-data -u root -p resymf_legacy > legacy_schema.sql
   ```

2. **Create User entity**:
   ```bash
   bin/console make:entity User
   # Add: id, username, email, password, roles[], isActive, createdAt
   ```

3. **Create UserFixtures**:
   ```bash
   bin/console make:fixtures UserFixtures
   # Add: admin user, regular user
   ```

4. **Write first test**:
   ```bash
   bin/console make:test functional UserAuthenticationTest
   # Test: login, logout, access control
   ```

5. **Configure security**:
   ```yaml
   # config/packages/security.yaml
   # Add: firewalls, access_control, password_hashers
   ```

---

## Troubleshooting

### "Database connection failed"
- Check `.env.local` DATABASE_URL
- Ensure MySQL is running
- Verify credentials

### "Class not found"
```bash
composer dump-autoload
bin/console cache:clear
```

### "Migration already executed"
```bash
bin/console doctrine:migrations:status
bin/console doctrine:migrations:version --delete <version> --all
```

---

## Resources

- **Symfony 7 Docs**: https://symfony.com/doc/7.1/
- **Doctrine ORM**: https://www.doctrine-project.org/projects/doctrine-orm/en/3.5/
- **MakerBundle**: https://symfony.com/bundles/SymfonyMakerBundle/current/
- **Testing**: https://symfony.com/doc/current/testing.html

---

**Questions?** Check `MIGRATION_ROADMAP.md` or legacy docs in `../docs/`

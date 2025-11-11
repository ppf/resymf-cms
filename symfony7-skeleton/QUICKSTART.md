# Symfony 7 Migration - Quick Start Guide

**Branch**: `symfony7-migration`
**PHP**: 8.3+
**Symfony**: 7.1.11

---

## Prerequisites

- PHP 8.3+ installed
- Composer 2.8+
- MySQL 8.0+ or MariaDB 10.11+
- Git

---

## Initial Setup

### 1. Install Dependencies
```bash
cd symfony7-skeleton
composer install
```

### 2. Configure Database
Edit `.env.local` (create if doesn't exist):
```env
DATABASE_URL="mysql://root:your_password@127.0.0.1:3306/resymf_cms?serverVersion=8.0&charset=utf8mb4"
```

### 3. Create Database
```bash
bin/console doctrine:database:create
```

### 4. Run Migrations (when ready)
```bash
bin/console doctrine:migrations:migrate
```

### 5. Load Fixtures (when ready)
```bash
bin/console doctrine:fixtures:load
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

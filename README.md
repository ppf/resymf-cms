# ReSymf-CMS

A modern, lightweight CMS built with Symfony 7, Vue 3, and Tailwind CSS.

![PHP](https://img.shields.io/badge/PHP-8.5-777BB4?logo=php&logoColor=white)
![Symfony](https://img.shields.io/badge/Symfony-7.3-000000?logo=symfony&logoColor=white)
![Vue](https://img.shields.io/badge/Vue-3-4FC08D?logo=vue.js&logoColor=white)
![Tailwind](https://img.shields.io/badge/Tailwind-3-06B6D4?logo=tailwindcss&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-blue)

## Features

- **Modern Stack**: Symfony 7.3, PHP 8.5, Vue 3, Tailwind CSS, Vite
- **Admin Panel**: Full CRUD with Vue-powered grids and forms
- **CMS Pages**: Slug-based routing with SEO support
- **Security**: CSRF protection, login throttling, 2FA support
- **API-Ready**: JSON endpoints for Vue islands
- **Docker**: Complete development environment

## Quick Start

```bash
# Clone and start
git clone https://github.com/ppf/resymf-cms.git
cd resymf-cms
docker-compose up -d --build

# Wait for containers, then setup database
docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction
docker-compose exec php php bin/console doctrine:fixtures:load --no-interaction
```

**Access:**
- Application: http://localhost:8088
- Mailpit UI: http://localhost:8030
- Database: localhost:33066

**Default credentials:** `admin` / `admin123`

## Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | Symfony 7.3, PHP 8.5, Doctrine ORM 3 |
| Frontend | Vue 3, Vite, Tailwind CSS |
| Database | MariaDB 11 / MySQL 8 |
| Infrastructure | Docker, Nginx, PHP-FPM |
| Testing | PHPUnit, PHPStan, PHP-CS-Fixer |

## Documentation

- [Installation Guide](docs/getting-started/installation.md)
- [Configuration](docs/getting-started/configuration.md)
- [Architecture Overview](docs/development/architecture.md)
- [Creating Entities](docs/development/entities.md)
- [Admin CRUD](docs/development/admin-crud.md)
- [Vue Islands](docs/development/vue-islands.md)
- [Forms](docs/development/forms.md)
- [Testing](docs/testing/testing.md)
- [Production Deployment](docs/deployment/production.md)
- [CI/CD](docs/deployment/ci-cd.md)
- [Security](docs/security.md)

## Development

```bash
# Frontend development (HMR)
npm run dev

# Build for production
npm run build

# Run tests
docker-compose exec php bin/phpunit

# Code quality
docker-compose exec php vendor/bin/phpstan analyse
docker-compose exec php vendor/bin/php-cs-fixer fix --dry-run
```

## License

MIT License - Copyright (c) 2013-2025 Piotr Francuz

See [LICENSE](LICENSE) for details.

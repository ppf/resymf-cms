# Installation Guide

## Prerequisites

**Docker (Recommended):**
- Docker and Docker Compose

**Manual Setup:**
- PHP 8.3+ with extensions: mbstring, xml, intl, pdo_mysql, gd, zip
- Composer 2.8+
- Node.js 20+
- MySQL 8.0+ or MariaDB 10.11+
- Git

---

## Docker Setup (Recommended)

### 1. Clone and Start

```bash
git clone https://github.com/ppf/resymf-cms.git
cd resymf-cms
docker-compose up -d --build
```

This starts all services:

| Service | Description | Port |
|---------|-------------|------|
| php | PHP 8.5 FPM | - |
| nginx | Web server | 8088 |
| db | MariaDB 11 | 33066 |
| mailpit | Email testing | 1030 (SMTP), 8030 (UI) |
| frontend-build | Vite asset builder | - |
| scheduler | Cron job worker | - |

### 2. Frontend Build

The `frontend-build` container automatically runs on startup:
- Image: `node:20-alpine`
- Command: `npm install && npm run build`
- Output: `public/build/`

The PHP container depends on frontend-build, so assets are ready before the app starts.

### 3. Database Setup

```bash
# Create database schema
docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction

# Load sample data
docker-compose exec php php bin/console doctrine:fixtures:load --no-interaction
```

### 4. Access the Application

- **Application:** http://localhost:8088
- **Mailpit UI:** http://localhost:8030
- **Database:** localhost:33066 (user: resymf, password: password)

**Default login:** `admin` / `admin123`

### Common Docker Commands

```bash
# Start containers
docker-compose up -d

# Stop containers
docker-compose down

# View logs
docker-compose logs -f php
docker-compose logs -f nginx

# Run Symfony commands
docker-compose exec php php bin/console <command>

# Open shell in PHP container
docker-compose exec php sh

# Rebuild containers
docker-compose up -d --build

# Remove volumes (reset database)
docker-compose down -v
```

---

## Manual Setup

### 1. Install Dependencies

```bash
composer install
npm install
```

### 2. Configure Environment

```bash
cp .env .env.local
```

Edit `.env.local`:
```dotenv
DATABASE_URL="mysql://root:password@127.0.0.1:3306/resymf_cms?serverVersion=8.0&charset=utf8mb4"
MAILER_DSN=smtp://localhost:1025
```

### 3. Setup Database

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
```

### 4. Build Frontend

```bash
# Development with HMR
npm run dev

# Production build
npm run build
```

### 5. Start Server

```bash
symfony server:start
# OR
php -S localhost:8000 -t public/
```

---

## Frontend Development

For hot module replacement during development:

```bash
npm run dev -- --host 127.0.0.1 --port 5173
```

Vite dev server runs on http://localhost:5173 with HMR enabled.

The Twig helpers (`vite_entry_script_tags`) automatically detect dev mode and serve from Vite.

---

## Troubleshooting

### "Database connection failed"
- Check DATABASE_URL in `.env.local`
- Ensure MySQL/MariaDB is running
- Verify credentials

### "Class not found"
```bash
composer dump-autoload
php bin/console cache:clear
```

### "Migration already executed"
```bash
php bin/console doctrine:migrations:status
php bin/console doctrine:migrations:version --delete <version>
```

### Docker: "Port already in use"
```bash
# Find process using port
lsof -i :8088

# Or change port in docker-compose.yml
```

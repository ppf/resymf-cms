# Configuration

## Environment Variables

Create `.env.local` for local overrides (never committed to git).

### Core Settings

```dotenv
# Application
APP_ENV=dev                    # dev, prod, test
APP_SECRET=your-secret-key     # Generate with: openssl rand -base64 32
APP_DEBUG=1                    # 1 for dev, 0 for prod

# Database
DATABASE_URL="mysql://user:password@127.0.0.1:3306/resymf_cms?serverVersion=8.0&charset=utf8mb4"

# Mailer
MAILER_DSN=smtp://localhost:1025  # Mailpit for dev
```

### Docker Environment

The `docker-compose.yml` sets these automatically:
```dotenv
DB_HOST=db
DB_USER=resymf
DB_PASS=password
MAILER_DSN=smtp://mailpit:1025
```

---

## Docker Services

### php
- **Image:** Custom (from Dockerfile)
- **Target:** `app_dev` stage
- **Extensions:** GD, IntL, ZIP, PDO MySQL, GMP
- **Volumes:** Application code, vendor (named volume)

### nginx
- **Image:** `nginx:1.25-alpine`
- **Port:** 8088 → 80
- **Config:** `docker/nginx/default.conf`

### db
- **Image:** `mariadb:11`
- **Port:** 33066 → 3306
- **Credentials:**
  - Database: `resymf_cms`
  - User: `resymf`
  - Password: `password`
  - Root password: `root_password`

### mailpit
- **Image:** `axllent/mailpit:latest`
- **Ports:**
  - 1030 → 1025 (SMTP)
  - 8030 → 8025 (Web UI)
- **Usage:** All emails sent by the app appear in the web UI

### frontend-build
- **Image:** `node:20-alpine`
- **Command:** `npm install && npm run build`
- **Purpose:** Builds Vite assets on container startup
- **Output:** `public/build/`

### scheduler
- **Image:** Custom (from Dockerfile)
- **Command:** `php bin/console messenger:consume scheduler_cron --time-limit=60 -vv`
- **Purpose:** Processes scheduled tasks
- **Restart:** `unless-stopped`

---

## Security Configuration

### config/packages/security.yaml

Key settings:
```yaml
security:
    password_hashers:
        App\Entity\User:
            algorithm: bcrypt
            cost: 12

    firewalls:
        main:
            login_throttling:
                max_attempts: 5
                interval: '1 minute'

            form_login:
                enable_csrf: true

            remember_me:
                lifetime: 604800  # 1 week

            two_factor:
                auth_form_path: 2fa_login
                check_path: 2fa_login_check
```

---

## Vite Configuration

### vite.config.mjs

```javascript
export default defineConfig({
  build: {
    manifest: true,
    outDir: 'public/build',
    rollupOptions: {
      input: {
        app: './assets/app.js',
        admin: './assets/admin.js',
        cms: './assets/cms.js',
        // Vue islands
        'category-form-app': './assets/vue/category-form-app.js',
        // ... more entries
      },
    },
  },
  server: {
    port: 5173,
    host: 'localhost',
  },
});
```

Add new Vue islands to `rollupOptions.input`.

---

## Database Connection

### Docker
```bash
# Connect from host
mysql -h 127.0.0.1 -P 33066 -u resymf -ppassword resymf_cms

# Connect from container
docker-compose exec db mysql -u resymf -ppassword resymf_cms
```

### GUI Clients
- Host: `127.0.0.1`
- Port: `33066`
- User: `resymf`
- Password: `password`
- Database: `resymf_cms`

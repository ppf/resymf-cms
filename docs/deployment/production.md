# Production Deployment

This guide covers deploying ReSymf-CMS to production.

## Server Requirements

- **OS:** Ubuntu 22.04 LTS or Debian 12
- **PHP:** 8.3+ with extensions: fpm, mysql, curl, xml, mbstring, intl, zip, gd, opcache
- **Database:** MySQL 8.0+ or MariaDB 10.11+
- **Web Server:** Nginx (recommended) or Apache
- **Node.js:** 20+ (for building assets)
- **Composer:** 2.8+
- **Git**

---

## Ubuntu Server Setup

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install Nginx and Git
sudo apt install -y nginx git

# Add PHP PPA
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP 8.3
sudo apt install -y php8.3-fpm php8.3-cli php8.3-mysql php8.3-curl \
  php8.3-xml php8.3-mbstring php8.3-intl php8.3-zip php8.3-gd php8.3-opcache

# Install Composer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm composer-setup.php

# Install Node.js 20
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

---

## Application Setup

### 1. Create Deploy User

```bash
sudo adduser resymf
sudo usermod -a -G www-data resymf
```

### 2. Clone Repository

```bash
sudo -i -u resymf
git clone https://github.com/ppf/resymf-cms.git ~/resymf-cms
cd ~/resymf-cms
```

### 3. Install Dependencies

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
```

### 4. Configure Environment

Create `.env.prod.local`:

```dotenv
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=generate-a-strong-secret-key

DATABASE_URL="mysql://user:password@localhost:3306/resymf_cms?serverVersion=8.0"

MAILER_DSN=smtp://user:pass@smtp.example.com:587

TRUSTED_HOSTS='^your-domain\.com$'
```

Generate secret:
```bash
openssl rand -base64 32
```

### 5. Setup Database

```bash
php bin/console doctrine:database:create --env=prod
php bin/console doctrine:migrations:migrate --env=prod --no-interaction
```

### 6. Set Permissions

```bash
sudo chown -R resymf:www-data var/
sudo chmod -R 775 var/
```

---

## Nginx Configuration

Create `/etc/nginx/sites-available/resymf-cms`:

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /home/resymf/resymf-cms/public;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        internal;
    }

    location ~ \.php$ {
        return 404;
    }

    error_log /var/log/nginx/resymf-cms_error.log;
    access_log /var/log/nginx/resymf-cms_access.log;
}
```

Enable site:
```bash
sudo ln -s /etc/nginx/sites-available/resymf-cms /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## SSL with Let's Encrypt

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com
```

---

## Deploy Script

Use the included `deploy.sh`:

```bash
chmod +x deploy.sh
./deploy.sh
```

Or manually:

```bash
# Enable maintenance mode
touch public/maintenance.flag

# Pull latest code
git pull origin master

# Install dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
php bin/console doctrine:migrations:migrate --env=prod --no-interaction

# Clear cache
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

# Build assets
npm install
npm run build

# Disable maintenance mode
rm public/maintenance.flag
```

---

## Docker Production

### Build Production Image

```bash
docker build --target app_prod -t resymf-cms:prod .
```

### docker-compose.prod.yml

```yaml
services:
  php:
    build:
      context: .
      target: app_prod
    environment:
      APP_ENV: prod
      APP_DEBUG: 0
      APP_SECRET: ${APP_SECRET}
      DATABASE_URL: ${DATABASE_URL}

  nginx:
    ports:
      - "80:80"
      - "443:443"
```

### Run Production

```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

---

## Monitoring

### Application Logs
```bash
tail -f var/log/prod.log
```

### Nginx Logs
```bash
tail -f /var/log/nginx/resymf-cms_error.log
```

### PHP-FPM Status
```bash
sudo systemctl status php8.3-fpm
```

---

## Performance

### PHP OPcache

Enable in `php.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
```

### Symfony Cache

Warm up cache:
```bash
php bin/console cache:warmup --env=prod
```

---

## Backup

### Database
```bash
mysqldump -u user -p resymf_cms > backup_$(date +%Y%m%d).sql
```

### Files
```bash
tar -czf uploads_$(date +%Y%m%d).tar.gz public/uploads/
```

---

## Checklist

Before deploying:

1. [ ] Environment variables configured
2. [ ] Database created and migrations run
3. [ ] Assets built (`npm run build`)
4. [ ] Cache cleared and warmed
5. [ ] Permissions set correctly
6. [ ] SSL certificate installed
7. [ ] Backup strategy in place

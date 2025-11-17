# Production Deployment Guide

This guide provides comprehensive instructions for deploying the ReSymf-CMS (Symfony 7) application to a production environment.

**Deployment Strategy**: This guide assumes a standard deployment model where code is pulled from a Git repository onto a production server.

## 1. Server Requirements

Before deploying, ensure your production server meets the following requirements:

- **Operating System**: A modern Linux distribution (e.g., Ubuntu 22.04 LTS, Debian 12).
- **Web Server**: Nginx (recommended) or Apache 2.4+.
- **PHP**: Version 8.3 or higher, with the following extensions:
  - `php-cli`, `php-fpm` (if using Nginx)
  - `php-mysql` (or `php-pgsql`)
  - `php-curl`, `php-json`, `php-mbstring`, `php-xml`, `php-intl`
  - `php-zip`, `php-gd`
  - `php-opcache` (highly recommended for performance)
- **Database**: MySQL 8.0+ or PostgreSQL 14+.
- **Composer**: The latest version of [Composer](https://getcomposer.org/).
- **Git**: For pulling code from the repository.
- **Node.js**: Optional, but useful for frontend asset management if you extend beyond the Asset Mapper.

### Example: Ubuntu 22.04 Server Setup
```bash
# Update package lists
sudo apt update && sudo apt upgrade -y

# Install Nginx, Git
sudo apt install -y nginx git

# Add PHP PPA for version 8.3
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP 8.3 and required extensions
sudo apt install -y php8.3-fpm php8.3-cli php8.3-mysql php8.3-curl \
  php8.3-xml php8.3-mbstring php8.3-intl php8.3-zip php8.3-gd php8.3-opcache

# Install Composer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
php -r "unlink('composer-setup.php');"
```

## 2. Initial Server Configuration

### Create a Deploy User
It is best practice to run your application under a dedicated user.

```bash
# Create a new user (e.g., 'resymf')
sudo adduser resymf

# Add the user to the web server group
sudo usermod -a -G www-data resymf
```

### Clone the Repository
As the new user, clone the project into their home directory.

```bash
# Switch to the new user
sudo -i -u resymf

# Clone the repository
git clone <your-repository-url> /home/resymf/resymf-cms
cd /home/resymf/resymf-cms
```

### Directory Permissions
Set the correct permissions for the application directories. The web server needs to be able to write to `var/`.

```bash
# From within /home/resymf/resymf-cms/symfony7-skeleton
HTTPDUSER=$(ps axo user,comm | grep -E '[a]pache|[h]ttpd|[_]www|[w]ww-data|[n]ginx' | grep -v root | head -1 | cut -d\  -f1)
sudo setfacl -R -m u:"$HTTPDUSER":rwX -m u:$(whoami):rwX var
sudo setfacl -dR -m u:"$HTTPDUSER":rwX -m u:$(whoami):rwX var
```

## 3. Production Environment Configuration

Symfony uses `.env` files for configuration. For production, you should create a `.env.prod.local` file for your secrets. This file is ignored by Git and should **never** be committed.

1.  **Navigate to the project directory**:
    ```bash
    cd /home/resymf/resymf-cms/symfony7-skeleton
    ```

2.  **Create the production environment file**:
    ```bash
    touch .env.prod.local
    ```

3.  **Edit the file** and add your production-specific variables. At a minimum, you will need:

    ```dotenv
    # .env.prod.local

    # Set the environment to production
    APP_ENV=prod
    APP_DEBUG=0

    # Generate a strong, random secret for production
    # You can generate one with: openssl rand -base64 32
    APP_SECRET='your-super-strong-random-secret'

    # Production Database Connection
    # Example for MySQL/MariaDB
    DATABASE_URL="mysql://user:password@127.0.0.1:3306/resymf_cms?serverVersion=8.0&charset=utf8mb4"

    # Production Mailer DSN
    # Example for SendGrid (or other transactional email service)
    MAILER_DSN=sendgrid://KEY@default

    # Trusted hosts (replace with your domain)
    TRUSTED_HOSTS='^your-domain-name\.com$'
    ```

## 4. Web Server Configuration (Nginx)

Create a new Nginx virtual host for your application.

1.  **Create a new Nginx configuration file**:
    ```bash
    sudo nano /etc/nginx/sites-available/resymf-cms.conf
    ```

2.  **Add the following configuration**, replacing `your-domain-name.com` and paths as needed. A full example can be found in `nginx.conf.example`.

    ```nginx
    server {
        listen 80;
        server_name your-domain-name.com;
        root /home/resymf/resymf-cms/symfony7-skeleton/public;

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

3.  **Enable the site and restart Nginx**:
    ```bash
    sudo ln -s /etc/nginx/sites-available/resymf-cms.conf /etc/nginx/sites-enabled/
    sudo nginx -t # Test configuration
    sudo systemctl restart nginx
    ```

## 5. Deployment Process

A `deploy.sh` script is provided to automate these steps.

### Manual Deployment Steps (for reference)

If you need to deploy manually, follow these steps from within the `symfony7-skeleton` directory:

1.  **Enable Maintenance Mode** (Optional, but recommended)
    ```bash
    # Create a maintenance flag file if your app supports it
    touch public/maintenance.flag
    ```

2.  **Pull Latest Code**
    ```bash
    git pull origin main
    ```

3.  **Install Composer Dependencies**
    ```bash
    composer install --no-dev --optimize-autoloader
    ```

4.  **Run Database Migrations**
    ```bash
    php bin/console doctrine:migrations:migrate --env=prod --no-interaction
    ```

5.  **Clear and Warm Up Cache**
    ```bash
    php bin/console cache:clear --env=prod
    ```

6.  **Compile Asset Map**
    ```bash
    php bin/console asset-map:compile
    ```

7.  **Disable Maintenance Mode**
    ```bash
    rm public/maintenance.flag
    ```

### Using the `deploy.sh` Script

The `deploy.sh` script automates the entire process.

1.  **Make the script executable**:
    ```bash
    chmod +x deploy.sh
    ```

2.  **Run the script**:
    ```bash
    ./deploy.sh
    ```

The script will guide you through the deployment, showing each step as it executes.

## 6. Post-Deployment Checks

After deploying, you should:

1.  **Check the application logs** for any errors:
    ```bash
    tail -f var/log/prod.log
    ```
2.  **Browse the website** to ensure it is functioning correctly.
3.  **Test key functionality**, such as user login and form submissions.

---

## Alternative: Deploying with Docker

The included Docker configuration can be adapted for production. This approach containerizes the application and its services, providing a consistent and isolated environment.

### 1. Production Docker Compose
Create a `docker-compose.prod.yml` file to override the base `docker-compose.yml` for production.

```yaml
# docker-compose.prod.yml
version: '3.8'

services:
  php:
    build:
      context: .
      target: app_prod # Use the production stage from the Dockerfile
    volumes:
      # In production, we use the code baked into the image, not a volume
      - /app/vendor # Keep the anonymous volume for vendor
    environment:
      # Override environment variables for production
      APP_ENV: prod
      APP_DEBUG: 0
      # Load other secrets from a .env file on the host

  nginx:
    ports:
      - "80:80"
      - "443:443"
    volumes:
      # Mount your SSL certificates and production Nginx config
      # - ./docker/nginx/prod.conf:/etc/nginx/conf.d/default.conf:ro
      # - /path/to/your/ssl/certs:/etc/ssl/certs:ro
      - .:/app:ro # Keep this for Nginx to access public assets

```

### 2. Environment Variables
On your production server, create a `.env` file that `docker-compose` will use to substitute variables.

```dotenv
# .env file on the production host
APP_SECRET=your-super-strong-random-secret
DATABASE_URL="mysql://resymf:password@db:3306/resymf_cms?serverVersion=8.0&charset=utf8mb4"
MAILER_DSN=sendgrid://KEY@default
TRUSTED_HOSTS='^your-domain-name\.com$'
```

### 3. Running in Production

1.  **Build the production image**:
    ```bash
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml build --no-cache
    ```

2.  **Start the services**:
    ```bash
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d
    ```

### 4. Docker-based Deployment Workflow

For a "zero-downtime" deployment with Docker Compose:

1.  **Pull the latest code**:
    ```bash
    git pull origin main
    ```

2.  **Re-build the PHP image with the new code**:
    ```bash
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml build php
    ```

3.  **Re-create only the `php` service**:
    Docker Compose will start a new `php` container and, once it's healthy, stop the old one. Nginx will then route traffic to the new container.
    ```bash
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --no-deps php
    ```

4.  **Run database migrations**:
    ```bash
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec php php bin/console doctrine:migrations:migrate --env=prod --no-interaction
    ```

This concludes the production deployment guide.

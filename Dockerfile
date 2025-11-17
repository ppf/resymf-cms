# Dockerfile for ReSymf-CMS (Symfony 7)

# --- Base Stage ---
# Use the official PHP 8.3 FPM image as a base.
# This stage installs PHP extensions and Composer.
FROM php:8.3-fpm-alpine AS app_base

# Set working directory
WORKDIR /app

# Install system dependencies required for PHP extensions
RUN apk add --no-cache \
    acl \
    fcgi \
    file \
    git \
    curl \
    libzip-dev \
    libpng-dev \
    jpeg-dev \
    freetype-dev \
    icu-dev \
    gmp-dev

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    gd \
    intl \
    opcache \
    zip \
    pdo_mysql \
    gmp

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# --- Development Stage ---
# This stage is for local development. It includes Xdebug.
FROM app_base AS app_dev

# Install Xdebug and its dependencies
RUN apk add --no-cache --update linux-headers $PHPIZE_DEPS \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del $PHPIZE_DEPS

# Configure Xdebug
RUN echo "xdebug.mode=develop,debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Copy application code
COPY . .

# Set permissions for cache and logs
RUN mkdir -p var/cache var/log \
    && chown -R www-data:www-data var \
    && chmod -R 775 var

# Install dependencies
RUN composer install --prefer-dist --no-scripts --no-progress

# --- Production Stage ---
# This stage creates a lean image for production.
FROM app_base AS app_prod

# Set ARG for environment
ARG APP_ENV=prod

# Copy application code
COPY . .

# Set permissions for cache and logs
RUN mkdir -p var/cache var/log \
    && chown -R www-data:www-data var \
    && chmod -R 775 var

# Install production dependencies
RUN composer install --no-dev --no-scripts --optimize-autoloader

# Run composer scripts (e.g., for cache warming)
RUN APP_ENV=$APP_ENV composer run-script post-install-cmd

# Clean up composer cache
RUN composer clear-cache

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]

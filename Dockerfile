# Dockerfile for ReSymf-CMS (Symfony 7)

# --- Frontend Build Stage ---
FROM node:20-alpine AS frontend_build
WORKDIR /app
# Install frontend dependencies and build assets
COPY package*.json vite.config.mjs ./
COPY assets ./assets
RUN npm install
RUN npm run build

# --- Base Stage ---
# Use the official PHP 8.5 FPM image as a base.
# This stage installs PHP extensions and Composer.
FROM php:8.5-fpm-alpine AS app_base

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

# Install PHP extensions (opcache is built-in to PHP 8.5)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd \
    && docker-php-ext-install intl \
    && docker-php-ext-install zip \
    && docker-php-ext-install pdo_mysql \
    && docker-php-ext-install gmp

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# --- Development Stage ---
# This stage is for local development. It includes Xdebug.
FROM app_base AS app_dev

# Xdebug not yet available for PHP 8.5 (requires <= 8.4.99)
# Uncomment when xdebug adds PHP 8.5 support:
# RUN apk add --no-cache --update linux-headers $PHPIZE_DEPS \
#     && pecl install xdebug \
#     && docker-php-ext-enable xdebug \
#     && apk del $PHPIZE_DEPS
# RUN echo "xdebug.mode=develop,debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
#     && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Copy composer files first for layer caching
COPY composer.json composer.lock symfony.lock ./

# Install dependencies (cached unless composer files change)
RUN composer install --prefer-dist --no-scripts --no-progress

# Copy application code
COPY . .

# Set permissions for cache and logs
RUN mkdir -p var/cache var/log \
    && chown -R www-data:www-data var \
    && chmod -R 775 var

# --- Production Stage ---
# This stage creates a lean image for production.
FROM app_base AS app_prod

# Set ARG for environment
ARG APP_ENV=prod

# Copy composer files first for layer caching
COPY composer.json composer.lock symfony.lock ./

# Install production dependencies (cached unless composer files change)
RUN composer install --no-dev --no-scripts --optimize-autoloader

# Copy application code
COPY . .

# Copy built frontend assets from the frontend build stage
COPY --from=frontend_build /app/public/build /app/public/build

# Set permissions for cache and logs
RUN mkdir -p var/cache var/log \
    && chown -R www-data:www-data var \
    && chmod -R 775 var

# Run composer scripts (e.g., for cache warming)
RUN APP_ENV=$APP_ENV composer run-script post-install-cmd

# Clean up for smallest possible image
RUN composer clear-cache \
    && rm -rf /root/.composer /tmp/* \
    && rm -rf var/cache/dev var/cache/test

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]

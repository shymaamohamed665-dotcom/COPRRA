# Stage 1: Dependencies
FROM php:8.4-fpm AS dependencies
WORKDIR /var/www/html

# Install minimal dependencies needed for composer
RUN apt-get update && apt-get install -y --no-install-recommends \
    unzip libzip-dev \
    && docker-php-ext-install zip bcmath \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY composer.json composer.lock ./
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Stage 2: Frontend Build
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# Stage 3: Production
FROM php:8.4-fpm
WORKDIR /var/www/html

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpng-dev libjpeg-dev libfreetype6-dev libicu-dev libcurl4-openssl-dev libxml2-dev libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql zip gd opcache intl mbstring bcmath exif curl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install and enable phpredis extension
RUN pecl install redis \
    && docker-php-ext-enable redis

# Composer binary for autoload dump
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy dependencies from stage 1
COPY --from=dependencies /var/www/html/vendor ./vendor

# Copy built assets from stage 2
COPY --from=frontend /app/public/build ./public/build

# Copy application (selective whitelist)
COPY composer.json composer.lock ./
COPY app/ ./app/
COPY config/ ./config/
COPY routes/ ./routes/
COPY public/ ./public/
COPY resources/ ./resources/
COPY bootstrap/ ./bootstrap/
COPY artisan ./artisan

# Copy PHP configuration (retain existing project configs)
COPY docker/php.ini /usr/local/etc/php/conf.d/opcache-prod.ini
COPY custom-php.ini /usr/local/etc/php/conf.d/custom.ini

# Ensure required Laravel writable directories exist before Composer scripts
RUN mkdir -p bootstrap/cache \
    && mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Generate optimized autoloader
RUN composer dump-autoload --optimize --classmap-authoritative

# Laravel optimization caches
RUN php artisan config:cache && php artisan route:cache && php artisan view:cache

# Set permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html && \
    mkdir -p public/build/assets && \
    chown -R www-data:www-data public/build && \
    chmod -R 775 storage bootstrap/cache public/build

USER www-data

EXPOSE 9000
CMD ["php-fpm"]

# Use the official PHP 8.4 image with FPM for development
FROM php:8.4-fpm

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
# We add sudo to allow running commands as a different user
# --- START: MODIFIED CODE ---
# Added nodejs, npm for frontend tooling, and libicu-dev for the intl PHP extension.
RUN apt-get update && apt-get install -y --no-install-recommends \
    git=1:2.47.3-0+deb13u1 \
    curl=8.14.1-2 \
    libpng-dev=1.6.48-1 \
    libonig-dev=6.9.9-1+b1 \
    libxml2-dev=2.12.7+dfsg+really2.9.14-2.1+deb13u1 \
    zip=3.0-15 \
    unzip=6.0-29 \
    libzip-dev=1.11.3-2 \
    libicu-dev=76.1-4 \
    gnupg=2.4.7-21 \
    ca-certificates=20250419 \
    libsqlite3-dev=3.46.0-2 && \
    rm -rf /var/lib/apt/lists/*

# Install Node.js and npm from Debian repos (pinned versions)
RUN apt-get update && apt-get install -y --no-install-recommends nodejs=20.19.2+dfsg-1 npm=9.2.0~ds1-3 && \
    rm -rf /var/lib/apt/lists/*
# --- END: MODIFIED CODE ---

# Install PHP extensions required by Laravel
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl sqlite3 pdo_sqlite

# Install and enable phpredis extension
RUN pecl install redis \
    && docker-php-ext-enable redis

# Install and enable PCOV for code coverage
RUN pecl install pcov \
    && docker-php-ext-enable pcov

# Install Composer (PHP package manager)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create user with specific UID/GID and set permissions
RUN groupadd -g 1000 appuser && \
    useradd -u 1000 -g appuser -s /bin/bash -m appuser && \
    mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache && \
    mkdir -p /var/www/html/public/build/assets && \
    chown -R appuser:appuser /var/www/html && \
    chmod -R 755 /var/www/html && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/public/build

# Apply custom PHP configuration (unlimited memory for CLI operations)
# Note: Build context is project root, so reference the file by its path
COPY dev-docker/zz-custom.ini /usr/local/etc/php/conf.d/zz-custom.ini
COPY dev-docker/php.ini /usr/local/etc/php/conf.d/opcache-dev.ini
# Override PHP-FPM pool settings to improve dev performance and avoid 504s
COPY dev-docker/www.conf /usr/local/etc/php-fpm.d/www.conf

# Switch to the new user
USER appuser

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]
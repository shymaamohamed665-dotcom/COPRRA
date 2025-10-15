# Use the official PHP 8.3 image with FPM for development
FROM php:8.3-fpm

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
# We add sudo to allow running commands as a different user
# --- START: MODIFIED CODE ---
# Added nodejs, npm for frontend tooling, and libicu-dev for the intl PHP extension.
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libicu-dev \
    gnupg \
    ca-certificates && \
    rm -rf /var/lib/apt/lists/*

# Install Node.js from NodeSource
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && \
    apt-get update && apt-get install -y nodejs && \
    npm install -g npm@latest
# --- END: MODIFIED CODE ---

# Install PHP extensions required by Laravel
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl

# Install and enable phpredis extension
RUN pecl install redis \
    && docker-php-ext-enable redis

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

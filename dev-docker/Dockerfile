# Use the official PHP 8.2 image with FPM for development
FROM php:8.2-fpm

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
# We add sudo to allow running commands as a different user
# --- START: MODIFIED CODE ---
# Added nodejs and npm for frontend tooling and Husky hooks
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    sudo \
    nodejs \
    npm
# --- END: MODIFIED CODE ---

# Install PHP extensions required by Laravel
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl

# Install Composer (PHP package manager)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create a new user 'dev' to avoid running as root
# This is a security best practice and avoids permission issues on the host machine
RUN useradd -G www-data,sudo -u 1000 -d /home/dev dev

# Give dev user passwordless sudo access
RUN echo "dev ALL=(ALL) NOPASSWD:ALL" > /etc/sudoers.d/dev

RUN mkdir -p /home/dev/.composer && \
    chown -R dev:dev /home/dev

# Switch to the new user
USER dev

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]

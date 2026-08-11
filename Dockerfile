FROM serversideup/php:8.2-cli

WORKDIR /app

# Make sure we're root for package installation
USER root

# Set PORT environment variable
ENV PORT=8000

# Install required PHP extensions
RUN install-php-extensions \
    ctype \
    curl \
    dom \
    exif \
    fileinfo \
    filter \
    hash \
    mbstring \
    openssl \
    pcre \
    pdo \
    pdo_pgsql \
    session \
    tokenizer \
    xml \
    zip

# Install system packages + Node.js
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    curl \
    nodejs \
    npm \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# IMPORTANT:
# Copy the whole Laravel project BEFORE composer install
COPY . .

# Install PHP dependencies
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist

# Install frontend dependencies and build Vite
RUN npm ci
RUN npm run build

# Laravel storage/cache directories
RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

RUN chmod -R 775 storage bootstrap/cache

# Laravel caches
RUN php artisan config:clear
RUN php artisan route:clear
RUN php artisan view:clear

# Railway PORT
ENV SERVER_NAME=:${PORT}

EXPOSE 8080

# Start Laravel
CMD ["sh", "-c", "php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT}"]
FROM serversideup/php:8.2-fpm-nginx

WORKDIR /var/www/html

# Root user for installation
USER root

# PHP extensions (serversideup image mein already hain, sirf missing ones add karo)
RUN install-php-extensions \
    exif \
    pdo_pgsql \
    zip

# System packages
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    curl \
    nodejs \
    npm \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Project files copy karo
COPY --chown=www-data:www-data . .

# PHP dependencies
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist

# Frontend build
RUN npm ci && npm run build

# Laravel directories
RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

# Laravel cache clear
RUN php artisan config:clear \
    && php artisan route:clear \
    && php artisan view:clear

# PORT
ENV PORT=8080
EXPOSE 8080

# Start
CMD ["sh", "-c", "php artisan migrate --force && php-fpm-nginx"]
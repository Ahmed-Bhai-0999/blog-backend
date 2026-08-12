FROM serversideup/php:8.2-cli

WORKDIR /var/www/html

USER root

# PHP extensions
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

# Project
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

# Clear Laravel caches
RUN php artisan config:clear \
    && php artisan route:clear \
    && php artisan view:clear

# EXPOSE 8080

# Start Laravel
# CMD ["sh", "-c", "php artisan serve --host=0.0.0.0 --port=${PORT}"]
# Pehle wali line hata do:
ENV PORT=8080
EXPOSE 8080

# Aur CMD yeh rakho:
CMD sh -c "php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT:-8080}"
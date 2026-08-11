# =========================
# Frontend Build
# =========================
FROM node:22-alpine AS frontend

WORKDIR /app

COPY package*.json ./

RUN npm ci

COPY resources ./resources
COPY vite.config.js ./

RUN npm run build


# =========================
# Laravel Backend
# =========================
FROM dunglas/frankenphp:php8.2-bookworm

WORKDIR /app

# PHP extensions
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

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# IMPORTANT:
# Copy artisan BEFORE composer install
COPY composer.json composer.lock artisan ./

# Install PHP dependencies
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction

# Copy Laravel application
COPY . .

# Copy Vite production build
COPY --from=frontend /app/public/build ./public/build

# Laravel writable directories
RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Railway provides PORT
ENV SERVER_NAME=:${PORT}

EXPOSE 8080

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
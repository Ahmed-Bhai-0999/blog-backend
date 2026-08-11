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

# Composer files
COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction

# Laravel application
COPY . .

# Copy Vite build
COPY --from=frontend /app/public/build ./public/build

# Laravel storage permissions
RUN mkdir -p \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache \
    storage/logs \
    bootstrap/cache

RUN chmod -R 775 storage bootstrap/cache

# Laravel optimization
RUN php artisan optimize:clear

EXPOSE 8000

CMD php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
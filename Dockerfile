# Stage 1: Node build
FROM node:20-alpine AS node-builder

WORKDIR /app

COPY package.json package-lock.json ./

RUN npm ci

COPY . .

RUN npm run build


# Stage 2: Laravel
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

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction

COPY . .

# Copy Vite production build
COPY --from=node-builder /app/public/build ./public/build

RUN mkdir -p \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache \
    storage/logs \
    bootstrap/cache

RUN chmod -R 775 storage bootstrap/cache

RUN php artisan config:cache

RUN php artisan route:cache

RUN php artisan view:cache
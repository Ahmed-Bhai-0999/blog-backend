FROM dunglas/frankenphp:php8.2-bookworm

# System packages required by Composer
RUN apt-get update && apt-get install -y \
    unzip \
    zip \
    libzip-dev \
    && rm -rf /var/lib/apt/lists/*

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

WORKDIR /app

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Composer dependencies
COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-scripts

# Copy Laravel project
COPY . .

# Laravel directories
RUN mkdir -p \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache \
    storage/logs \
    bootstrap/cache

RUN chmod -R 775 storage bootstrap/cache

# Laravel public directory
ENV SERVER_ROOT=/app/public

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
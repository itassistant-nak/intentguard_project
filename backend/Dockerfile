# syntax=docker/dockerfile:1

# -----------------------------------------------------------------------------
# Stage 1: Dependency installation
# -----------------------------------------------------------------------------
FROM composer:2 AS vendor
WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

# -----------------------------------------------------------------------------
# Stage 2: Production Runtime
# -----------------------------------------------------------------------------
FROM php:8.4-cli-alpine AS runtime

# Install system dependencies & PHP extensions required by Laravel & IntentGuard
RUN apk add --no-cache \
    curl \
    git \
    libzip-dev \
    netcat-openbsd \
    zip \
    && docker-php-ext-install -j$(nproc) \
    bcmath \
    opcache \
    pcntl \
    pdo_mysql \
    zip

# Production OPcache configuration
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.enable_cli=1'; \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=16'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.revalidate_freq=0'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'opcache.save_comments=1'; \
} > /usr/local/etc/php/conf.d/opcache-recommended.ini

WORKDIR /var/www/html

# Copy vendor from builder stage
COPY --from=vendor /app/vendor /var/www/html/vendor

# Copy application code
COPY . /var/www/html

# Run post-autoload scripts
RUN php artisan package:discover --ansi 2>/dev/null || true

# Prepare storage and bootstrap cache directories
RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chmod -R 777 storage bootstrap/cache

EXPOSE 8000

ENTRYPOINT ["/var/www/html/docker/entrypoint.sh"]

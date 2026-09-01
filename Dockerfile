# ---- Stage 1: install PHP dependencies with Composer ----
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --no-autoloader \
    --ignore-platform-reqs

COPY . .
RUN composer dump-autoload --optimize --no-dev

# ---- Stage 2: the actual runtime image ----
FROM php:8.3-cli-alpine

RUN apk add --no-cache libpng-dev libzip-dev icu-dev postgresql-dev \
    && docker-php-ext-install pdo_mysql pdo_pgsql gd zip bcmath intl

WORKDIR /app
COPY --from=vendor /app /app

RUN chmod -R 775 storage bootstrap/cache

EXPOSE 8080

CMD php artisan config:cache \
    && php artisan route:cache \
    && php artisan migrate --force \
    && php artisan serve --host=0.0.0.0 --port=${PORT:-8080}

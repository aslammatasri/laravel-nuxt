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
FROM php:8.4-cli-alpine

RUN apk add --no-cache libpng-dev libzip-dev icu-dev \
&& docker-php-ext-install pdo_mysql gd zip bcmath intl

# Allow several product images per request (each image is still capped at 2 MB by validation)
RUN printf 'upload_max_filesize=2M\npost_max_size=20M\nmax_file_uploads=20\n' > "$PHP_INI_DIR/conf.d/uploads.ini"

WORKDIR /app
COPY --from=vendor /app /app

RUN chmod -R 775 storage bootstrap/cache

EXPOSE 8080

CMD php artisan config:cache \
&& php artisan route:cache \
&& php artisan migrate --force \
&& php artisan storage:link --force \
&& php artisan serve --host=0.0.0.0 --port=${PORT:-8080}

# --- Dependencias de Composer (producción, sin dev) ---
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# --- Dependencias de Composer (desarrollo: PHPUnit, Pint, Pail...) ---
FROM composer:2 AS vendor-dev
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader --prefer-dist --no-interaction

# --- Base común: PHP-FPM con extensiones y entrypoint ---
FROM php:8.4-fpm-alpine AS base

COPY --from=ghcr.io/mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_pgsql pgsql opcache bcmath

COPY --from=vendor /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]

# --- Desarrollo ---
FROM base AS dev
RUN install-php-extensions pcntl
COPY --from=vendor-dev /app/vendor ./vendor
COPY . .
RUN composer dump-autoload --no-interaction \
    && chown -R www-data:www-data storage bootstrap/cache

# --- Producción (debe ser la ÚLTIMA etapa: es la que se construye por defecto) ---
FROM base AS production
COPY --from=vendor /app/vendor ./vendor
COPY . .
RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && chown -R www-data:www-data storage bootstrap/cache

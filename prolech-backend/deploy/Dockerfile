# Imagen de producción de ProLech.
#   target app: PHP-FPM 8.5 con el código, dependencias y assets ya compilados.
#   target web: Caddy (HTTPS automático) con los archivos públicos de esta misma versión.

FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json vite.config.js ./
COPY resources ./resources
RUN npm install --no-audit --no-fund && npm run build

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --ignore-platform-reqs

FROM php:8.5-fpm-alpine AS app
# mariadb-client trae mysqldump para los respaldos; connector-c, la autenticación de MySQL 8
RUN apk add --no-cache icu-dev libzip-dev libpng-dev libjpeg-turbo-dev freetype-dev mariadb-client mariadb-connector-c \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install pdo_mysql intl zip gd bcmath opcache
COPY deploy/php.ini /usr/local/etc/php/conf.d/prolech.ini
WORKDIR /var/www/html
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build
# --no-scripts saltó el descubrimiento de paquetes: se hace aquí, ya sin los de desarrollo
RUN rm -rf .env tests bootstrap/cache/*.php \
 && php artisan package:discover --ansi \
 && php artisan storage:link \
 && chown -R www-data:www-data storage bootstrap/cache
COPY deploy/arranque.sh /usr/local/bin/arranque
RUN chmod +x /usr/local/bin/arranque
CMD ["arranque"]

# Cada "docker compose up --build" publica también los CSS/JS/imágenes nuevos
FROM caddy:2-alpine AS web
COPY --from=app /var/www/html/public /var/www/html/public
COPY deploy/Caddyfile /etc/caddy/Caddyfile

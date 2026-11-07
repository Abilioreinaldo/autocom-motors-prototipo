# syntax=docker/dockerfile:1
# ---------- 1) Assets (Vite + Tailwind) ----------
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js ./
COPY resources ./resources
COPY app ./app
RUN npm run build

# ---------- 2) Dependências PHP ----------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --no-scripts --no-autoloader
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev

# ---------- 3) Runtime PHP-FPM 8.3 ----------
FROM php:8.3-fpm-alpine AS app
RUN apk add --no-cache bash curl icu-dev libzip-dev libpng-dev libjpeg-turbo-dev libwebp-dev freetype-dev oniguruma-dev mysql-client tzdata \
 && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
 && docker-php-ext-install -j"$(nproc)" pdo_mysql bcmath intl zip gd opcache pcntl exif \
 && apk del --no-cache freetype-dev libpng-dev libjpeg-turbo-dev libwebp-dev \
 && apk add --no-cache freetype libpng libjpeg-turbo libwebp \
 && cp /usr/share/zoneinfo/America/Sao_Paulo /etc/localtime && echo "America/Sao_Paulo" > /etc/timezone

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-autocom.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-autocom.conf

WORKDIR /var/www/html
COPY --from=vendor /app /var/www/html
COPY --from=assets /app/public/build /var/www/html/public/build
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint \
 && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
 && php artisan filament:assets \
 && chown -R www-data:www-data storage bootstrap/cache public

USER www-data
EXPOSE 9000
ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]

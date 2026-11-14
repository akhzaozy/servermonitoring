# syntax=docker/dockerfile:1

# ---- Stage 1: dependensi PHP (tanpa dev) ----
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --no-dev --no-scripts --ignore-platform-reqs

# ---- Stage 2: runtime (nginx + php-fpm, berjalan sebagai www-data di port 8080) ----
FROM serversideup/php:8.4-fpm-nginx

USER root
# openssh-client: dipakai terminal bila TERMINAL_SSH_HOST diisi (akses shell host).
RUN apt-get update \
    && apt-get install -y --no-install-recommends openssh-client procps \
    && rm -rf /var/lib/apt/lists/* \
    && mkdir -p /data \
    && chown www-data:www-data /data

COPY --chown=www-data:www-data --from=vendor /app /var/www/html
COPY docker/entrypoint.d/ /etc/entrypoint.d/
RUN chmod +x /etc/entrypoint.d/*.sh \
    && mkdir -p /var/www/html/storage/framework/{cache,sessions,views} /var/www/html/storage/logs /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

USER www-data

ENV AUTORUN_ENABLED=true \
    APP_ENV=production \
    APP_DEBUG=false \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/data/database.sqlite \
    SESSION_DRIVER=database \
    CACHE_STORE=database \
    QUEUE_CONNECTION=sync \
    LOG_CHANNEL=stderr \
    PHP_OPCACHE_ENABLE=1

VOLUME ["/data"]
EXPOSE 8080

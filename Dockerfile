# syntax=docker/dockerfile:1.7

# ---------- Stage 1: Composer dependencies ----------
FROM php:8.3-cli-bookworm AS vendor

WORKDIR /app

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libwebp-dev \
        libzip-dev \
        zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" gd zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --no-scripts \
        --prefer-dist \
        --optimize-autoloader

# ---------- Stage 2: Front-end assets ----------
FROM node:20-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY resources ./resources
COPY public ./public
COPY vite.config.js ./
COPY --from=vendor /app/vendor/wireui/wireui/ts ./vendor/wireui/wireui/ts
RUN npm run build

# ---------- Stage 3: PHP-FPM runtime ----------
FROM php:8.3-fpm-bookworm AS runtime

ENV DEBIAN_FRONTEND=noninteractive \
    COMPOSER_ALLOW_SUPERUSER=1 \
    APP_HOME=/var/www/html

# System packages + PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        curl \
        ca-certificates \
        supervisor \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libwebp-dev \
        libonig-dev \
        libxml2-dev \
        libzip-dev \
        libicu-dev \
        libpq-dev \
        zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        opcache \
        pcntl \
        pdo_mysql \
        zip \
    && pecl channel-update pecl.php.net \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Composer binary (for occasional in-container use)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# PHP / FPM / opcache config
COPY docker/php/php.ini       /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/opcache.ini   /usr/local/etc/php/conf.d/zz-opcache.ini
COPY docker/php/www.conf      /usr/local/etc/php-fpm.d/zz-www.conf

# Supervisor (runs php-fpm + queue worker)
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/app.conf

WORKDIR ${APP_HOME}

# Application source — copy after extensions so dependency installs cache cleanly
COPY . ${APP_HOME}

# Drop in vendor + built assets from earlier stages
COPY --from=vendor /app/vendor ${APP_HOME}/vendor
COPY --from=assets /app/public/build ${APP_HOME}/public/build
COPY --from=assets /app/public/build /opt/app-public-build

# Re-run scripts now that the full app is present, then optimize the autoloader
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

# Permissions for Laravel writable paths
RUN chown -R www-data:www-data ${APP_HOME}/storage ${APP_HOME}/bootstrap/cache \
    && find ${APP_HOME}/storage -type d -exec chmod 775 {} \; \
    && find ${APP_HOME}/bootstrap/cache -type d -exec chmod 775 {} \;

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/app.conf"]

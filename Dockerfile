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
# Retry composer install up to 5x - GitHub/codeload can return transient
# HTTP 400s/timeouts on dist downloads (esp. on flaky networks). Succeeds on
# the first good attempt; fails the build (exit 1) only if all 5 fail.
RUN for i in 1 2 3 4 5; do \
        composer install \
            --no-dev \
            --no-interaction \
            --no-progress \
            --no-scripts \
            --prefer-dist \
            --optimize-autoloader \
        && exit 0; \
        echo "composer install attempt $i failed; retrying in 10s"; sleep 10; \
    done; \
    exit 1

# ---------- Stage 2: Front-end assets ----------
FROM node:20-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
# Retry npm ci too (registry/network hiccups).
RUN for i in 1 2 3 4 5; do npm ci && exit 0; echo "npm ci attempt $i failed; retry in 10s"; sleep 10; done; exit 1

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
        nginx \
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

# nginx vhost — nginx runs INSIDE this image (web role), reaching php-fpm
# over 127.0.0.1:9000. No separate nginx container/task needed on ECS.
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
RUN rm -f /etc/nginx/sites-enabled/default

# Supervisor — WEB role only: runs nginx + php-fpm together.
# worker/scheduler/migrate roles override the container command instead.
COPY docker/supervisor/web.conf /etc/supervisor/conf.d/app.conf

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
# Strip CR from the entrypoint + all docker configs. On Windows checkouts these
# files can have CRLF line endings, which break the shell shebang inside the
# Linux container (exit 127) and can upset nginx/php/supervisor parsing.
RUN sed -i 's/\r$//' \
        /usr/local/bin/entrypoint.sh \
        /usr/local/etc/php/conf.d/zz-app.ini \
        /usr/local/etc/php/conf.d/zz-opcache.ini \
        /usr/local/etc/php-fpm.d/zz-www.conf \
        /etc/nginx/conf.d/default.conf \
        /etc/supervisor/conf.d/app.conf \
    && chmod +x /usr/local/bin/entrypoint.sh

# Web role serves HTTP on 80 (nginx). The ALB target group points here.
EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
# Default = WEB role. Other ECS services override this command:
#   worker     -> php artisan queue:work --tries=3 --timeout=120 --sleep=3 --max-jobs=1000 --max-time=3600
#   scheduler  -> php artisan schedule:work
#   migrate    -> sh -c "php artisan migrate --force --no-interaction && php artisan lms:migrate --no-interaction"
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/app.conf"]

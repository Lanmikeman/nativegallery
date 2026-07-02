# NativeGallery — nginx + PHP 8.3-FPM (Debian bookworm, Sury PHP)
# Production форка: bare-metal Ubuntu+Nginx; образ — для Docker / Pterodactyl.

FROM debian:bookworm-slim

ENV DEBIAN_FRONTEND=noninteractive \
    NG_WEB_ROOT=/var/www/html \
    NG_AUTO_MIGRATE=1

RUN apt-get update -qq \
    && apt-get install -y -qq --no-install-recommends \
        nginx \
        supervisor \
        cron \
        curl \
        git \
        unzip \
        ffmpeg \
        ca-certificates \
        lsb-release \
        apt-transport-https \
        gnupg2 \
        default-mysql-client \
        mariadb-client \
    && curl -fsSL https://packages.sury.org/php/apt.gpg -o /usr/share/keyrings/deb.sury.org-php.gpg \
    && echo "deb [signed-by=/usr/share/keyrings/deb.sury.org-php.gpg] https://packages.sury.org/php/ bookworm main" \
        > /etc/apt/sources.list.d/php.list \
    && apt-get update -qq \
    && apt-get install -y -qq --no-install-recommends \
        php8.3-fpm \
        php8.3-cli \
        php8.3-mysql \
        php8.3-gd \
        php8.3-curl \
        php8.3-mbstring \
        php8.3-xml \
        php8.3-zip \
        php8.3-exif \
        php8.3-intl \
        php8.3-bcmath \
        php8.3-opcache \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php-nativegallery.ini /etc/php/8.3/fpm/conf.d/99-nativegallery.ini
COPY docker/php-nativegallery.ini /etc/php/8.3/cli/conf.d/99-nativegallery.ini
COPY docker/nginx.conf /etc/nginx/sites-available/default
COPY docker/supervisord.conf /etc/supervisor/conf.d/nativegallery.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY docker/start-pterodactyl.sh /usr/local/bin/start-pterodactyl.sh

RUN ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default \
    && rm -f /etc/nginx/sites-enabled/default.bak \
    && chmod +x /usr/local/bin/entrypoint.sh /usr/local/bin/start-pterodactyl.sh \
    && mkdir -p /var/www/html /var/log/nginx /run/php

WORKDIR /var/www/html

COPY composer.json composer.lock* ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts 2>/dev/null \
    || composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && mkdir -p uploads cdn/temp cdn/previews cdn/image cdn/video logs storage/locks \
    && chown -R www-data:www-data uploads cdn logs storage \
    && chmod -R 775 uploads cdn logs storage

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=3 \
    CMD curl -f http://127.0.0.1:8080/ || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]
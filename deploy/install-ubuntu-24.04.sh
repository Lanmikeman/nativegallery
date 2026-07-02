#!/usr/bin/env bash
# NativeGallery — automated install for Ubuntu Server 24.04 (recommended production stack)
# Stack: nginx + PHP 8.3 + MySQL 8 + Composer
# Debian: use deploy/install-debian-12.sh
#
# Usage:
#   sudo bash deploy/install-ubuntu-24.04.sh
#
# Defaults: NG_WEB_ROOT=/var/www/nativegallery  NG_DOMAIN=example.com
# Custom path example (optional): NG_WEB_ROOT=/mnt/win/nativegallery  NG_DOMAIN=your.domain
# See docs/paths.md
#
# Optional: NG_DOMAIN, NG_DB_NAME, NG_DB_USER, NG_DB_PASS, NG_WEB_ROOT

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=lib/install-common.sh
source "${SCRIPT_DIR}/lib/install-common.sh"

NG_DOMAIN="${NG_DOMAIN:-example.com}"
NG_DB_NAME="${NG_DB_NAME:-ngallery}"
NG_DB_USER="${NG_DB_USER:-ngallery}"
NG_DB_PASS="${NG_DB_PASS:-$(ng_random_pass)}"
NG_WEB_ROOT="${NG_WEB_ROOT:-/var/www/nativegallery}"
NG_SITE_TITLE="${NG_SITE_TITLE:-NativeGallery}"
NG_ADMIN_EMAIL="${NG_ADMIN_EMAIL:-admin@${NG_DOMAIN}}"
NG_WEB_USER="${NG_WEB_USER:-www-data}"
NG_STACK_LABEL="Ubuntu 24.04 + Nginx + PHP-FPM"

ng_require_root

echo "==> Updating system packages"
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get upgrade -y -qq

echo "==> Installing nginx, PHP 8.3, MySQL, Composer dependencies"
apt-get install -y -qq \
    nginx \
    mysql-server \
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
    composer \
    ffmpeg \
    unzip \
    git \
    curl

ng_configure_php_ini "/etc/php/8.3/fpm/php.ini"

ng_setup_mysql_db

ng_deploy_app
ng_composer_install
ng_create_writable_dirs
ng_import_migrations
ng_generate_config

echo "==> Configuring nginx"
sed "s|example.com|${NG_DOMAIN}|g; s|/var/www/nativegallery|${NG_WEB_ROOT}|g" \
    deploy/nginx/nativegallery.conf > /etc/nginx/sites-available/nativegallery
ln -sf /etc/nginx/sites-available/nativegallery /etc/nginx/sites-enabled/nativegallery
rm -f /etc/nginx/sites-enabled/default

nginx -t
systemctl enable --now nginx php8.3-fpm mysql
systemctl reload nginx php8.3-fpm

echo "==> Setting up cron for contests"
ng_setup_cron

ng_print_footer
echo " SSL hint: sudo apt install certbot python3-certbot-nginx && sudo certbot --nginx -d ${NG_DOMAIN}"
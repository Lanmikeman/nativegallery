#!/usr/bin/env bash
# NativeGallery — automated install for Ubuntu/Debian + Apache + PHP-FPM
# Alternative to install-ubuntu-24.04.sh (production uses Nginx).
#
# Usage:
#   sudo bash deploy/install-ubuntu-apache.sh
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
NG_STACK_LABEL="Ubuntu/Debian + Apache + PHP-FPM"

ng_require_root

echo "==> Updating system packages"
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get upgrade -y -qq

echo "==> Installing Apache, PHP 8.3, MySQL, Composer"
apt-get install -y -qq \
    apache2 \
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
    curl \
    libapache2-mod-fcgid

ng_configure_php_ini "/etc/php/8.3/fpm/php.ini"
ng_configure_php_ini "/etc/php/8.3/cli/php.ini"

ng_setup_mysql_db

ng_deploy_app
ng_composer_install
ng_create_writable_dirs
ng_import_migrations
ng_generate_config

echo "==> Configuring Apache"
sed "s|example.com|${NG_DOMAIN}|g; s|/var/www/nativegallery|${NG_WEB_ROOT}|g" \
    deploy/apache/nativegallery.conf > /etc/apache2/sites-available/nativegallery.conf

a2dissite 000-default.conf 2>/dev/null || true
a2ensite nativegallery.conf
a2enmod rewrite proxy proxy_fcgi setenvif headers

apachectl configtest
systemctl enable --now apache2 php8.3-fpm mysql
systemctl reload apache2 php8.3-fpm

echo "==> Setting up cron for contests"
ng_setup_cron

ng_print_footer
echo " SSL hint: sudo apt install certbot python3-certbot-apache && sudo certbot --apache -d ${NG_DOMAIN}"
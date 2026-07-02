#!/usr/bin/env bash
# NativeGallery — automated install for Debian 12/13 (bookworm/trixie)
# Stack: nginx + PHP 8.3 (Sury) + MariaDB + Composer
# Production форка: Ubuntu 24.04 + Nginx; Debian — поддерживаемая альтернатива.
#
# Usage:
#   sudo bash deploy/install-debian-12.sh
#
# Optional: NG_DOMAIN, NG_DB_NAME, NG_DB_USER, NG_DB_PASS, NG_WEB_ROOT

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=lib/install-common.sh
source "${SCRIPT_DIR}/lib/install-common.sh"
# shellcheck source=lib/debian-php83.sh
source "${SCRIPT_DIR}/lib/debian-php83.sh"

NG_DOMAIN="${NG_DOMAIN:-example.com}"
NG_DB_NAME="${NG_DB_NAME:-ngallery}"
NG_DB_USER="${NG_DB_USER:-ngallery}"
NG_DB_PASS="${NG_DB_PASS:-$(ng_random_pass)}"
NG_WEB_ROOT="${NG_WEB_ROOT:-/var/www/nativegallery}"
NG_SITE_TITLE="${NG_SITE_TITLE:-NativeGallery}"
NG_ADMIN_EMAIL="${NG_ADMIN_EMAIL:-admin@${NG_DOMAIN}}"
NG_WEB_USER="${NG_WEB_USER:-www-data}"
NG_STACK_LABEL="Debian + Nginx + PHP-FPM (Sury 8.3)"

ng_require_root
ng_debian_require

echo "==> Updating system packages"
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get upgrade -y -qq

ng_debian_enable_php83_repo

echo "==> Installing nginx, PHP 8.3, MariaDB, Composer"
mapfile -t NG_PHP_PKGS < <(ng_debian_php_packages)
apt-get install -y -qq \
    nginx \
    default-mysql-server \
    mariadb-client \
    "${NG_PHP_PKGS[@]}" \
    composer \
    ffmpeg \
    unzip \
    git \
    curl

ng_configure_php_ini "/etc/php/8.3/fpm/php.ini"

ng_debian_start_database
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
systemctl enable --now nginx php8.3-fpm
ng_debian_start_database
systemctl reload nginx php8.3-fpm

echo "==> Setting up cron for contests"
ng_setup_cron

ng_print_footer
echo " SSL hint: sudo apt install certbot python3-certbot-nginx && sudo certbot --nginx -d ${NG_DOMAIN}"
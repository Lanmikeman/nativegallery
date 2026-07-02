#!/usr/bin/env bash
# NativeGallery — Rocky/AlmaLinux/CentOS Stream 9 + Apache (httpd) + PHP-FPM
# Alternative to install-rocky-9.sh (nginx).
#
# Usage:
#   sudo bash deploy/install-rocky-9-apache.sh

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
NG_WEB_USER="${NG_WEB_USER:-apache}"
NG_STACK_LABEL="Rocky/AlmaLinux 9 + Apache + PHP-FPM"

ng_require_root

if ! command -v dnf >/dev/null 2>&1; then
    echo "This script requires dnf (Rocky/AlmaLinux/CentOS Stream 9)."
    exit 1
fi

echo "==> Installing EPEL and Remi (PHP 8.3)"
dnf install -y epel-release
dnf install -y https://rpms.remirepo.net/enterprise/remi-release-9.rpm
dnf module reset php -y
dnf module enable php:remi-8.3 -y

echo "==> Installing httpd, MariaDB, PHP, Composer"
dnf install -y \
    httpd mod_proxy_fcgi \
    mariadb-server \
    php-cli php-fpm php-mysqlnd php-gd php-curl php-mbstring \
    php-xml php-zip php-exif php-intl php-bcmath php-opcache \
    composer ffmpeg unzip git curl policycoreutils-python-utils

systemctl enable --now mariadb

ng_configure_php_ini "/etc/php.ini"
if [[ -f /etc/php-fpm.d/www.conf ]]; then
    sed -i "s/^user = .*/user = ${NG_WEB_USER}/" /etc/php-fpm.d/www.conf
    sed -i "s/^group = .*/group = ${NG_WEB_USER}/" /etc/php-fpm.d/www.conf
fi

ng_setup_mysql_db

ng_deploy_app
ng_composer_install
ng_create_writable_dirs
ng_import_migrations
ng_generate_config

echo "==> Configuring Apache (httpd)"
sed "s|example.com|${NG_DOMAIN}|g; s|/var/www/nativegallery|${NG_WEB_ROOT}|g" \
    deploy/apache/nativegallery-rocky.conf > /etc/httpd/conf.d/nativegallery.conf

if command -v setsebool >/dev/null 2>&1; then
    echo "==> SELinux"
    setsebool -P httpd_can_network_connect 1 2>/dev/null || true
    chcon -R -t httpd_sys_rw_content_t \
        "${NG_WEB_ROOT}/uploads" "${NG_WEB_ROOT}/cdn" \
        "${NG_WEB_ROOT}/logs" "${NG_WEB_ROOT}/storage" 2>/dev/null || true
fi

apachectl configtest
systemctl enable --now httpd php-fpm mariadb
systemctl reload httpd php-fpm

echo "==> Setting up cron for contests"
ng_setup_cron

ng_print_footer
echo " SSL hint: sudo dnf install certbot python3-certbot-apache && sudo certbot --apache -d ${NG_DOMAIN}"
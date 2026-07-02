#!/usr/bin/env bash
# NativeGallery — automated install for Ubuntu Server 24.04
# Stack: nginx + PHP 8.3 + MySQL 8 + Composer
#
# Usage:
#   sudo bash deploy/install-ubuntu-24.04.sh
#
# Optional environment variables:
#   NG_DOMAIN=example.com
#   NG_DB_NAME=ngallery
#   NG_DB_USER=ngallery
#   NG_DB_PASS=secret
#   NG_WEB_ROOT=/var/www/nativegallery

set -euo pipefail

NG_DOMAIN="${NG_DOMAIN:-example.com}"
NG_DB_NAME="${NG_DB_NAME:-ngallery}"
NG_DB_USER="${NG_DB_USER:-ngallery}"
NG_DB_PASS="${NG_DB_PASS:-$(openssl rand -base64 24)}"
NG_WEB_ROOT="${NG_WEB_ROOT:-/var/www/nativegallery}"
NG_SITE_TITLE="${NG_SITE_TITLE:-NativeGallery}"
NG_ADMIN_EMAIL="${NG_ADMIN_EMAIL:-admin@${NG_DOMAIN}}"

if [[ $EUID -ne 0 ]]; then
    echo "Run as root: sudo bash $0"
    exit 1
fi

echo "==> Updating system packages"
apt-get update -qq
DEBIAN_FRONTEND=noninteractive apt-get upgrade -y -qq

echo "==> Installing nginx, PHP 8.3, MySQL, Composer dependencies"
DEBIAN_FRONTEND=noninteractive apt-get install -y -qq \
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

echo "==> Configuring PHP for NativeGallery"
PHP_INI="/etc/php/8.3/fpm/php.ini"
sed -i 's/^upload_max_filesize.*/upload_max_filesize = 128M/' "$PHP_INI"
sed -i 's/^post_max_size.*/post_max_size = 128M/' "$PHP_INI"
sed -i 's/^memory_limit.*/memory_limit = 512M/' "$PHP_INI"
sed -i 's/^max_execution_time.*/max_execution_time = 300/' "$PHP_INI"
sed -i 's/^;*date.timezone.*/date.timezone = UTC/' "$PHP_INI"

echo "==> Setting up MySQL database"
mysql -e "CREATE DATABASE IF NOT EXISTS \`${NG_DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '${NG_DB_USER}'@'localhost' IDENTIFIED BY '${NG_DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON \`${NG_DB_NAME}\`.* TO '${NG_DB_USER}'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

echo "==> Deploying application to ${NG_WEB_ROOT}"
if [[ ! -d "${NG_WEB_ROOT}/.git" ]]; then
    if [[ -d "${NG_WEB_ROOT}" ]]; then
        rm -rf "${NG_WEB_ROOT}"
    fi
    git clone https://github.com/Lanmikeman/nativegallery.git "${NG_WEB_ROOT}"
else
    cd "${NG_WEB_ROOT}"
    git pull --ff-only
fi

cd "${NG_WEB_ROOT}"

echo "==> Installing Composer dependencies"
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Creating writable directories"
mkdir -p uploads cdn/temp cdn/previews cdn/image cdn/video logs lock
chown -R www-data:www-data uploads cdn logs lock
chmod -R 775 uploads cdn logs lock

echo "==> Importing SQL schema"
for sql_file in sqlcore/base.sql sqlcore/sql_0001.sql sqlcore/sql_0002.sql sqlcore/sql_0003.sql sqlcore/sql_0004.sql sqlcore/sql_0005.sql; do
    if [[ -f "$sql_file" ]]; then
        mysql "${NG_DB_NAME}" < "$sql_file"
        echo "    imported: $sql_file"
    fi
done

echo "==> Generating ngallery.yaml"
if [[ ! -f ngallery.yaml ]]; then
    ENCRYPTION_KEY=$(openssl rand -hex 32)
    cat > ngallery.yaml <<EOF
ngallery:
  root:
    title: "${NG_SITE_TITLE}"
    adminemail: "${NG_ADMIN_EMAIL}"
    showtitle: false
    logo: "/static/img/logosmall.png"
    description: ""
    keywords: ""
    maintenance: false
    debug: false
    alloweddomains: ["${NG_DOMAIN}"]
    botkey: ''
    logslocation: '/logs'
    encryptionkey: '${ENCRYPTION_KEY}'
    footerslogan: 'Powered by NativeGallery'
    access:
      type: 'allow'
      countries: ''
    cloudflare-caching: false
    email:
      credentials:
        host: ''
        username: ''
        password: ''
        port: 465
      from:
        address: '${NG_ADMIN_EMAIL}'
    db:
      name: '${NG_DB_NAME}'
      host: '127.0.0.1'
      login: '${NG_DB_USER}'
      password: '${NG_DB_PASS}'
    websockets:
      messages: ""
    storage:
      type: 'server'
      s3:
        domains:
          public: ''
          gateway: ''
        credentials:
          key: ''
          secret: ''
          region: 'auto'
          version: 'latest'
          bucket: ''
    img:
      proxy: true
      percent: 50
    registration:
      emailverify: false
      prohibited_usernames: ''
      access:
        public: true
    video:
      upload:
        allow: true
    photo:
      uploadindex:
        enabled: false
        default: 5
        acceptvalue: 1
        declinevalue: 2
      upload:
        allow: true
        premoderation: true
        defaultindex: 5.0
        allowgif: true
    comments:
      premoderation: false
    contests:
      enabled: true
      autonew:
        enabled: true
        times:
          pretendsopen: 'now'
          pretendsclose: '2d'
          open: 'now'
          close: '2d'
EOF
    chown www-data:www-data ngallery.yaml
    chmod 640 ngallery.yaml
fi

echo "==> Configuring nginx"
sed "s|example.com|${NG_DOMAIN}|g; s|/var/www/nativegallery|${NG_WEB_ROOT}|g" \
    deploy/nginx/nativegallery.conf > /etc/nginx/sites-available/nativegallery
ln -sf /etc/nginx/sites-available/nativegallery /etc/nginx/sites-enabled/nativegallery
rm -f /etc/nginx/sites-enabled/default

nginx -t
systemctl enable --now nginx php8.3-fpm mysql
systemctl reload nginx php8.3-fpm

echo "==> Setting up cron for contests"
CRON_LINE="*/5 * * * * www-data php ${NG_WEB_ROOT}/app/Controllers/Exec/Tasks/ExecContests.php >> ${NG_WEB_ROOT}/logs/cron.log 2>&1"
CRON_FILE="/etc/cron.d/nativegallery"
echo "$CRON_LINE" > "$CRON_FILE"
chmod 644 "$CRON_FILE"

echo ""
echo "============================================"
echo " NativeGallery installed successfully!"
echo "============================================"
echo " URL:       http://${NG_DOMAIN}"
echo " Web root:  ${NG_WEB_ROOT}"
echo " Database:  ${NG_DB_NAME}"
echo " DB user:   ${NG_DB_USER}"
echo " DB pass:   ${NG_DB_PASS}"
echo ""
echo " Next steps:"
echo "  1. Point DNS A-record for ${NG_DOMAIN} to this server"
echo "  2. Install SSL: sudo apt install certbot python3-certbot-nginx && sudo certbot --nginx -d ${NG_DOMAIN}"
echo "  3. Register the first admin account at http://${NG_DOMAIN}/register"
echo "============================================"
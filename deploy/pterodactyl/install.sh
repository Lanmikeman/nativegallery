#!/bin/bash
# Pterodactyl installation script (reference — embedded in egg-nativegallery.json)
# Runs in install container; server files land in /mnt/server

set -e

NG_REPO="${NG_REPO:-https://github.com/Lanmikeman/nativegallery.git}"
NG_BRANCH="${NG_BRANCH:-main}"

cd /mnt/server

if [[ ! -f index.php ]]; then
    echo "[NativeGallery] Cloning repository..."
    git clone --depth 1 --branch "${NG_BRANCH}" "${NG_REPO}" /tmp/nativegallery-src
    shopt -s dotglob
    mv /tmp/nativegallery-src/* /mnt/server/
    rm -rf /tmp/nativegallery-src
fi

echo "[NativeGallery] Composer install..."
export COMPOSER_ALLOW_SUPERUSER=1
cd /mnt/server
composer install --no-dev --optimize-autoloader --no-interaction

echo "[NativeGallery] Writable directories..."
mkdir -p uploads cdn/temp cdn/previews cdn/image cdn/video logs storage/locks
chmod -R 775 uploads cdn logs storage 2>/dev/null || true

echo "[NativeGallery] Installation complete."
echo "Configure database variables in the panel, then start the server."
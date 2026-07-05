#!/usr/bin/env bash
# Shared install helpers for NativeGallery deploy scripts.
# Source from install-*.sh; do not execute directly.

ng_require_root() {
    if [[ ${EUID:-$(id -u)} -ne 0 ]]; then
        echo "Run as root: sudo bash $0"
        exit 1
    fi
}

ng_random_pass() {
    if command -v openssl >/dev/null 2>&1; then
        openssl rand -base64 24
    else
        tr -dc 'A-Za-z0-9' </dev/urandom | head -c 32
    fi
}

ng_deploy_app() {
    local web_root="${NG_WEB_ROOT:-/var/www/nativegallery}"
    local repo_url="${NG_REPO_URL:-https://github.com/Lanmikeman/nativegallery.git}"

    echo "==> Deploying application to ${web_root}"
    if [[ ! -d "${web_root}/.git" ]]; then
        if [[ -d "${web_root}" ]]; then
            rm -rf "${web_root}"
        fi
        git clone "${repo_url}" "${web_root}"
    else
        git -C "${web_root}" pull --ff-only
    fi

    cd "${web_root}"
}

ng_composer_install() {
    echo "==> Installing Composer dependencies"
    COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction
}

ng_create_writable_dirs() {
    local web_root="${NG_WEB_ROOT:-/var/www/nativegallery}"
    local web_user="${NG_WEB_USER:-www-data}"

    echo "==> Creating writable directories"
    mkdir -p "${web_root}/uploads" \
        "${web_root}/cdn/temp" "${web_root}/cdn/previews" \
        "${web_root}/cdn/image" "${web_root}/cdn/video" \
        "${web_root}/logs" "${web_root}/storage/locks"

    chown -R "${web_user}:${web_user}" \
        "${web_root}/uploads" "${web_root}/cdn" \
        "${web_root}/logs" "${web_root}/storage"
    chmod -R 775 \
        "${web_root}/uploads" "${web_root}/cdn" \
        "${web_root}/logs" "${web_root}/storage"
}

ng_import_migrations() {
    local db_name="${NG_DB_NAME:-ngallery}"
    local mysql_cmd="${NG_MYSQL_CMD:-mysql}"

    echo "==> Importing SQL schema"
    for sql_file in sqlcore/base.sql \
        sqlcore/sql_0001.sql sqlcore/sql_0002.sql sqlcore/sql_0003.sql \
        sqlcore/sql_0004.sql sqlcore/sql_0005.sql sqlcore/sql_0006.sql \
        sqlcore/sql_0007.sql sqlcore/sql_0008.sql sqlcore/sql_0009.sql; do
        if [[ -f "${sql_file}" ]]; then
            "${mysql_cmd}" "${db_name}" < "${sql_file}"
            echo "    imported: ${sql_file}"
        fi
    done
}

ng_generate_config() {
    local web_root="${NG_WEB_ROOT:-/var/www/nativegallery}"
    local web_user="${NG_WEB_USER:-www-data}"
    local domain="${NG_DOMAIN:-example.com}"
    local db_name="${NG_DB_NAME:-ngallery}"
    local db_user="${NG_DB_USER:-ngallery}"
    local db_pass="${NG_DB_PASS:-}"
    local site_title="${NG_SITE_TITLE:-NativeGallery}"
    local admin_email="${NG_ADMIN_EMAIL:-admin@${domain}}"

    echo "==> Generating ngallery.yaml"
    if [[ -f "${web_root}/ngallery.yaml" ]]; then
        echo "    ngallery.yaml already exists, skipping"
        return 0
    fi

    local encryption_key
    encryption_key=$(openssl rand -hex 32)

    cat > "${web_root}/ngallery.yaml" <<EOF
ngallery:
  root:
    title: "${site_title}"
    adminemail: "${admin_email}"
    showtitle: false
    logo: "/static/img/logosmall.png"
    description: ""
    keywords: ""
    maintenance: false
    debug: false
    alloweddomains: ["${domain}"]
    botkey: ''
    logslocation: '/logs'
    encryptionkey: '${encryption_key}'
    footerslogan: 'Powered by NativeGallery'
    timezone: 'Europe/Moscow'
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
        address: '${admin_email}'
    db:
      name: '${db_name}'
      host: '127.0.0.1'
      login: '${db_user}'
      password: '${db_pass}'
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

    chown "${web_user}:${web_user}" "${web_root}/ngallery.yaml"
    chmod 640 "${web_root}/ngallery.yaml"
}

ng_configure_php_ini() {
    local php_ini="${1:-}"
    if [[ -z "${php_ini}" || ! -f "${php_ini}" ]]; then
        echo "    PHP ini not found: ${php_ini:-<empty>}, skipping tuning"
        return 0
    fi

    echo "==> Configuring PHP (${php_ini})"
    sed -i 's/^upload_max_filesize.*/upload_max_filesize = 128M/' "${php_ini}"
    sed -i 's/^post_max_size.*/post_max_size = 128M/' "${php_ini}"
    sed -i 's/^memory_limit.*/memory_limit = 512M/' "${php_ini}"
    sed -i 's/^max_execution_time.*/max_execution_time = 300/' "${php_ini}"
    sed -i 's/^;*date.timezone.*/date.timezone = UTC/' "${php_ini}"
}

ng_setup_mysql_db() {
    local db_name="${NG_DB_NAME:-ngallery}"
    local db_user="${NG_DB_USER:-ngallery}"
    local db_pass="${NG_DB_PASS:-}"
    local mysql_cmd="${NG_MYSQL_CMD:-mysql}"

    echo "==> Setting up MySQL database"
    "${mysql_cmd}" -e "CREATE DATABASE IF NOT EXISTS \`${db_name}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    "${mysql_cmd}" -e "CREATE USER IF NOT EXISTS '${db_user}'@'localhost' IDENTIFIED BY '${db_pass}';"
    "${mysql_cmd}" -e "GRANT ALL PRIVILEGES ON \`${db_name}\`.* TO '${db_user}'@'localhost';"
    "${mysql_cmd}" -e "FLUSH PRIVILEGES;"
}

ng_setup_cron() {
    local web_root="${NG_WEB_ROOT:-/var/www/nativegallery}"
    NG_WEB_ROOT="${web_root}" NG_WEB_USER="${NG_WEB_USER:-www-data}" bash "${web_root}/deploy/setup-cron.sh"
}

ng_print_footer() {
    local domain="${NG_DOMAIN:-example.com}"
    local web_root="${NG_WEB_ROOT:-/var/www/nativegallery}"
    local db_name="${NG_DB_NAME:-ngallery}"
    local db_user="${NG_DB_USER:-ngallery}"
    local db_pass="${NG_DB_PASS:-}"
    local stack="${NG_STACK_LABEL:-nginx}"

    echo ""
    echo "============================================"
    echo " NativeGallery installed successfully!"
    echo "============================================"
    echo " Stack:     ${stack}"
    echo " URL:       http://${domain}"
    echo " Web root:  ${web_root}"
    echo " Database:  ${db_name}"
    echo " DB user:   ${db_user}"
    echo " DB pass:   ${db_pass}"
    echo ""
    echo " Next steps:"
    echo "  1. Point DNS A-record for ${domain} to this server"
    echo "  2. Install SSL (Let's Encrypt)"
    echo "  3. Register the first admin at http://${domain}/register"
    echo "  4. Grant admin: UPDATE users SET admin = 1 WHERE username = '...';"
    echo "============================================"
}
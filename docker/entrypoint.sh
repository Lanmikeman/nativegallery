#!/usr/bin/env bash
set -euo pipefail

NG_WEB_ROOT="${NG_WEB_ROOT:-/var/www/html}"
NG_DB_HOST="${NG_DB_HOST:-db}"
NG_DB_NAME="${NG_DB_NAME:-ngallery}"
NG_DB_USER="${NG_DB_USER:-ngallery}"
NG_DB_PASS="${NG_DB_PASS:-ngallery}"
NG_DOMAIN="${NG_DOMAIN:-localhost}"
NG_SITE_TITLE="${NG_SITE_TITLE:-NativeGallery}"
NG_ADMIN_EMAIL="${NG_ADMIN_EMAIL:-admin@localhost}"
NG_AUTO_MIGRATE="${NG_AUTO_MIGRATE:-1}"
NG_WEB_USER="${NG_WEB_USER:-www-data}"

cd "${NG_WEB_ROOT}"

mkdir -p uploads cdn/temp cdn/previews cdn/image cdn/video logs storage/locks
chown -R "${NG_WEB_USER}:${NG_WEB_USER}" uploads cdn logs storage 2>/dev/null || true
chmod -R 775 uploads cdn logs storage 2>/dev/null || true

generate_config() {
    local key="${NG_ENCRYPTION_KEY:-}"
    if [[ -z "${key}" ]]; then
        key=$(openssl rand -hex 32)
    fi

    cat > "${NG_WEB_ROOT}/ngallery.yaml" <<EOF
ngallery:
  root:
    title: "${NG_SITE_TITLE}"
    adminemail: "${NG_ADMIN_EMAIL}"
    showtitle: false
    logo: "/static/img/logosmall.png"
    description: ""
    keywords: ""
    maintenance: false
    debug: ${NG_DEBUG:-false}
    alloweddomains: ["${NG_DOMAIN}"]
    botkey: ''
    logslocation: '/logs'
    encryptionkey: '${key}'
    footerslogan: 'Powered by NativeGallery'
    timezone: '${NG_TIMEZONE:-Europe/Moscow}'
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
      host: '${NG_DB_HOST}'
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

    chown "${NG_WEB_USER}:${NG_WEB_USER}" "${NG_WEB_ROOT}/ngallery.yaml" 2>/dev/null || true
    chmod 640 "${NG_WEB_ROOT}/ngallery.yaml"
}

wait_for_db() {
    local tries=60
    echo "==> Waiting for database ${NG_DB_HOST}..."
    while (( tries > 0 )); do
        if mysql -h "${NG_DB_HOST}" -u "${NG_DB_USER}" -p"${NG_DB_PASS}" -e "SELECT 1" "${NG_DB_NAME}" &>/dev/null; then
            echo "==> Database is ready"
            return 0
        fi
        tries=$((tries - 1))
        sleep 2
    done
    echo "ERROR: database not reachable at ${NG_DB_HOST}"
    return 1
}

run_migrations() {
    local marker="${NG_WEB_ROOT}/storage/.schema_version"
    if [[ -f "${marker}" && "${NG_FORCE_MIGRATE:-0}" != "1" ]]; then
        echo "==> Schema marker present (${marker}), skipping migrations"
        return 0
    fi

    echo "==> Importing SQL migrations"
    for sql_file in sqlcore/base.sql \
        sqlcore/sql_0001.sql sqlcore/sql_0002.sql sqlcore/sql_0003.sql \
        sqlcore/sql_0004.sql sqlcore/sql_0005.sql sqlcore/sql_0006.sql \
        sqlcore/sql_0007.sql sqlcore/sql_0008.sql sqlcore/sql_0009.sql; do
        if [[ -f "${NG_WEB_ROOT}/${sql_file}" ]]; then
            mysql -h "${NG_DB_HOST}" -u "${NG_DB_USER}" -p"${NG_DB_PASS}" "${NG_DB_NAME}" \
                < "${NG_WEB_ROOT}/${sql_file}"
            echo "    imported: ${sql_file}"
        fi
    done
    echo "9" > "${marker}"
    chown "${NG_WEB_USER}:${NG_WEB_USER}" "${marker}" 2>/dev/null || true
}

setup_cron() {
    local cron_file="/etc/cron.d/nativegallery"
    cat > "${cron_file}" <<EOF
*/5 * * * * ${NG_WEB_USER} php ${NG_WEB_ROOT}/app/Controllers/Exec/Tasks/ExecContests.php >> ${NG_WEB_ROOT}/logs/cron.log 2>&1
EOF
    chmod 644 "${cron_file}"
}

if [[ "${NG_SKIP_CONFIG:-0}" != "1" ]]; then
    if [[ ! -f "${NG_WEB_ROOT}/ngallery.yaml" || "${NG_REGENERATE_CONFIG:-0}" == "1" ]]; then
        echo "==> Generating ngallery.yaml from environment"
        generate_config
    fi
fi

if [[ "${NG_AUTO_MIGRATE}" == "1" && -n "${NG_DB_HOST}" ]]; then
    wait_for_db
    run_migrations
fi

setup_cron

exec "$@"
#!/usr/bin/env bash
# Debian-specific helpers (PHP 8.3 via packages.sury.org).
# Source from install-debian-*.sh; do not execute directly.

ng_debian_require() {
    if [[ ! -f /etc/debian_version ]]; then
        echo "This script is for Debian. On Ubuntu use deploy/install-ubuntu-24.04.sh"
        exit 1
    fi
    if grep -qi '^ID=ubuntu' /etc/os-release 2>/dev/null; then
        echo "Detected Ubuntu — use deploy/install-ubuntu-24.04.sh or install-ubuntu-apache.sh"
        exit 1
    fi

    # shellcheck source=/dev/null
    source /etc/os-release
    case "${VERSION_ID:-}" in
        12|13)
            echo "==> Debian ${VERSION_ID} (${VERSION_CODENAME:-unknown})"
            ;;
        *)
            echo "Supported: Debian 12 (bookworm) and 13 (trixie). Found VERSION_ID=${VERSION_ID:-?}"
            echo "PHP 8.3 on older Debian requires manual setup (packages.sury.org)."
            exit 1
            ;;
    esac
}

ng_debian_enable_php83_repo() {
    if [[ -f /etc/apt/sources.list.d/php.list ]]; then
        echo "==> Sury PHP repository already configured"
        apt-get update -qq
        return 0
    fi

    echo "==> Adding Sury PHP 8.3 repository (packages.sury.org)"
    apt-get install -y -qq lsb-release ca-certificates curl apt-transport-https gnupg2

    curl -fsSL https://packages.sury.org/php/apt.gpg -o /usr/share/keyrings/deb.sury.org-php.gpg
    echo "deb [signed-by=/usr/share/keyrings/deb.sury.org-php.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" \
        > /etc/apt/sources.list.d/php.list

    apt-get update -qq
}

ng_debian_php_packages() {
    cat <<'EOF'
php8.3-fpm
php8.3-cli
php8.3-mysql
php8.3-gd
php8.3-curl
php8.3-mbstring
php8.3-xml
php8.3-zip
php8.3-exif
php8.3-intl
php8.3-bcmath
php8.3-opcache
EOF
}

ng_debian_start_database() {
    systemctl enable --now mariadb 2>/dev/null \
        || systemctl enable --now mysql 2>/dev/null \
        || true
}
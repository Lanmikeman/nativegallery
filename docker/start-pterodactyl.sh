#!/usr/bin/env bash
# Pterodactyl startup wrapper — panel sets SERVER_IP, SERVER_PORT, etc.
set -euo pipefail

export NG_WEB_ROOT="${NG_WEB_ROOT:-/home/container}"

if [[ -z "${NG_DOMAIN:-}" && -n "${SERVER_IP:-}" ]]; then
    export NG_DOMAIN="${SERVER_IP}"
fi

if [[ -f /usr/local/bin/entrypoint.sh ]]; then
    exec /usr/local/bin/entrypoint.sh /usr/bin/supervisord -n -c /etc/supervisor/supervisord.conf
fi

exec /usr/bin/supervisord -n -c /etc/supervisor/supervisord.conf
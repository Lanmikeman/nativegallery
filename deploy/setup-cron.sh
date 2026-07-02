#!/usr/bin/env bash
# Install or update cron job for photo contest automation.
#
# Usage:
#   sudo NG_WEB_ROOT=/mnt/win/nativegallery bash deploy/setup-cron.sh
#   sudo NG_WEB_ROOT=/var/www/nativegallery bash deploy/setup-cron.sh

set -euo pipefail

NG_WEB_ROOT="${NG_WEB_ROOT:-/var/www/nativegallery}"
CRON_FILE="/etc/cron.d/nativegallery"
LOG_DIR="${NG_WEB_ROOT}/logs"
TASK_SCRIPT="${NG_WEB_ROOT}/app/Controllers/Exec/Tasks/ExecContests.php"

if [[ $EUID -ne 0 ]]; then
    echo "Run as root: sudo NG_WEB_ROOT=${NG_WEB_ROOT} bash $0"
    exit 1
fi

if [[ ! -f "${NG_WEB_ROOT}/ngallery.yaml" ]]; then
    echo "ngallery.yaml not found in ${NG_WEB_ROOT}"
    exit 1
fi

if [[ ! -f "${TASK_SCRIPT}" ]]; then
    echo "ExecContests.php not found: ${TASK_SCRIPT}"
    exit 1
fi

mkdir -p "${LOG_DIR}"
chown www-data:www-data "${LOG_DIR}"

CRON_LINE="*/5 * * * * www-data php ${TASK_SCRIPT} >> ${LOG_DIR}/cron.log 2>&1"
echo "${CRON_LINE}" > "${CRON_FILE}"
chmod 644 "${CRON_FILE}"

MARKER_FILE="${NG_WEB_ROOT}/storage/cron-tasks.json"
mkdir -p "${NG_WEB_ROOT}/storage"
cat > "${MARKER_FILE}" <<EOF
{
    "ExecContests": {
        "handler": "/app/Controllers/Exec/Tasks/ExecContests.php",
        "source": "cron.d",
        "installed_at": $(date +%s)
    }
}
EOF
chown www-data:www-data "${MARKER_FILE}"
chmod 664 "${MARKER_FILE}"

echo "Cron installed:"
cat "${CRON_FILE}"
echo ""
echo "Test run:"
echo "  sudo -u www-data php ${TASK_SCRIPT}"
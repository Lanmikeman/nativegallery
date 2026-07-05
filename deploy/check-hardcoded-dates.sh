#!/usr/bin/env bash
# Quick scan for likely year/date hardcodes that should be dynamic.
# Run from repo root: bash deploy/check-hardcoded-dates.sh

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

echo "Scanning for suspicious hardcoded dates in views/app/static/js..."

PATTERN='Mar 1, 20[0-9]{2}|countdownDate = new Date|date=20[0-9]{2}-[0-9]{2}-[0-9]{2}|option value="20[0-9]{2}"'

if rg -n "$PATTERN" views app static/js --glob '!**/notie.js' 2>/dev/null; then
    echo ""
    echo "Found potential hardcoded dates above. Prefer Unix timestamps or date('Y')."
    exit 1
fi

echo "No obvious hardcoded date patterns found."
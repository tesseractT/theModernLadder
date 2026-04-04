#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
FLUTTER_APP_DIR="${ROOT_DIR}/apps/flutter_app"
FLUTTER_DEVICE="${1:-${FLUTTER_DEVICE:-macos}}"
API_HOST="${API_HOST:-127.0.0.1}"
API_PORT="${API_PORT:-8000}"
API_PID=""

cleanup() {
  if [[ -n "${API_PID}" ]] && kill -0 "${API_PID}" 2>/dev/null; then
    kill "${API_PID}" 2>/dev/null || true
    wait "${API_PID}" 2>/dev/null || true
  fi
}

trap cleanup EXIT INT TERM

if ! command -v php >/dev/null 2>&1; then
  echo "php is required to start the Laravel API." >&2
  exit 1
fi

if ! command -v flutter >/dev/null 2>&1; then
  echo "flutter is required to launch the Flutter app." >&2
  exit 1
fi

if [[ ! -f "${ROOT_DIR}/artisan" ]]; then
  echo "Could not find Laravel's artisan file in ${ROOT_DIR}." >&2
  exit 1
fi

if [[ ! -d "${FLUTTER_APP_DIR}" ]]; then
  echo "Could not find the Flutter app in ${FLUTTER_APP_DIR}." >&2
  exit 1
fi

if [[ ! -f "${ROOT_DIR}/vendor/autoload.php" ]]; then
  echo "Composer dependencies are missing. Run 'composer install' first." >&2
  exit 1
fi

if [[ ! -f "${FLUTTER_APP_DIR}/.dart_tool/package_config.json" ]]; then
  echo "Installing Flutter dependencies..."
  (
    cd "${FLUTTER_APP_DIR}"
    flutter pub get
  )
fi

echo "Starting Laravel API at http://${API_HOST}:${API_PORT}"
(
  cd "${ROOT_DIR}"
  php artisan serve --host="${API_HOST}" --port="${API_PORT}"
) &
API_PID=$!

echo "Launching Flutter on device '${FLUTTER_DEVICE}'"
cd "${FLUTTER_APP_DIR}"
flutter run -d "${FLUTTER_DEVICE}"

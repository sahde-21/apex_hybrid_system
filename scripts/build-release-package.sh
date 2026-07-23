#!/usr/bin/env bash
# Build a commercial distribution ZIP for SCF Enterprise Suite 1.0
# Does not include .env, vendor, node_modules, or database dumps.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
VERSION="${APP_VERSION:-1.0.0}"
NAME="scf-enterprise-suite-${VERSION}"
DIST="${ROOT}/dist"
STAGE="${DIST}/${NAME}"

rm -rf "${STAGE}"
mkdir -p "${STAGE}"

rsync -a \
  --exclude '.git' \
  --exclude '.env' \
  --exclude '.env.*' \
  --exclude '!.env.example' \
  --exclude 'node_modules' \
  --exclude 'vendor' \
  --exclude 'dist' \
  --exclude 'database/*.sqlite' \
  --exclude 'database/backups' \
  --exclude 'storage/logs/*' \
  --exclude 'storage/framework/cache/*' \
  --exclude 'storage/framework/sessions/*' \
  --exclude 'storage/framework/views/*' \
  --exclude '.phpunit.cache' \
  --exclude '.cursor' \
  --exclude '.vscode' \
  --exclude '.idea' \
  "${ROOT}/" "${STAGE}/"

# Ensure env example is present even if exclude patterns are aggressive
cp -f "${ROOT}/.env.example" "${STAGE}/.env.example"

# Keep empty storage tree
mkdir -p "${STAGE}/storage/logs" "${STAGE}/storage/framework/cache" \
  "${STAGE}/storage/framework/sessions" "${STAGE}/storage/framework/views" \
  "${STAGE}/storage/app/public" "${STAGE}/bootstrap/cache"
touch "${STAGE}/storage/logs/.gitignore" 2>/dev/null || true

cd "${DIST}"
rm -f "${NAME}.zip" "${NAME}.zip.sha256"
zip -qr "${NAME}.zip" "${NAME}"
if command -v shasum >/dev/null 2>&1; then
  shasum -a 256 "${NAME}.zip" > "${NAME}.zip.sha256"
elif command -v sha256sum >/dev/null 2>&1; then
  sha256sum "${NAME}.zip" > "${NAME}.zip.sha256"
fi

echo "Created ${DIST}/${NAME}.zip"
test -f "${DIST}/${NAME}.zip.sha256" && echo "Checksum ${DIST}/${NAME}.zip.sha256"

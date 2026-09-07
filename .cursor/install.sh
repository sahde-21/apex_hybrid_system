#!/usr/bin/env bash
#
# Cloud Agent / local development bootstrap for the SCF Enterprise Suite.
#
# Runs from the repository root. Safe to run repeatedly (idempotent):
# it only installs system packages that are missing, never overwrites an
# existing .env or APP_KEY, and uses idempotent migrate/seed commands.
#
# Uses SQLite for the development database so no external database service
# is required (see .env.example: "Local SQLite development").
set -euo pipefail

# --- 1. System dependencies: PHP 8.4 (required by the locked Symfony 8.1
#        packages) and Composer. Only installed when missing. ---
if ! command -v php >/dev/null 2>&1 \
  || ! php -r 'exit(version_compare(PHP_VERSION, "8.4", ">=") ? 0 : 1);' >/dev/null 2>&1; then
  sudo add-apt-repository -y ppa:ondrej/php
  sudo apt-get update -y
  sudo apt-get install -y --no-install-recommends \
    php8.4-cli php8.4-common php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip \
    php8.4-bcmath php8.4-intl php8.4-gd php8.4-sqlite3 php8.4-pgsql php8.4-mysql php8.4-redis
  sudo update-alternatives --set php /usr/bin/php8.4
fi

if ! command -v composer >/dev/null 2>&1; then
  curl -sS https://getcomposer.org/installer | sudo php -- \
    --install-dir=/usr/local/bin --filename=composer
fi

# --- 2. PHP dependencies ---
composer install --no-interaction --prefer-dist

# --- 3. Environment file + SQLite database (created only once) ---
if [ ! -f .env ]; then
  cp .env.example .env
  sed -i 's/^DB_CONNECTION=pgsql/DB_CONNECTION=sqlite/' .env
  sed -i 's/^DB_HOST=/# DB_HOST=/' .env
  sed -i 's/^DB_PORT=5432/# DB_PORT=5432/' .env
  sed -i 's/^DB_DATABASE=scf_erp/# DB_DATABASE=scf_erp/' .env
  sed -i 's/^DB_USERNAME=scf/# DB_USERNAME=scf/' .env
  sed -i 's/^DB_PASSWORD=secret/# DB_PASSWORD=secret/' .env
fi

grep -q '^APP_KEY=base64' .env || php artisan key:generate --force
touch database/database.sqlite

# --- 4. Schema + demo data ---
php artisan migrate --force

# Seed demo data only on a fresh (unseeded) database. The demo seeders are
# not all safe to re-run (e.g. AccountingSeeder creates a fiscal year with a
# unique date range), so we skip seeding once users already exist.
SEED_STATE="$(php artisan tinker --execute='echo \App\Models\User::query()->exists() ? "seeded" : "empty";' 2>/dev/null | tail -n1)"
if [ "$SEED_STATE" != "seeded" ]; then
  # The first db:seed on a brand-new database can fail once because
  # RolePermissionSeeder clears the Spatie permission cache before creating
  # permissions but not after, so syncPermissions() reads a stale cache. The
  # permissions are persisted before the failure, so a second run (fresh
  # process with a warm cache) succeeds. (See PR notes for the one-line fix.)
  php artisan db:seed --force || php artisan db:seed --force
fi

# --- 5. Frontend assets ---
npm install
npm run build

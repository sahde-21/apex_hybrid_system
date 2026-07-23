# Installation Guide — SCF Enterprise Suite 1.0

Customer-facing clean install for self-hosted production. Read `PRODUCTION_SERVER_REQUIREMENTS.md` first.

## 1. Prerequisites checklist

| Requirement | Minimum | Recommended |
|-------------|---------|-------------|
| OS | Ubuntu 22.04 LTS / Debian 12 / Rocky 9 | Ubuntu 24.04 LTS |
| CPU / RAM / Disk | 2 vCPU / 4 GB / 40 GB SSD | 4 vCPU / 8 GB / 80 GB+ |
| PHP | 8.3 + extensions below | **8.4** + OPcache |
| Composer | 2.x | 2.x |
| Node.js / npm | 20+ (build host or CI) | 20 LTS |
| Database | PostgreSQL 14+ or MySQL 8+ | **PostgreSQL 16** |
| Web server | Nginx or Apache | **Nginx** + TLS |
| Process manager | Supervisor or systemd | Supervisor (queue) |
| Cron | Available to app user | System crontab |

### PHP extensions

`bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `json`, `mbstring`, `openssl`, `pdo`, `tokenizer`, `xml`, plus `pdo_pgsql` or `pdo_mysql`.

**Do not use SQLite for production multi-user ERP.**

## 2. Obtain the release

```bash
# Example — use your licensed release artifact or private git remote
sudo mkdir -p /var/www/scf
sudo chown $USER:www-data /var/www/scf
cd /var/www/scf
git clone <your-release-remote> .
# or extract the Version 1.0 release tarball into /var/www/scf
```

## 3. Application dependencies

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

## 4. Configure `.env` (production)

Set at least:

```env
APP_NAME="Your Company ERP"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://erp.example.com
APP_TIMEZONE=Asia/Baghdad
APP_LOCALE=en

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=scf_erp
DB_USERNAME=scf
DB_PASSWORD=<strong-secret>

QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true

MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=noreply@example.com

LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=info

TRUSTED_PROXIES=*
SECURITY_HEADERS_ENABLED=true
ALLOW_DESTRUCTIVE_DB=false
```

Never commit `.env`. Backup `APP_KEY` offline.

## 5. Permissions and storage

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo find storage bootstrap/cache -type d -exec chmod 775 {} \;
sudo find storage bootstrap/cache -type f -exec chmod 664 {} \;
php artisan storage:link
```

## 6. Database and first administrator

```bash
php artisan migrate --force
php artisan db:seed --class=ProductionSeeder --force
php artisan scf:create-admin
```

**Never** run `DemoSeeder` in production.

## 7. Frontend assets

On the build host (or same server):

```bash
npm ci
npm run build
```

Confirm `public/build/manifest.json` exists.

## 8. Optimize

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

## 9. Web server, queue, scheduler

1. Configure Nginx — `docs/infrastructure/NGINX_EXAMPLE.md`
2. Configure Supervisor workers — `docs/infrastructure/SUPERVISOR_EXAMPLE.md`
3. Install cron:

```cron
* * * * * cd /var/www/scf && php artisan schedule:run >> /dev/null 2>&1
```

## 10. Validate

```bash
php artisan scf:deploy-check --production
php artisan scf:release-readiness
php artisan scf:health --detailed
```

Browser checks:

- `https://erp.example.com/health/ready` → 200
- `https://erp.example.com/api/v1/health` → 200
- Login with the admin from `scf:create-admin`

## 11. Rollback and recovery

See `DEPLOYMENT.md`, `ROLLBACK.md`, and `DISASTER_RECOVERY.md`.

## Common gaps (avoid)

| Mistake | Fix |
|---------|-----|
| Leaving `APP_DEBUG=true` | Set `false` and recache config |
| Using demo users | Use `ProductionSeeder` + `scf:create-admin` |
| HTTP only | Terminate TLS; set HTTPS `APP_URL` |
| No queue worker | Supervisor `queue:work` |
| No cron | `schedule:run` every minute |
| Empty `TRUSTED_PROXIES` behind Nginx | Set `TRUSTED_PROXIES=*` |
| Building caches before editing `.env` | Edit `.env` first, then `config:cache` |

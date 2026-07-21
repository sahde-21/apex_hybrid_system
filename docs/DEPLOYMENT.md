# Deployment Checklist

## Pre-deployment

1. Run `php artisan scf:deploy-check --production`
2. Run `php artisan scf:release-readiness`
3. Run `php artisan scf:migrations:inspect`
4. Run `php artisan scf:health --detailed`
5. Ensure `APP_DEBUG=false` in production
6. Ensure `APP_KEY` is set and backed up
7. Build frontend assets: `npm ci && npm run build`
8. Review `docs/SECURITY_CHECKLIST.md`

## Deployment sequence

Use the orchestration guide:

```bash
php artisan scf:deploy-plan
```

Manual sequence:

```bash
php artisan down --render="errors::503"
php artisan db:backup --label=pre-deploy
# deploy code manually — never auto git pull in tooling
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan scf:deploy-check --production
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan storage:link
php artisan queue:restart
php artisan scf:deploy-verify
php artisan up
```

## Post-deployment

1. Verify `GET /health/ready` returns 200
2. Verify `GET /api/v1/health` returns 200
3. Confirm scheduler cron is active (`scf:schedule:list`)
4. Confirm queue worker is running (`scf:queue-status`)
5. Run `php artisan db:backup --label=post-deploy`
6. Run `php artisan scf:release-info`

## First production administrator

```bash
php artisan db:seed --class=ProductionSeeder --force
php artisan scf:create-admin
```

## Backup operations

```bash
php artisan scf:backup:list
php artisan scf:backup:verify database_YYYYMMDD_HHMMSS.sqlite
php artisan scf:backup:restore database_YYYYMMDD_HHMMSS.sqlite
php artisan scf:backup:restore database_YYYYMMDD_HHMMSS.sqlite --execute --force
```

Restore requires maintenance mode and creates a pre-restore safety backup.

## Rollback

See `docs/ROLLBACK.md` and `docs/DISASTER_RECOVERY.md`.

## Maintenance mode

```bash
php artisan down --render="errors::503" --retry=60
php artisan up
```

Optional bypass (keep secret):

```bash
php artisan down --secret="your-secret-token"
# visit /your-secret-token during maintenance
```

## Local development (no Docker required)

```bash
php artisan serve
php artisan queue:work
php artisan schedule:work
```

SQLite, file cache, database queue, and log mail driver work out of the box.

## Optional Docker / Sail

Laravel Sail is available in `require-dev` only. It is optional and not required for production deployment.

## Cost control

**FREE / BUILT-IN / SELF-HOSTED:** Laravel commands, database queue, scheduler, local backups, local logs, native server deployment.

**OPTIONAL COSTS — NOT PURCHASED OR ENABLED:** domain, VPS, managed DB/Redis, paid CDN, paid backups, paid email/SMS, paid monitoring, paid scanners, paid registries.

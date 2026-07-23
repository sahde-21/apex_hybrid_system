# Upgrade Guide — SCF Enterprise Suite 1.0

## Version compatibility

| From | To | Path |
|------|----|------|
| Fresh install | 1.0.0 | `INSTALLATION_GUIDE.md` |
| 1.0.x patch | 1.0.y | This guide (minor/patch) |
| Pre-1.0 RC | 1.0.0 | Treat as new deploy + data migration project |

Always read `RELEASE_NOTES_1.0.md` / `CHANGELOG.md` for the target version.

## Pre-upgrade checklist

1. Schedule a maintenance window (expect downtime).  
2. Notify users.  
3. Verify disk space for backup + new release.  
4. Record current metadata: `php artisan scf:release-info`  
5. Confirm `APP_DEBUG=false` in production.

## Required backup

```bash
php artisan down --render="errors::503"
php artisan db:backup --label=pre-upgrade
php artisan scf:backup:verify {latest-backup-file}
# Also snapshot .env and storage/app if attachments matter
```

## Upgrade sequence (patch / minor within 1.0)

```bash
# 1. Deploy new release artifacts (code + public/build or rebuild assets)
composer install --no-dev --optimize-autoloader
npm ci && npm run build   # if assets not prebuilt in package

# 2. Inspect then migrate
php artisan scf:migrations:inspect
php artisan migrate --force

# 3. Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan queue:restart

# 4. Verify
php artisan scf:deploy-verify
php artisan scf:health --detailed
php artisan scf:release-info
php artisan up
```

## Configuration changes

- Diff `.env.example` against production `.env`; add new keys with safe defaults.  
- Rebuild config cache after any `.env` change.  
- Update `APP_VERSION` / `APP_RELEASE` / `APP_COMMIT_SHA` for the new build.

## Database migration strategy

- Prefer **forward-fix** migrations (`MIGRATION_SAFETY.md`).  
- Do **not** run `migrate:rollback` on financial systems unless DBA-approved.  
- If migrate fails: stay in maintenance; restore pre-upgrade backup (`BACKUP_OPERATIONS.md`).

## Rollback strategy

1. Code: redeploy previous release artifacts; rebuild caches; `queue:restart`.  
2. Data: restore pre-upgrade backup only if migrations partially applied or data corrupt.  
3. See `ROLLBACK.md` and `DISASTER_RECOVERY.md`.

## Downtime guidance

| Change type | Typical downtime |
|-------------|------------------|
| Patch (no heavy migrate) | 5–15 minutes |
| Schema migration on large tables | Maintenance window sized to table locks |
| Failed upgrade restore | RTO per DR plan (default ≤ 4 hours) |

## Post-upgrade

- Smoke: login, create draft document, queue job, health endpoints.  
- Confirm daily backup still scheduled.  
- Update customer runbook with new version string.

# Deployment Checklist

## Pre-deployment

1. Run `php artisan scf:validate-env`
2. Run `php artisan scf:health --detailed`
3. Ensure `APP_DEBUG=false` in production
4. Ensure `APP_KEY` is set
5. Run targeted tests for changed areas

## Deployment sequence

```bash
php artisan down --render="errors::503"
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan queue:restart
php artisan up
```

## Post-deployment

1. Verify `GET /health/ready` returns 200
2. Verify `GET /api/v1/health` returns 200
3. Confirm scheduler cron is active
4. Confirm queue worker is running
5. Run `php artisan db:backup --label=post-deploy`

## Rollback notes

- Restore previous release artifacts
- Restore database from latest backup in `database/backups/`
- Run `php artisan config:clear && php artisan cache:clear`
- Restart queue workers

## Maintenance mode

Laravel maintenance mode hides the application from users while migrations or releases run. Always bring the app back up with `php artisan up` after completion.

## Local development (no Docker required)

```bash
php artisan serve
php artisan queue:work
# separate terminal:
php artisan schedule:work
```

SQLite, file cache, database queue, and log mail driver work out of the box.

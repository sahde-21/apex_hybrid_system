# Maintenance Runbook — SCF Enterprise Suite 1.0

Operational procedures for production. Pair with `DEPLOYMENT.md` and `ROLLBACK.md`.

## Routine maintenance (weekly)

1. `php artisan scf:health --detailed`  
2. `php artisan scf:queue-status`  
3. `php artisan scf:backup:list` + verify latest  
4. Review `storage/logs` for ERROR spikes  
5. Confirm disk < 80%  
6. Confirm TLS expiry > 21 days  

## Deployment

Follow `php artisan scf:deploy-plan` and `DEPLOYMENT.md`.

## Rollback

Follow `ROLLBACK.md`. Prefer code rollback; avoid `migrate:rollback` on financial schema.

## Backup / restore

Follow `BACKUP_OPERATIONS.md`.

## Queue restart

```bash
php artisan queue:restart
sudo supervisorctl status scf-worker:*
php artisan scf:queue-status
```

## Scheduler restart / repair

```bash
crontab -l   # confirm schedule:run
php artisan scf:schedule:list
# run missed critical job manually if needed, e.g.:
php artisan db:backup --label=manual-catchup
```

## Emergency maintenance

```bash
php artisan down --render="errors::503" --retry=60 --secret="REDACTED"
# … repair …
php artisan scf:health --detailed
php artisan up
```

## System upgrade (minor patch)

1. Backup + maintenance mode  
2. Deploy release artifacts  
3. `composer install --no-dev --optimize-autoloader`  
4. `migrate --force` (after `scf:migrations:inspect`)  
5. Rebuild caches; `queue:restart`  
6. Verify health  

## Credential / admin recovery

```bash
php artisan scf:create-admin
```

Remove demo accounts before go-live (`scf:release-readiness`).

## Incident handling

`INCIDENT_RESPONSE.md`.

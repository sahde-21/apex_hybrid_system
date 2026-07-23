# Troubleshooting Guide — SCF Enterprise Suite 1.0

## Application will not boot

1. Confirm `.env` exists and `APP_KEY` is set.
2. Run `php artisan scf:validate-env`.
3. Check `storage/logs` for boot exceptions.
4. Ensure `bootstrap/cache` and `storage` are writable.

## Health check failures

```bash
php artisan scf:health --detailed
```

- **database** — verify DB credentials and connectivity
- **cache** — verify cache driver and writable storage
- **queue** — ensure jobs tables exist (`migrate`)
- **storage** — fix permissions; recreate `storage:link` if public files 404

## Release readiness fails in production

| Message | Action |
|---------|--------|
| Demo account default password | Remove demo users or change passwords; use `ProductionSeeder` + `scf:create-admin` only |
| APP_URL should use HTTPS | Set `APP_URL=https://...` behind TLS |
| Configuration / routes not cached | Run `config:cache` and `route:cache` after deploy |

## Queue backlog or failed jobs

```bash
php artisan scf:queue-status
php artisan queue:failed
php artisan queue:retry all
php artisan queue:restart
```

See `QUEUE_SCHEDULER.md`.

## Scheduler not running

Confirm cron: `* * * * * php artisan schedule:run`  
List tasks: `php artisan scf:schedule:list`

## Backup / restore

```bash
php artisan scf:backup:list
php artisan scf:backup:verify {file}
php artisan scf:backup:restore {file}          # dry-run
php artisan scf:backup:restore {file} --execute
```

Restore requires maintenance mode and creates a safety backup. See `DISASTER_RECOVERY.md`.

## 403 / permission errors

- Confirm user roles in Administration
- Review `PERMISSION_MATRIX_G3.md`
- Intelligence domains need specific permissions (not a global view grant)

## Locale / RTL issues

- Switch locale via `?lang=ar` / `ckb` / `en`
- Clear view cache after language file updates: `php artisan view:clear`

## Feature tests fail after caching

Run `php artisan optimize:clear` before Pest/PHPUnit. Do not leave production route cache active during local test runs.

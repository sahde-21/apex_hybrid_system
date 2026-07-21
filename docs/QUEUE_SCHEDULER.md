# Queue and Scheduler Operations

## Queue worker

Production (database queue):

```bash
php artisan queue:work database --sleep=3 --tries=3 --timeout=90
```

After deployment:

```bash
php artisan queue:restart
```

Status:

```bash
php artisan scf:queue-status
php artisan queue:failed
php artisan queue:retry all
php artisan queue:flush   # use with caution
```

## Scheduler

Production cron (one entry):

```cron
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

Local development:

```bash
php artisan schedule:work
```

List registered SCF tasks:

```bash
php artisan scf:schedule:list
```

Registered tasks include document maintenance, idempotency pruning, daily backups, and cache warming.

## Development vs production

| Topic | Development | Production |
| --- | --- | --- |
| Queue | `sync` acceptable | `database` recommended |
| Scheduler | `schedule:work` | system cron |
| Failed jobs | inspect frequently | alert via logs |

Horizon and Redis are optional and not required.

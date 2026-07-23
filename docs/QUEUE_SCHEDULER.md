# Queue and Scheduler Operations

## Queue worker

Production (database queue):

```bash
php artisan queue:work database --sleep=3 --tries=3 --timeout=90
```

**Enterprise:** run under Supervisor — see `docs/infrastructure/SUPERVISOR_EXAMPLE.md` (`numprocs`, autorestart, memory limits, worker log).

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

### Failure and recovery

| Topic | Guidance |
|-------|----------|
| Failed jobs | Inspect `failed_jobs`; fix root cause; `queue:retry` |
| Worker crash | Supervisor `autorestart=true` |
| After deploy | `queue:restart` so workers reload code |
| Monitoring | `scf:queue-status`; alert on growing `pending_jobs` / `failed_jobs` |

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

Registered tasks include document maintenance, idempotency pruning, daily backups, cache warming, and intelligence jobs. Tasks use `withoutOverlapping()`.

### Failure and recovery

| Topic | Guidance |
|-------|----------|
| Missed cron | Reinstall crontab; run missed commands manually if needed |
| Task errors | Check `storage/logs`; re-run artisan command |
| Overlap | Built-in `withoutOverlapping` mutex |

## Development vs production

| Topic | Development | Production |
| --- | --- | --- |
| Queue | `sync` acceptable | `database` recommended + Supervisor |
| Scheduler | `schedule:work` | system cron |
| Failed jobs | inspect frequently | alert via logs |

Horizon and Redis are optional and not required.

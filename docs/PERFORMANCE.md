# Performance & Production Readiness

## Cache architecture

SCF uses Laravel Cache with **file** or **database** drivers by default. No Redis is required.

| Cache key prefix | Purpose | TTL |
|------------------|---------|-----|
| `scf:perf:dashboard:{user}:{locale}` | Dashboard KPI counts | `PERFORMANCE_DASHBOARD_CACHE_TTL` (default 120s) |
| `scf:perf:currencies` | Currency reference list | `PERFORMANCE_REFERENCE_CACHE_TTL` (default 3600s) |
| `scf:perf:tax-rates` | Tax rate reference list | `PERFORMANCE_REFERENCE_CACHE_TTL` |

### Invalidation

- Dashboard cache expires by TTL (per user).
- Reference caches: run `php artisan scf:warm-cache` or wait for TTL expiry.
- Do **not** cache financial transactions, authorization decisions, or tokens.

## Queue worker

Default queue driver: **database** (`QUEUE_CONNECTION=database`).

```bash
php artisan queue:work --queue=notifications,default --tries=3
```

Notifications are queued when `PERFORMANCE_QUEUE_NOTIFICATIONS=true` and queue driver is not `sync`.

## Scheduler

Add to crontab:

```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

Scheduled tasks:

- Hourly document maintenance (overdue invoices/bills, expired quotations/RFQs)
- Daily idempotency key pruning
- Daily database backup with retention pruning
- Hourly reference cache warming

## Health checks

| Endpoint | Purpose |
|----------|---------|
| `GET /up` | Laravel built-in liveness |
| `GET /health/live` | Minimal liveness JSON |
| `GET /health/ready` | Database, cache, queue, storage readiness |
| `GET /api/v1/health` | API health (Sanctum-free) |
| `php artisan scf:health` | CLI readiness check |

Set `PERFORMANCE_HEALTH_EXPOSE_DETAILS=true` only in trusted environments.

## Backups

```bash
php artisan db:backup --label=manual --prune
```

- SQLite: file copy to `database/backups/`
- PostgreSQL: `pg_dump` when available locally
- Retention: `PERFORMANCE_BACKUP_RETENTION_DAYS` (default 14)

## Environment validation

```bash
php artisan scf:validate-env
```

## Instrumentation (development)

```env
PERFORMANCE_INSTRUMENTATION=true
PERFORMANCE_LOG_REQUESTS=true
PERFORMANCE_LOG_SLOW_QUERIES=true
PERFORMANCE_SLOW_QUERY_MS=500
```

## Optional future integrations (NOT enabled)

- Managed Redis
- Elasticsearch / Algolia / Meilisearch Cloud
- Sentry, Datadog, New Relic
- Paid cloud backups or CDN

These require paid accounts and are intentionally not configured in this phase.

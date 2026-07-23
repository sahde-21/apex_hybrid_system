# Logging and Retention

## Channels

Production recommendation:

```env
LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=info
LOG_RETENTION_DAYS=14
```

Daily logs rotate automatically through Laravel's `daily` channel.

## Log catalog

| Category | Source | Notes |
|----------|--------|-------|
| Application / Laravel | `storage/logs/laravel-YYYY-MM-DD.log` | Primary app log |
| Security (authz denials) | Same log (`authorization.denied`) | No secrets |
| Audit | Application audit store | Immutable business/security events |
| Queue / workers | Supervisor logfile + Laravel log | See `SUPERVISOR_EXAMPLE.md` |
| Scheduler | Laravel log during `schedule:run` | Missed jobs → ops alert |
| Deployment | Operator change log + artisan output | Retain with release notes |
| Database | PostgreSQL/MySQL server logs | Host-level |
| Web server | Nginx/Apache access/error | Host-level |
| PHP-FPM | Pool / slowlog (if enabled) | Host-level |

## Rotation and size

| Control | Guidance |
|---------|----------|
| Laravel daily rotation | Enabled via `LOG_STACK=daily` |
| Retention | `LOG_RETENTION_DAYS=14` (adjust per compliance) |
| Disk protection | Alert at 80% (`OPERATIONS_ALERTING.md`); prune old logs |
| Worker logs | Rotate with `logrotate` on Supervisor stdout files |

## Sensitive data protection

Do not log passwords, tokens, database credentials, or full payment card data. Audit attribute redaction is configured in `config/security.php` (`audit_redact`).

Review custom log statements before release. Keep `APP_DEBUG=false` in production so stack traces are not returned to clients.

## Operational inspection

```bash
php artisan pail
tail -f storage/logs/laravel-$(date +%F).log
```

Check deployment, queue, scheduler, and backup errors after each release.

Paid log aggregation services are optional and not enabled by default.

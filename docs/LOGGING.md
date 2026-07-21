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

## Locations

- `storage/logs/laravel.log` (single channel)
- `storage/logs/laravel-YYYY-MM-DD.log` (daily channel)

## Sensitive data

Do not log passwords, tokens, database credentials, or full payment card data. Review custom log statements before release.

## Operational inspection

```bash
php artisan pail
tail -f storage/logs/laravel.log
```

Check deployment, queue, scheduler, and backup errors after each release.

Paid log aggregation services are optional and not enabled by default.

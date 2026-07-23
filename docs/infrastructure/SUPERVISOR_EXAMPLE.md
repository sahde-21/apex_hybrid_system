# Supervisor example (queue workers)

Keep at least one long-running Laravel queue worker under Supervisor (or systemd). Database queue is the Version 1.0 default.

```ini
; /etc/supervisor/conf.d/scf-worker.conf
[program:scf-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/scf/artisan queue:work database --sleep=3 --tries=3 --timeout=90 --max-time=3600 --memory=256
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/scf/storage/logs/worker.log
stopwaitsecs=90
```

## Operations

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status scf-worker:*
php artisan queue:restart   # graceful recycle after each deploy
php artisan scf:queue-status
php artisan queue:failed
php artisan queue:retry all
```

## Policy

| Setting | Value |
|---------|-------|
| Restart | `autorestart=true` |
| Memory | `--memory=256` (MB) then recycle |
| Timeout | `--timeout=90` |
| Tries | `--tries=3` |
| After deploy | Always `queue:restart` |

Horizon/Redis are optional and not required for v1.0.

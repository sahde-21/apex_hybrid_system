# Production Monitoring — SCF Enterprise Suite 1.0

Self-hosted monitoring strategy. Paid APM (Datadog, New Relic, etc.) is optional and not required for Version 1.0.

## Built-in health signals

| Signal | How to check | Cadence |
|--------|--------------|---------|
| Application liveness | `GET /up`, `GET /health/live` | Every 1–5 min (probe) |
| Application readiness | `GET /health/ready`, `php artisan scf:health --detailed` | Every 1–5 min |
| API health | `GET /api/v1/health` | Every 1–5 min |
| Database | Ready check `database` | Via readiness |
| Cache | Ready check `cache` | Via readiness |
| Queue tables | Ready check `queue` + `scf:queue-status` | Every 5–15 min |
| Storage writable | Ready check `storage` | Via readiness |
| Scheduler registered | `scf:schedule:list` | Daily / after deploy |
| Backups present | `scf:backup:list` | Daily (after 02:00 job) |
| Failed jobs | `queue:failed`, `scf:queue-status` | Every 15–60 min |
| Release posture | `scf:release-readiness` | After deploy / weekly |

Set `PERFORMANCE_HEALTH_EXPOSE_DETAILS=false` on public endpoints.

## Host-level signals (operator responsibility)

| Signal | Recommendation |
|--------|----------------|
| Disk usage | Alert ≥ 80% used; critical ≥ 90% on app, DB, and backup volumes |
| Memory | Alert sustained ≥ 85%; watch PHP-FPM + workers |
| CPU | Alert sustained ≥ 85% for 10+ minutes |
| Inode / filesystem | Alert on inode exhaustion |
| Web server errors | Tail Nginx/Apache error log; alert on 5xx spike |
| PHP-FPM | Monitor pool busy workers / listen queue |
| SSL certificate | External check; alert ≤ 21 days to expiry |
| Mail | Synthetic send or bounce monitoring on SMTP provider |

## Application errors

| Source | Location / tool |
|--------|-----------------|
| Laravel | `storage/logs/laravel-YYYY-MM-DD.log` (`LOG_STACK=daily`) |
| Queue workers | Supervisor `stdout_logfile` + Laravel log |
| Slow queries | Enable only when diagnosing (`PERFORMANCE_LOG_SLOW_QUERIES`) |
| Security / auth | Authorization warnings in log; audit trail in app |

## Recommended probe script (cron or monitoring agent)

```bash
#!/usr/bin/env bash
set -euo pipefail
BASE="${APP_URL:-https://erp.example.com}"
curl -fsS "$BASE/health/ready" >/dev/null
cd /var/www/scf && php artisan scf:queue-status --json >/dev/null
```

Wire the script to your host monitor (systemd timer, Nagios, Uptime Kuma, CloudWatch, etc.).

## Dashboard (ops)

Minimum weekly review:

1. Health endpoints green  
2. Failed jobs = 0 or triaged  
3. Latest backup verified  
4. Disk < 80%  
5. Certificate valid > 21 days  

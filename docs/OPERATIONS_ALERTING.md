# Production Alerting — SCF Enterprise Suite 1.0

Alerting is implemented by the **operator’s monitoring stack** using SCF health commands and host metrics. The application provides signals; it does not ship a paid pager.

## Priority model

| Priority | Meaning | Response target (enterprise default) |
|----------|---------|--------------------------------------|
| **P1 Critical** | Customer-facing outage or data risk | Immediate |
| **P2 High** | Degraded service / rising failure | ≤ 4 hours |
| **P3 Medium** | Operational debt / early warning | Next business day |
| **P4 Low** | Informational | Weekly review |

## Alert catalog

| Alert | Priority | Detection | Primary action |
|-------|----------|-----------|----------------|
| Application down | P1 | `/health/live` or `/up` fail | Check PHP-FPM, Nginx, `storage/logs`, bring up |
| Database down | P1 | `/health/ready` database fail | DB process, credentials, disk, restore if corrupt |
| Health check failure (ready) | P1 | `/health/ready` non-200 | `scf:health --detailed` |
| Disk full | P1 | Host ≥ 90% | Free logs/tmp; pause uploads; expand volume |
| Memory critical | P1 | Host OOM / ≥ 95% | Restart FPM/workers; investigate leak |
| Backup failure | P1 | No new backup after schedule; verify fail | Fix `db:backup`; restore path; disk |
| Queue failure (workers dead) | P2 | Supervisor stopped; backlog grows | `supervisorctl`; `queue:restart` |
| Failed jobs spike | P2 | `failed_jobs` rising | Inspect, fix, `queue:retry` |
| Scheduler failure | P2 | Missed daily backup / stale tasks | Fix cron; run missed jobs |
| Mail failure | P2 | SMTP errors in log / provider bounce | Fix mailer credentials/quota |
| CPU critical | P2 | Sustained high CPU | Scale workers/FPM; find hot queries |
| SSL expiration ≤ 21d | P2 | Cert monitor | Renew certificate |
| Certificate errors | P1 if live users broken | Browser/TLS probe fail | Fix cert chain |
| Cache degraded | P3 | Cache health warn | `cache:clear`; driver check |
| Disk ≥ 80% | P3 | Host monitor | Prune logs/backups |
| Demo credentials in prod | P1 if live | `scf:release-readiness` FAIL | Remove demo users |

## Routing

1. P1 → on-call phone/SMS (customer-defined)  
2. P2 → on-call ticket + chat  
3. P3/P4 → ops ticket queue  

Document actual contacts in the customer runbook (see `SUPPORT.md`).

## Noise control

- Probe readiness, not every page.  
- Require multi-window confirmation for CPU/memory (e.g. 2 of 3 intervals).  
- Do not page on single failed job if auto-retried successfully.

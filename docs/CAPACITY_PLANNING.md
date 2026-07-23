# Capacity Planning — SCF Enterprise Suite 1.0

Estimates for self-hosted deployments. Validate with customer load tests before large rollouts.

## Concurrent users (approximate)

| Profile | Concurrent interactive users | App nodes | DB | Workers |
|---------|------------------------------|-----------|-----|---------|
| Small | ≤ 15 | 1× (4 GB) | Shared or small | 1–2 |
| Medium | 15–75 | 1× (8 GB) | Dedicated | 2–4 |
| Large | 75–200 | 2× app (LB) | Dedicated + replicas read-optional | 4–8 |

“Concurrent” means simultaneous authenticated sessions with active requests, not named seats.

## Growth drivers

| Resource | Growth driver | Guidance |
|----------|---------------|----------|
| Database | Documents, ledger, audit, jobs | Monitor table size monthly; index reviews quarterly |
| Storage | Attachments, exports, backups, logs | Separate volume for backups; prune retention |
| CPU | Reports, PDF, intelligence jobs | Schedule heavy jobs off-peak |
| RAM | PHP-FPM children × memory_limit + workers | Size FPM `pm.max_children` from RAM budget |
| Queue | Notifications, intelligence, exports | Scale `numprocs` in Supervisor |

## Branch / company expansion

| Expansion | Strategy |
|-----------|----------|
| Additional branches (same tenant) | Usually same DB; watch inventory/warehouse volume |
| Additional companies | Prefer separate deployment or strict tenancy controls if productized later |
| Heavy API integrations | Raise rate limits carefully; consider Redis queue |

## Scaling playbook

1. **Vertical** — more RAM/CPU on single node (simplest).  
2. **Workers** — increase Supervisor `numprocs` before adding app nodes.  
3. **Database** — move DB off app node; enable managed backups.  
4. **Horizontal app** — multiple PHP nodes behind LB; shared DB; sticky sessions not required if session driver is `database`/`redis`.  
5. **Redis (optional)** — introduce for cache/queue/session under high concurrency.

## Targets to watch

- p95 web latency  
- Queue oldest pending age (`scf:queue-status`)  
- Backup duration vs maintenance window  
- Disk growth week-over-week  

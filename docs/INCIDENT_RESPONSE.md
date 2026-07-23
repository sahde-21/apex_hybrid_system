# Incident Response — SCF Enterprise Suite 1.0

## Severity classification

| Class | Definition | Examples |
|-------|------------|----------|
| **Critical** | Full outage or likely data loss | App/DB down, disk full, failed restore mid-flight, credential leak |
| **High** | Major degradation | Workers dead, mail down, scheduler missed backups, TLS broken |
| **Medium** | Partial impact | Elevated failed jobs, slow queries, disk 80% |
| **Low** | Limited / cosmetic | Single user permission issue, log noise |

## Response lifecycle (all severities)

| Phase | Actions |
|-------|---------|
| **Detection** | Health probes, alerts (`OPERATIONS_ALERTING.md`), user report |
| **Escalation** | Per customer on-call matrix (`SUPPORT.md`) |
| **Recovery** | Use `DISASTER_RECOVERY.md` / `MAINTENANCE_RUNBOOK.md` |
| **Communication** | Status to stakeholders; maintenance page if needed |
| **Resolution** | Confirm health endpoints; clear alerts |
| **Postmortem** | Blameless write-up within 5 business days (Critical/High) |

## Critical playbook

1. Confirm blast radius (`/health/ready`, logs).  
2. `php artisan down` if data integrity at risk.  
3. Preserve evidence (logs, last good backup list).  
4. Restore service (DB/app/code) per DR plan.  
5. `scf:health --detailed` + smoke login.  
6. `php artisan up`.  
7. Postmortem.

## High playbook

1. Stabilize (restart workers, fix cron, renew cert).  
2. Drain/retry failed jobs carefully.  
3. Verify backups still running.  
4. Schedule postmortem if recurring.

## Medium / Low

Ticket → triage → fix in maintenance window → document.

## Security incidents

1. Rotate compromised secrets (`APP_KEY` only with encryption plan).  
2. Revoke API tokens; force password resets.  
3. Review audit logs.  
4. Follow `SECURITY_CHECKLIST.md`.

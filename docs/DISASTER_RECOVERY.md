# Disaster Recovery Plan

This plan covers common operational incidents for self-hosted SCF Enterprise Suite deployments. It does not guarantee zero data loss unless verified backups exist.

## Database corruption

| Item | Guidance |
| --- | --- |
| Detection | Application errors, failed queries, health check database failure |
| Containment | Enable maintenance mode (`php artisan down`) |
| Recovery | Restore latest verified backup with `scf:backup:restore` |
| Validation | `scf:deploy-verify`, financial spot checks |
| Rollback decision | Prefer backup restore when corruption is widespread |
| Data-loss risk | Changes after last backup are lost |
| Escalation | Restore safety backup if restore fails |

## Failed migration

| Item | Guidance |
| --- | --- |
| Detection | `migrate` command failure, 500 errors after deploy |
| Containment | Stay in maintenance mode |
| Recovery | Fix forward with corrective migration; avoid `migrate:rollback` on financial data |
| Validation | `scf:migrations:inspect`, `scf:deploy-verify` |
| Rollback decision | Code rollback + forward-fix migration preferred over data rollback |
| Data-loss risk | Rollback may drop columns or data |
| Escalation | Restore pre-deploy backup if schema is inconsistent |

## Broken deployment

| Item | Guidance |
| --- | --- |
| Detection | Health endpoints fail, `scf:deploy-verify` fails |
| Containment | Maintenance mode |
| Recovery | Redeploy previous release artifacts, rebuild caches |
| Validation | Health endpoints, login, API smoke tests |
| Rollback decision | Code rollback first; database unchanged if migrations did not run |
| Data-loss risk | Low if migrations were not applied |
| Escalation | Database restore only if migrations partially applied |

## Missing environment configuration

| Item | Guidance |
| --- | --- |
| Detection | `scf:deploy-check` failures, boot exceptions |
| Containment | Do not expose stack traces publicly (`APP_DEBUG=false`) |
| Recovery | Restore `.env` from secure backup or secret store |
| Validation | `scf:validate-env`, `scf:deploy-check` |
| Rollback decision | Not applicable |
| Data-loss risk | None |
| Escalation | Rotate `APP_KEY` only with full session/token invalidation plan |

## Queue failure

| Item | Guidance |
| --- | --- |
| Detection | Growing backlog, `scf:queue-status` warnings |
| Containment | Keep app online; pause non-critical jobs if needed |
| Recovery | Restart workers (`php artisan queue:restart`), inspect `failed_jobs` |
| Validation | Queue counts decrease, notifications deliver |
| Rollback decision | Not applicable |
| Data-loss risk | Jobs may need manual retry |
| Escalation | Fix root cause before mass `queue:retry` |

## Scheduler failure

| Item | Guidance |
| --- | --- |
| Detection | Missed backups, overdue documents, stale caches |
| Containment | Run missed commands manually |
| Recovery | Verify cron entry `* * * * * php artisan schedule:run` |
| Validation | `scf:schedule:list`, logs, next backup file |
| Rollback decision | Not applicable |
| Data-loss risk | Low |
| Escalation | Use `schedule:work` only for local development |

## Storage failure

| Item | Guidance |
| --- | --- |
| Detection | Upload/download errors, health storage check fails |
| Containment | Disable uploads if disk is full |
| Recovery | Free space, fix permissions, recreate `storage:link` |
| Validation | Writable path checks, authorized download test |
| Rollback decision | Not applicable |
| Data-loss risk | Possible if files were deleted |
| Escalation | Restore files from filesystem backup |

## Cache failure

| Item | Guidance |
| --- | --- |
| Detection | Slow pages, cache health failure |
| Containment | App may continue with degraded performance |
| Recovery | `php artisan cache:clear`, verify cache driver |
| Validation | `scf:deploy-verify` cache read/write |
| Rollback decision | Not applicable |
| Data-loss risk | None |
| Escalation | Switch to file/database cache if needed |

## Application key loss

| Item | Guidance |
| --- | --- |
| Detection | Decryption errors, invalid sessions |
| Containment | Maintenance mode |
| Recovery | Restore previous `APP_KEY` or re-encrypt data (complex) |
| Validation | Login, encrypted session/cookie behavior |
| Rollback decision | Restore environment backup |
| Data-loss risk | Encrypted data may be unrecoverable |
| Escalation | Treat as major incident |

## Lost administrator access

| Item | Guidance |
| --- | --- |
| Detection | No super-admin can sign in |
| Containment | Restrict network access |
| Recovery | `php artisan scf:create-admin` from secure shell |
| Validation | Login, permissions, audit log entry |
| Rollback decision | Not applicable |
| Data-loss risk | None |
| Escalation | Database user role inspection only if command unavailable |

## Accidental file deletion

| Item | Guidance |
| --- | --- |
| Detection | Missing views/assets/backups |
| Containment | Maintenance mode if app cannot boot |
| Recovery | Redeploy code or restore files from backup |
| Validation | Asset manifest, page load, backup list |
| Rollback decision | Redeploy previous release |
| Data-loss risk | Depends on deleted files |
| Escalation | Database restore not needed unless DB file deleted |

## API availability failure

| Item | Guidance |
| --- | --- |
| Detection | `/api/v1/health` degraded, client errors |
| Containment | Rate-limit abusive clients |
| Recovery | Fix auth, queue, database, or route cache issues |
| Validation | API health, token auth smoke test |
| Rollback decision | Code rollback if regression introduced |
| Data-loss risk | Low |
| Escalation | Review API audit logs and failed jobs |

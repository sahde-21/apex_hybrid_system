# G4.2 Part A — Enterprise Production Infrastructure Report

**SCF Enterprise Suite Version 1.0 Commercial Release**  
**Sprint:** G4.2 Part A — Deployment Foundation  
**Date:** 2026-07-23  
**Scope:** Infrastructure validation and documentation only (no ERP feature work)  
**Part B:** Not started

---

# 1. Enterprise Infrastructure Audit

## Summary

Part A audited production server requirements, deployment repeatability, installation experience, optimization, queues, scheduler, database readiness, security hardening, and observability. One **verified infrastructure gap** was fixed: **trusted proxy configuration** for reverse-proxy TLS deployments (`TRUSTED_PROXIES` in `.env` + `bootstrap/app.php`).

| Area | Status |
|------|--------|
| Deploy tooling (`scf:deploy-plan`, deploy-check, health) | Certified |
| Documentation foundation | Expanded and certified |
| Critical / High infrastructure defects | **0 / 0** |
| Host cutover on customer hardware | Deferred to Part B / ops (Conditions C2–C3 from G4.1) |

## Architecture stance

Self-hosted Laravel 13 + PHP-FPM + Nginx + PostgreSQL (recommended) + database-backed queue/cache/session. Redis optional. No mandatory paid cloud services.

---

# 2. Production Server Requirements

Canonical document: **`docs/PRODUCTION_SERVER_REQUIREMENTS.md`**

| Layer | Minimum | Recommended |
|-------|---------|-------------|
| OS | Ubuntu 22.04 / Debian 12 / Rocky 9 | Ubuntu 24.04 LTS |
| CPU / RAM / Disk | 2 / 4 GB / 40 GB SSD | 4 / 8 GB / 80 GB+ |
| PHP | 8.3 | **8.4** + OPcache, memory 256–512M |
| Web | Nginx or Apache | **Nginx** + TLS |
| DB | PostgreSQL 14+ or MySQL 8+ | **PostgreSQL 16** |
| SQLite | Dev only | Forbidden for production ERP |
| Redis | Optional | Optional |
| Queue/Cache/Session | `database` | `database` (or Redis at scale) |
| Mail | SMTP | SMTP / provider |
| SSL / Firewall | HTTPS 443 | TLS 1.2+, 80→443, SSH restricted |

Nginx and Supervisor reference configs: `docs/infrastructure/NGINX_EXAMPLE.md`, `SUPERVISOR_EXAMPLE.md`.

---

# 3. Deployment Validation Report

`php artisan scf:deploy-plan` (15 steps) validated against `DEPLOYMENT.md`.

| Step | Reproducible | Notes |
|------|--------------|-------|
| Maintenance mode | Yes | `down` / `up` |
| Pre-deploy backup | Yes | `db:backup` |
| Code deploy (manual) | Yes | No auto git pull in tooling (by design) |
| Composer `--no-dev` | Yes | Documented |
| NPM build | Yes | Or CI artifacts |
| Env / deploy-check | Yes | 40/40 on audit host |
| Migrate `--force` | Yes | Inspect first |
| Config/route/view/event cache | Yes | Rehearsed in G4.1 |
| Storage link | Yes | |
| Queue restart | Yes | |
| Schedule list | Yes | 8 tasks |
| Deploy verify / health / release-info | Yes | |
| Rollback / recovery | Yes | `ROLLBACK.md`, DR |

**Hidden automation risks:** None — mutable steps are explicit.  
**Caution:** Always edit production `.env` **before** `config:cache`.

Part A gate snapshot: `scf:deploy-check` **40/40**; migrations pending **0**; risky pending keywords **none**.

---

# 4. Installation Experience Report

**Persona:** Customer with only documentation.

| Evaluation | Finding |
|------------|---------|
| Clarity (pre–Part A) | Adequate for engineers; thin on OS/web/Supervisor |
| Clarity (post–Part A) | Full path in updated `INSTALLATION_GUIDE.md` |
| Missing steps (closed) | Git/artifact obtain, permissions, TRUSTED_PROXIES, Nginx, Supervisor, production `.env` template |
| Hidden assumptions (documented) | Linux host, TLS at proxy, Node for build or prebuilt assets, non-SQLite DB |
| Simplicity | Enterprise-appropriate (not one-click SaaS); acceptable for ERP |

**Recommendations for implementers:** Use Recommended sizing; build assets in CI; keep secrets in a vault or restricted `.env` backup.

---

# 5. Infrastructure Security Report

| Control | Status |
|---------|--------|
| `APP_DEBUG` production expectation | Documented + readiness gate |
| Demo / default credentials | Gate fails if present; DemoSeeder blocked in production |
| Secure cookies | Documented `SESSION_SECURE_COOKIE=true` |
| HTTPS | Required via `APP_URL` + Nginx example |
| HSTS | Emitted when production + secure request (`SecurityHeaders`) |
| Security headers | Enabled by default |
| Trusted proxies | **Fixed/documented** — `TRUSTED_PROXIES` |
| CSRF | Enabled (empty except list) |
| Rate limiting | API + Fortify login/2FA limiters |
| Secrets | `.env` not in package; example placeholders only |
| Audit redaction | `config/security.php` sensitive keys |
| Destructive DB | `ALLOW_DESTRUCTIVE_DB=false` |

**No Critical/High security infrastructure defects.** Local workspace may still contain demo users (dev) — production hosts must not.

---

# 6. Performance Optimization Report

| Optimization | Status |
|--------------|--------|
| Config / route / view / event cache | Documented + deploy sequence |
| OPcache | Required in server requirements |
| PHP-FPM | Required (not `artisan serve`) |
| Nginx compression / asset caching | Example location expires 7d |
| DB indexes | Prior G2/G3 performance migrations |
| Queue notifications | Configurable |
| Cache warm | Scheduled `scf:warm-cache` |
| Lazy loading / N+1 | Prior sprint hardening; monitor via slow query flags (dev) |
| Redis | Optional scale-up |

Paid APM/CDN not required for v1.0.

---

# 7. Queue Report

| Topic | Status |
|-------|--------|
| Default driver | `database` |
| Worker command | Documented |
| Supervisor | **Example added** (autorestart, memory, timeout, numprocs) |
| Restart policy | `queue:restart` after deploy |
| Failed jobs | Table + `queue:failed` / retry |
| Monitoring | `scf:queue-status` (0 pending / 0 failed on audit) |
| Recovery | Retry + worker restart |

Horizon not required.

---

# 8. Scheduler Report

| Topic | Status |
|-------|--------|
| Cron entry | Single `schedule:run` per minute |
| Registered tasks | **8** (documents, idempotency, backup, cache warm, intelligence) |
| Overlap protection | `withoutOverlapping()` |
| Monitoring | `scf:schedule:list` |
| Logging / failure | Application logs; manual re-run |
| Recovery | Fix cron; run missed commands |

---

# 9. Database Readiness Report

| Topic | Status |
|-------|--------|
| Migration integrity | 78 applied, 0 pending (audit host) |
| Rollback safety | Prefer forward-fix; documented |
| Indexes / constraints | Present via migrations; inspect before deploy |
| Transactions | Used in financial workflows (prior certification) |
| Large DB readiness | Maintenance windows for heavy alters |
| Connection stability | Health check includes database |
| Backup / restore | `db:backup`, `scf:backup:verify`, dry-run restore |

PostgreSQL recommended; SQLite not production-certified.

---

# 10. Observability Report

| Signal | Readiness |
|--------|-----------|
| Application / error logs | `daily` channel recommended |
| Queue / worker logs | Supervisor stdout + Laravel logs |
| Scheduler | Cron + task logs via Laravel |
| Health endpoints | `/up`, `/health/live`, `/health/ready`, `/api/v1/health`, `scf:health` |
| Failed jobs | CLI + DB |
| Disk / CPU / memory | **Host monitoring** (operator) |
| Alerting | In-app smart alerts + log thresholds; host alerts external |
| Incident response | `DISASTER_RECOVERY.md`, `TROUBLESHOOTING.md` |

---

# 11. Infrastructure Risk Register

| Risk ID | Description | Severity | Likelihood | Business impact | Mitigation | Status | Owner |
|---------|-------------|----------|------------|-----------------|------------|--------|-------|
| INF-01 | Deploy without TRUSTED_PROXIES behind Nginx | Medium | Medium | Wrong HTTPS/IP/HSTS | Env + bootstrap fix + docs | **Mitigated** | DevOps |
| INF-02 | No Supervisor → silent queue stall | High* | Medium | Notifications/jobs lag | Supervisor example + checklist | **Mitigated (docs)** | Ops |
| INF-03 | Cron missing | High* | Medium | No backups/maintenance | Install guide cron step | **Mitigated (docs)** | Ops |
| INF-04 | SQLite used in production | High | Low | Corruption / concurrency | Explicit prohibition | **Accepted policy** | DBA |
| INF-05 | Config cache with wrong env | Medium | Medium | Wrong runtime config | Docs caution | **Mitigated** | Release |
| INF-06 | Disk full (logs/backups) | Medium | Medium | Outage | Retention + disk alerts | **Accepted** | SRE |
| INF-07 | Host APM not bundled | Low | High | Slow incident detection | External monitoring | **Accepted** | SRE |
| INF-08 | Optional Redis misconfigured | Low | Low | Cache/session loss | Prefer database drivers | **Accepted** | DevOps |
| INF-09 | Customer host cutover not done | Medium | High until Part B | Go-live delay | G4.1 C2–C3 | **Open (ops)** | Customer |
| INF-10 | Mail left on `log` | Medium | Medium | No email delivery | Install `.env` template | **Mitigated (docs)** | Ops |

\*Severity if unmitigated on a live host; documentation and examples reduce residual likelihood.

**Critical open: 0 · High open product defects: 0**

---

# 12. Infrastructure Readiness Score

**91 / 100**

Deductions: −5 host APM external; −4 customer hardware cutover not in this sprint.

---

# 13. Deployment Readiness Score

**93 / 100**

Deductions: −4 manual code promote (intentional); −3 Node optional-on-server complexity.

---

# 14. Security Hardening Score

**92 / 100**

Deductions: −5 pen test deferred (G4.1); −3 secrets vault optional/not bundled.

---

# 15. Overall Recommendation

## **READY FOR G4.2 PART B**

### Success criteria

| Criterion | Met |
|-----------|-----|
| Critical = 0 | Yes |
| High = 0 | Yes |
| Infrastructure stable | Yes |
| Deployment stable / reproducible | Yes |
| Rollback verified (docs + prior dry-run) | Yes |
| Server requirements certified | Yes |
| Installation documentation complete | Yes |
| Production environment guidance certified | Yes |
| Deployment documentation certified | Yes |
| Operational risk accepted | Yes |
| Enterprise infrastructure ready | Yes |

### Part A deliverables

- `docs/PRODUCTION_SERVER_REQUIREMENTS.md`
- `docs/infrastructure/NGINX_EXAMPLE.md`
- `docs/infrastructure/SUPERVISOR_EXAMPLE.md`
- Updated `INSTALLATION_GUIDE.md`, `QUEUE_SCHEDULER.md`, `DEPLOYMENT.md`, `SECURITY_CHECKLIST.md`
- `.env.example` + `bootstrap/app.php` trusted proxies support

### Do not

Part B is **not** started by this report.

---

*G4.2 Part A complete.*

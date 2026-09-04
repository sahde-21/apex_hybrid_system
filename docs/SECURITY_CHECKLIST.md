# Security Release Checklist

Use this checklist before Version 1.0 production promotion.

## Application configuration

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] Strong unique `APP_KEY` set and backed up securely (never commit secrets)
- [ ] `APP_URL` uses HTTPS
- [ ] Release metadata configured (`APP_VERSION`, `APP_RELEASE`)
- [ ] Real production database credentials (do not reuse `.env.example` placeholders)
- [ ] `INVENTORY_LEDGER_ENABLED=false` until explicit cutover approval

## Transport and headers

- [ ] HTTPS termination configured on the reverse proxy
- [ ] `APP_URL` uses `https://`
- [ ] `TRUSTED_PROXIES` set when behind Nginx/Apache/LB (`*` or proxy IPs)
- [ ] `SESSION_SECURE_COOKIE=true` under HTTPS
- [ ] Security headers enabled (`SECURITY_HEADERS_ENABLED=true`)
- [ ] HSTS issued by app when production + HTTPS (`SecurityHeaders` middleware)
- [ ] Review CSP report-only config before enforcement

## Authentication and sessions

- [ ] CSRF protection active on web routes
- [ ] Fortify login throttling enabled
- [ ] 2FA and passkeys available for administrators
- [ ] No demo users with default password (`scf:deploy-check`)

## API security

- [ ] Sanctum token prefix and expiration configured
- [ ] API rate limits enabled
- [ ] Idempotency keys enabled for write operations
- [ ] CORS restricted via `SCF_CORS_ALLOWED_ORIGINS` (explicit HTTPS origins; never `*` in production)

## Authorization

- [ ] `ProductionSeeder` applied
- [ ] First admin created with `scf:create-admin`
- [ ] Super-admin role limited to trusted accounts
- [ ] Private attachments require authorization

## Data protection

- [ ] Audit logs immutable
- [ ] Logs exclude secrets and credentials
- [ ] Backups stored on restricted local paths
- [ ] `ALLOW_DESTRUCTIVE_DB=false` in production

## Operations

- [ ] Queue worker running (Supervisor/systemd — `docs/infrastructure/SUPERVISOR_EXAMPLE.md`)
- [ ] Scheduler cron active (`* * * * * php artisan schedule:run`)
- [ ] `php artisan storage:link` completed
- [ ] Backup schedule verified (`db:backup --prune` / `docs/BACKUP_OPERATIONS.md`)

## Operational verification

- [ ] `scf:deploy-check --production` passes
- [ ] `scf:release-readiness` not `not_ready`
- [ ] Health endpoints return expected status
- [ ] Maintenance mode bypass secret configured and not public

## Deferred / optional paid controls

The following are documented only and not required for v1.0:

- Paid WAF or bot protection
- Paid vulnerability scanning
- Paid SIEM or log aggregation
- Managed secrets vaults

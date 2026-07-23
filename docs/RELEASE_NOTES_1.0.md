# Release Notes — SCF Enterprise Suite Version 1.0.0

**Release name:** SCF Enterprise Suite 1.0  
**Schema version:** 2026.07  
**Status:** **Version 1.0 Stable** — certified for commercial production release (G4.2 Part D, 2026-07-23)

## Highlights

- Full ERP workflows: sales, purchasing, inventory, accounting, HR, CRM, projects, support, POS, customer portal
- Workflow engine with document status maintenance
- REST API v1 with Sanctum tokens and idempotency
- Business Intelligence and rule-based Intelligence workspace (forecasts, health score, smart alerts, recommendations)
- Tri-locale UI: English, Arabic (RTL), Central Kurdish (RTL)
- Deployment, backup, restore, health, and release-readiness artisan tooling
- Branded error pages and accessibility-oriented UI polish (Sprint G2)

## Security notes for operators

- Do **not** deploy with demo seeders or default demo passwords
- Require `APP_ENV=production`, `APP_DEBUG=false`, HTTPS `APP_URL`
- Complete `docs/SECURITY_CHECKLIST.md` before go-live

## Known limitations

See `KNOWN_LIMITATIONS.md`.

## Upgrade

Fresh installs: follow `INSTALLATION_GUIDE.md`.  
Existing deployments: follow `UPGRADE_GUIDE.md`, `DEPLOYMENT.md`, and `MIGRATION_SAFETY.md`. Prefer forward-fix migrations; avoid automatic `migrate:rollback` on financial data.

## Distribution

Commercial ZIP packaging: `docs/DISTRIBUTION.md` and `scripts/build-release-package.sh`.

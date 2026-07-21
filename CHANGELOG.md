# Changelog

All notable changes to the SCF Enterprise Suite are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [1.0.0] - 2026-07-21

### Added

- Core ERP foundation: products, inventory, contacts, branches, users, and permissions.
- Enterprise accounting: chart of accounts, journals, fiscal periods, currencies, financial statements, auto-posting.
- Enterprise sales workflow: quotations, sale orders, invoices, payments.
- Enterprise purchasing workflow: purchase requests, RFQs, purchase orders, bills, vendor payments.
- Workflow engine with approvals, history, and notifications.
- Activity timeline, comments, mentions, and immutable audit center.
- REST API v1 with Sanctum authentication, idempotency, rate limits, and OpenAPI documentation.
- Performance optimizations: caching, indexes, global search, health checks, scheduler, and local backups.
- Phase E deployment readiness:
  - Release metadata (`config/release.php`)
  - `scf:release-info`, `scf:deploy-check`, `scf:deploy-verify`, `scf:release-readiness`
  - `scf:create-admin`, `scf:deploy-plan`, `scf:migrations:inspect`
  - `scf:queue-status`, `scf:schedule:list`
  - `scf:backup:list`, `scf:backup:verify`, `scf:backup:restore`
  - Administration system-information page
  - Production seeding separation and deployment documentation

### Security

- Role-based permissions across all modules.
- API token abilities, expiration, and audit logging.
- CSRF protection, security headers, and production debug enforcement.

### Documentation

- Deployment, release process, disaster recovery, rollback, security checklist, and operational guides.

[1.0.0]: https://github.com/example/scf-enterprise-suite/releases/tag/v1.0.0

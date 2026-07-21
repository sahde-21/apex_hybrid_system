# Migration Safety

## Principles

- Never run `migrate:fresh`, `db:wipe`, or `migrate:rollback` automatically in production.
- Prefer forward-fix migrations over editing already-applied migrations.
- Preserve financial and audit records.
- Review pending migrations before deployment.

## Inspection command

```bash
php artisan scf:migrations:inspect
php artisan scf:migrations:inspect --json
```

Reports pending migrations and keyword matches such as `dropColumn`, `truncate`, and `Schema::drop`. Keyword matches require manual review; they are not a guarantee of risk.

## SQLite vs PostgreSQL

- Most migrations support both drivers.
- PostgreSQL-specific indexes or constraints are documented inline where used.
- PostgreSQL backups require local `pg_dump`; restores require `psql`.

## Locking expectations

Large table alterations may lock tables on PostgreSQL. Schedule during maintenance windows and take a pre-migration backup.

## Financial migrations

If a migration touches ledger, journal, payment, or audit tables, require:

1. Pre-deploy backup
2. Manual review
3. Post-deploy verification
4. Forward-fix only if correction is needed

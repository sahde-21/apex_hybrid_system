# Backup Operations — SCF Enterprise Suite 1.0

## Automation

| Mechanism | Detail |
|-----------|--------|
| Scheduled backup | Daily `02:00` — `db:backup --prune` (`database-backup` schedule) |
| Retention | `PERFORMANCE_BACKUP_RETENTION_DAYS` (default **14**) |
| Directory | `database/backups/` (or `performance.backup.directory`) |
| Manual | `php artisan db:backup --label=manual` |
| Pre-deploy | `php artisan db:backup --label=pre-deploy` |

## Supported database drivers (application backup tooling)

| Driver | `db:backup` / verify / restore | Production note |
|--------|--------------------------------|-----------------|
| **PostgreSQL** | Supported (`pg_dump` / `psql`) | **Recommended** |
| **SQLite** | Supported (file copy) | Development only |
| **MySQL / MariaDB** | **Not built into SCF backup commands** | Use native `mysqldump` + filesystem policy, or deploy on PostgreSQL |

## Verification

```bash
php artisan scf:backup:list
php artisan scf:backup:verify database_YYYYMMDD_HHMMSS.sql
```

Verify after every production backup failure alert and weekly as a control.

## Restore

```bash
php artisan down --render="errors::503"
php artisan scf:backup:restore {filename}            # dry-run
php artisan scf:backup:restore {filename} --execute  # live
php artisan scf:health --detailed
php artisan up
```

Restore creates a **safety backup** when executed. See `DISASTER_RECOVERY.md`.

## Encryption & off-site copy

Application backups are stored as files on the server. Enterprise policy should:

1. Restrict filesystem permissions on `database/backups/`  
2. Copy verified backups **off-host** (object storage / backup appliance)  
3. Encrypt at rest using host or backup-tool encryption (LUKS, Borg, restic, provider CMEK)

SCF does not require a paid backup SaaS.

## Application & storage backup

| Asset | Method |
|-------|--------|
| Code | Release artifacts / git tag (immutable) |
| `.env` / secrets | Secure vault or encrypted offline copy (never in git) |
| `storage/app` private files | Filesystem or object-storage snapshot |
| `public/build` | Rebuild from release or deploy artifacts |

## Recovery documentation

`DISASTER_RECOVERY.md`, `ROLLBACK.md`, `TROUBLESHOOTING.md`.

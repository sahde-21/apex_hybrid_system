# FAQ — SCF Enterprise Suite 1.0

## Installation & environment

**Q: Can we run production on SQLite?**  
A: No. Use PostgreSQL (recommended) or MySQL/MariaDB. SQLite is for development/tests only.

**Q: Is Redis required?**  
A: No. Database drivers for queue, cache, and session are certified for v1.0.

**Q: Why does release-readiness fail on demo users?**  
A: Production must not ship default passwords. Use `ProductionSeeder` + `scf:create-admin` only.

## Operations

**Q: How do I know the system is healthy?**  
A: `GET /health/ready` and `php artisan scf:health --detailed`.

**Q: Jobs are not sending notifications.**  
A: Confirm Supervisor workers, `QUEUE_CONNECTION=database`, and `scf:queue-status`. See `QUEUE_SCHEDULER.md`.

**Q: Backups did not appear today.**  
A: Confirm cron `schedule:run`, check `database-backup` in `scf:schedule:list`, disk space, and logs.

**Q: Does SCF backup MySQL automatically?**  
A: Application `db:backup` supports PostgreSQL and SQLite. For MySQL use `mysqldump` (or choose PostgreSQL). See `BACKUP_OPERATIONS.md`.

## Security

**Q: How do we enable HSTS?**  
A: Serve HTTPS, set `APP_ENV=production`, `TRUSTED_PROXIES` behind the proxy; `SecurityHeaders` adds HSTS on secure requests.

**Q: How do we rotate the admin password?**  
A: Use in-app security settings or create a replacement admin with `scf:create-admin`, then disable the old account.

## Support

**Q: Where do we get vendor support?**  
A: Per commercial contract — see `SUPPORT.md`. Start with `TROUBLESHOOTING.md` and `MAINTENANCE_RUNBOOK.md`.

## Known limitations

See `KNOWN_LIMITATIONS.md`.

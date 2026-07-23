# Administrator Guide — SCF Enterprise Suite 1.0

## First login

1. Install using `INSTALLATION_GUIDE.md`.
2. Sign in with the account created by `php artisan scf:create-admin`.
3. Enable two-factor authentication for administrators (Settings → Security).
4. Confirm locale (EN / AR / CKB) from the language switcher.

## Company setup checklist

1. Review accounting chart of accounts (seeded by `ProductionSeeder`).
2. Create warehouses and branches as needed.
3. Create products and opening stock.
4. Create customers and suppliers (Contacts).
5. Configure tax, currency, and payment terms used by sales/purchasing.
6. Create additional users and assign roles (Administration → Users).

## Roles and permissions

- Role matrix: `PERMISSION_MATRIX_G3.md`
- Super-admin is unrestricted; limit membership to trusted staff.
- Managers, sales, warehouse, purchasing, HR, accounting, and support roles are seeded.
- Intelligence and BI domains require explicit domain permissions (not a global wildcard).

## Operational duties

| Duty | Command / location |
|------|--------------------|
| Health | `php artisan scf:health --detailed` |
| Release readiness | `php artisan scf:release-readiness` |
| Queue status | `php artisan scf:queue-status` |
| Scheduler | `php artisan scf:schedule:list` |
| Backups | `php artisan db:backup`, `scf:backup:list`, `scf:backup:verify` |
| Logs | `storage/logs/` — see `LOGGING.md` |
| Deploy plan | `php artisan scf:deploy-plan` |

## Security

- Keep `APP_DEBUG=false` in production.
- Use HTTPS and secure cookies.
- Do not seed demo users in production.
- Rotate administrator passwords and revoke unused API tokens.
- Follow `SECURITY_CHECKLIST.md` before each promotion.

## Backup and restore

1. Verify: `php artisan scf:backup:verify {filename}`
2. Dry-run restore: `php artisan scf:backup:restore {filename}`
3. Execute (maintenance mode): `php artisan scf:backup:restore {filename} --execute`

Details: `DISASTER_RECOVERY.md`, `ROLLBACK.md`.

## Intelligence & reporting

Administrators and managers with intelligence permissions can open `/intelligence`, export domain reports, and review smart alerts. See `INTELLIGENCE_OVERVIEW.md` and `INTELLIGENCE_PERMISSIONS.md`.

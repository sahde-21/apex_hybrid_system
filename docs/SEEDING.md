# Production Seeding

## Required system seeders

```bash
php artisan db:seed --class=ProductionSeeder --force
```

Includes:

- `RolePermissionSeeder` — idempotent roles and permissions sync
- `AccountingSeeder` — chart of accounts and accounting baseline

## Optional demo seeders

```bash
php artisan db:seed --class=DemoSeeder
```

**Never run in production.** Creates demo users (`admin@scf.com`, `test@example.com`) with insecure passwords and sample POS/portal data.

## Default DatabaseSeeder behavior

- `APP_ENV=production` → `ProductionSeeder` only
- Other environments → `DemoSeeder` + `AccountingSeeder`

## Test factories

Factories under `database/factories/` are for tests only and are not invoked by production seeders.

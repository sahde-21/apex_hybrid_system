# Release Process

## Version 1.0 scope

SCF Enterprise Suite 1.0 includes core ERP, accounting, sales and purchasing workflows, the workflow engine, activity and audit center, REST API v1, performance tooling, and deployment readiness commands.

## Pre-release checklist

1. Update `APP_VERSION`, `APP_RELEASE`, `APP_BUILD_ID`, and `APP_COMMIT_SHA` in the deployment environment.
2. Run `php artisan scf:deploy-check --production`.
3. Run `php artisan scf:release-readiness`.
4. Run targeted tests for changed areas.
5. Build frontend assets with `npm ci && npm run build`.
6. Review `CHANGELOG.md`.

## Deployment sequence

Use `php artisan scf:deploy-plan` to print the safe workflow. Never run destructive commands automatically.

```bash
php artisan down --render="errors::503"
php artisan db:backup --label=pre-deploy
# deploy code manually
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan scf:deploy-check --production
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan storage:link
php artisan queue:restart
php artisan scf:deploy-verify
php artisan up
php artisan scf:health --detailed
php artisan scf:release-info
```

## First production administrator

Do not rely on demo seed users in production.

```bash
php artisan db:seed --class=ProductionSeeder --force
php artisan scf:create-admin
```

## Production seeding

| Seeder | Purpose | Production |
| --- | --- | --- |
| `ProductionSeeder` | Roles, permissions, accounting baseline | Yes |
| `DemoSeeder` | Demo users and sample data | No |
| `DatabaseSeeder` | Local/dev convenience wrapper | No (auto-selects by `APP_ENV`) |

## Post-release validation

- `GET /health/ready`
- `GET /api/v1/health`
- `php artisan scf:deploy-verify`
- `php artisan scf:queue-status`
- `php artisan scf:backup:list`

## Cost control

**FREE / BUILT-IN / SELF-HOSTED**

- Laravel deployment commands
- Database queue
- Laravel scheduler
- Local backup files
- Local logging
- Native Linux/macOS deployment
- Optional Laravel Sail (if used locally)
- Database-backed cache and search

**OPTIONAL COSTS — NOT PURCHASED OR ENABLED**

- Domain name, VPS, managed database, managed Redis, paid CDN, paid backup storage, paid email/SMS, paid monitoring, paid security scanners, managed container registry

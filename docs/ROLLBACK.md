# Rollback Checklist

## Before rolling back

- [ ] Confirm incident scope (code, database, assets, configuration)
- [ ] Enable maintenance mode
- [ ] Create fresh backup: `php artisan db:backup --label=pre-rollback`
- [ ] Record current release metadata: `php artisan scf:release-info --json`

## Application code rollback

1. Redeploy the previous known-good release artifacts.
2. Rebuild frontend assets from the same release tag if needed.
3. Run:
   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan event:cache
   php artisan queue:restart
   ```
4. Run `php artisan scf:deploy-verify`.

**Rule:** Prefer code rollback when migrations did not run or were backward-compatible.

## Database migration rollback

- Do **not** run `migrate:rollback` automatically on financial systems.
- Prefer forward-fix migrations for schema defects.
- Roll back migrations only after manual review with `scf:migrations:inspect`.
- If schema is inconsistent, restoring a backup is often safer than rollback.

## Asset rollback

- Restore `public/build` from the previous release or rebuild with the matching frontend commit.
- Verify `public/build/manifest.json` exists.

## Configuration rollback

- Restore `.env` from secure backup if configuration changed.
- Rebuild config cache after any `.env` change.

## Backup restoration rollback

When code rollback is insufficient:

1. `php artisan down`
2. `php artisan scf:backup:verify {backup}`
3. `php artisan scf:backup:restore {backup}` (dry-run first)
4. `php artisan scf:backup:restore {backup} --execute --force`
5. `php artisan scf:deploy-verify`
6. `php artisan up`

## Queue and scheduler

- `php artisan queue:restart`
- Confirm cron still runs `schedule:run`
- Inspect `failed_jobs` before mass retries

## Final validation

- [ ] `/health/ready` returns 200
- [ ] `/api/v1/health` returns 200
- [ ] Administrator login works
- [ ] Critical financial documents open read-only
- [ ] `scf:release-readiness` has no blocking failures

## Audit history

Preserve audit logs and workflow history whenever possible. Do not delete audit tables during rollback.

# Storage Operations

## Required directories

Writable paths checked by `scf:deploy-check`:

- `storage/app`
- `storage/framework`
- `storage/logs`
- `bootstrap/cache`

## Public storage link

```bash
php artisan storage:link
```

Public assets use `storage/app/public`. Sensitive attachments remain on private disks and must be served through authorized controllers.

## Permissions

- Use application user ownership on Linux/macOS deployments.
- Avoid world-writable (`777`) permissions.
- Typical recommendation: directories `775`, files `664`.

## Backups

Local database backups are stored under `database/backups/` by default (configurable via performance backup settings).

## Cleanup

Temporary exports and uploads should be pruned according to operational policy. No paid object storage is required.

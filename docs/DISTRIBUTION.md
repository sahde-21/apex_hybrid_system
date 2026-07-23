# Distribution Guide — SCF Enterprise Suite 1.0

How to build and ship a commercial customer package.

## Release identity

| Field | Value |
|-------|-------|
| Product | SCF Enterprise Suite |
| Company brand | Sahdi Create Future |
| Version | 1.0.0 |
| Release name | SCF Enterprise Suite 1.0 |
| Schema | 2026.07 |
| Suggested archive name | `scf-enterprise-suite-1.0.0.zip` |

## What to include

- Application source (`app`, `bootstrap`, `config`, `database`, `lang`, `public` without secrets, `resources`, `routes`, `scripts`)
- `artisan`, `composer.json`, `composer.lock`
- `package.json`, `package-lock.json`
- `.env.example` (never `.env`)
- `docs/` customer + ops guides
- `LICENSE`, `NOTICE`, `README.md`, `CHANGELOG.md`
- Optional: prebuilt `public/build/` (if omitting Node on target)
- Optional: customer commercial terms PDF

## What to exclude

| Path | Reason |
|------|--------|
| `.env`, `.env.*` secrets | Credentials |
| `vendor/` | Rebuild with Composer on target (or include only if offline install agreed) |
| `node_modules/` | Rebuild or use prebuilt assets |
| `database/*.sqlite`, `database/backups/*` | Customer data risk |
| `storage/logs/*`, `storage/framework/cache/*` | Runtime |
| `.git/` | Optional; omit for ZIP, keep for private git delivery |
| `tests/`, `phpunit.xml`, CI configs | Optional omit for lean customer ZIP |
| Demo seeders data | Never ship a DB with demo users |

## Build the archive

```bash
chmod +x scripts/build-release-package.sh
./scripts/build-release-package.sh
# produces dist/scf-enterprise-suite-1.0.0.zip (+ .sha256 if shasum available)
```

## Delivery channels

| Channel | Guidance |
|---------|----------|
| ZIP package | Primary commercial drop |
| Private Git | Tag `v1.0.0`; customer clones then installs |
| Docker / Compose | Optional (`Dockerfile`, `docker-compose.yml`) — not required for v1.0 cert |
| VPS / cloud | Follow `INSTALLATION_GUIDE.md` + `PRODUCTION_SERVER_REQUIREMENTS.md` |

## Integrity

After packaging, verify:

1. Archive extracts cleanly  
2. No `.env` inside  
3. `.env.example` present  
4. `docs/INSTALLATION_GUIDE.md` present  
5. Checksum matches published `.sha256`  
6. Customer runs `composer install` + migrate + `scf:create-admin`  

## Installer readiness

There is **no** GUI installer in v1.0. Installation is documentation-driven (enterprise standard for self-hosted ERP). Artisan commands provide automation hooks.

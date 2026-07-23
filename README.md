# SCF Enterprise Suite

**Version 1.0.0** · Release name: *SCF Enterprise Suite 1.0*  
**Company:** Sahdi Create Future  
**Schema:** 2026.07

Self-hosted enterprise hybrid ERP (Laravel + Livewire): sales, purchasing, inventory, accounting, HR, CRM, projects, support, POS, portal, BI/intelligence, and API v1 — with English, Arabic, and Central Kurdish.

## Quick start (production)

1. Read **[docs/PRODUCTION_SERVER_REQUIREMENTS.md](docs/PRODUCTION_SERVER_REQUIREMENTS.md)**  
2. Follow **[docs/INSTALLATION_GUIDE.md](docs/INSTALLATION_GUIDE.md)**  
3. Create the first admin: `php artisan scf:create-admin`  
4. Verify: `php artisan scf:health --detailed` and `php artisan scf:release-readiness`

Full documentation map: **[docs/DOCUMENTATION_INDEX.md](docs/DOCUMENTATION_INDEX.md)**

## Version metadata

Set in `.env` (see `.env.example`):

```env
APP_NAME="Sahdi Create Future"
APP_VERSION=1.0.0
APP_RELEASE="SCF Enterprise Suite 1.0"
APP_SCHEMA_VERSION=2026.07
```

## Support

See [docs/SUPPORT.md](docs/SUPPORT.md) and your commercial agreement.

## License

See [LICENSE](LICENSE), [NOTICE](NOTICE), and [docs/COMMERCIAL_LICENSE_NOTICE.md](docs/COMMERCIAL_LICENSE_NOTICE.md).

## Upgrade

See [docs/UPGRADE_GUIDE.md](docs/UPGRADE_GUIDE.md).

# Support Information — SCF Enterprise Suite 1.0

## Documentation map

See `DOCUMENTATION_INDEX.md` for the full customer and operator library.

## Operator self-service

Before escalating, use:

1. `docs/TROUBLESHOOTING.md`
2. `docs/FAQ.md`
3. `docs/MAINTENANCE_RUNBOOK.md`
4. `docs/OPERATIONS_MONITORING.md` / `docs/OPERATIONS_ALERTING.md`
5. `php artisan scf:health --detailed`
6. `php artisan scf:queue-status`
7. `php artisan scf:release-readiness`
8. Application logs under `storage/logs/`

## Support workflow

1. Reproduce with health/queue/backup commands.  
2. Classify severity (`INCIDENT_RESPONSE.md`).  
3. Apply runbook / DR steps.  
4. Escalate per commercial SLA if unresolved.  
5. Record postmortem for Critical/High.

## Security incidents

Follow `docs/DISASTER_RECOVERY.md` and `docs/SECURITY_CHECKLIST.md`. Rotate credentials and revoke API tokens if compromise is suspected.

## License

Composer package metadata currently declares **MIT** for the application skeleton (`composer.json`). **Commercial distribution terms** (if different) must be supplied by the vendor contract and included in the customer delivery package at shipment time.

# Known Limitations — SCF Enterprise Suite 1.0

| ID | Limitation | Guidance |
|----|------------|----------|
| L1 | Intelligence `configure` UI not shipped | Configure via permissions and config files |
| L2 | Some modules lack mobile card-table layouts / full breadcrumbs | Desktop-first; G2 backlog |
| L3 | RFM / large-customer analytics may need tuning at extreme scale | Monitor query time; use indexes from G2 |
| L4 | Intelligence COGS may use proxy metrics where GL detail is limited | Interpret KPIs with accounting context |
| L5 | External penetration test not included in product certification | Customer may commission independently |
| L6 | Demo seeders insecure by design | Never use in production |
| L7 | Host-level monitoring (CPU/memory/disk alerts) is operator responsibility | Wire OS/host monitoring around `scf:health` and logs |
| L8 | Paid WAF / commercial log aggregation optional | Not required for v1.0 |
| L9 | Built-in `db:backup` supports PostgreSQL + SQLite only | Use `mysqldump` for MySQL/MariaDB or deploy PostgreSQL |

These are accepted for Version 1.0 commercial release unless elevated by a customer contract.

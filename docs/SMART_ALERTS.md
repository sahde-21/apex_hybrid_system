# Smart Alerts

Rule-based alerts that flag patterns requiring review. Alerts are **not** fraud accusations.

## Categories

Financial, Sales, Purchasing, Inventory, Customer, Supplier, Workflow, Security, System Operations.

## Built-in rules

| Rule key | Trigger |
|----------|---------|
| `overdue_receivables` | Outstanding invoices above threshold |
| `low_stock` | Product count at/below minimum stock |
| `negative_cash_flow` | Cash-in below cash-out proxy in period |

Thresholds: `config/intelligence.php` → `alert_thresholds`.

## Persistence

Active alerts stored in `intelligence_alerts`. Duplicate active alerts per `rule_key` are prevented.

## Lifecycle

- **Active** — visible in alert center
- **Acknowledged** — user reviewed (`intelligence.alerts.manage`)
- **Dismissed** — user dismissed with audit trail

## Scheduling

`EvaluateSmartAlertsJob` runs hourly (configurable). Uses database queue.

## API

- `GET /api/v1/intelligence/alerts`
- `POST /api/v1/intelligence/alerts/{id}/acknowledge`
- `POST /api/v1/intelligence/alerts/{id}/dismiss`

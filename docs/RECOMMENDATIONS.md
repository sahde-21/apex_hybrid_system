# Recommendations

Advisory, rule-based recommendations. No automatic financial or workflow actions.

## Built-in rules

| Rule key | Category | Trigger |
|----------|----------|---------|
| `follow_up_invoices` | Financial | Outstanding receivables > 0 |
| `reorder_stock` | Inventory | Low-stock products detected |
| `review_tickets` | Operations | Open support tickets > 0 |

## Fields

Each recommendation includes title, description, reason, suggested action, severity, priority, supporting metrics, and optional action route.

## Lifecycle

Stored in `intelligence_recommendations`. Idempotent refresh via `RefreshRecommendationsJob` (daily).

Users with `intelligence.recommendations.manage` can acknowledge or dismiss.

## Deduplication

Active recommendations with the same `rule_key` are not duplicated.

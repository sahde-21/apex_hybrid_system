# Intelligence Permissions

## Web permissions

| Permission | Description |
|------------|-------------|
| `intelligence.view` | Base access to intelligence module |
| `intelligence.executive.view` | Executive dashboard |
| `intelligence.financial.view` | Financial analytics |
| `intelligence.sales.view` | Sales analytics |
| `intelligence.purchasing.view` | Purchasing analytics |
| `intelligence.inventory.view` | Inventory analytics |
| `intelligence.customers.view` | Customer intelligence |
| `intelligence.suppliers.view` | Supplier intelligence |
| `intelligence.operations.view` | Operations intelligence |
| `intelligence.forecasts.view` | Statistical forecasts |
| `intelligence.alerts.view` | View alerts |
| `intelligence.alerts.manage` | Acknowledge/dismiss alerts |
| `intelligence.recommendations.view` | View recommendations |
| `intelligence.recommendations.manage` | Acknowledge/dismiss recommendations |
| `intelligence.assistant.use` | Smart assistant |
| `intelligence.export` | CSV/PDF exports |
| `intelligence.configure` | Settings (reserved) |

## API token abilities

| Ability | Scope |
|---------|-------|
| `intelligence.read` | Read analytics, alerts, recommendations |
| `intelligence.manage` | Acknowledge/dismiss alerts and recommendations |
| `intelligence.export` | Export endpoints |

## Policies

- `IntelligenceAlertPolicy`
- `IntelligenceRecommendationPolicy`

Manager role receives intelligence permissions via `RolePermissionSeeder`.

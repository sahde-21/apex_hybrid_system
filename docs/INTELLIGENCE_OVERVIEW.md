# Intelligence Overview

SCF Enterprise Suite Phase F adds a **local, rule-based** business intelligence and decision-support layer. No external AI APIs, paid BI platforms, or cloud ML services are used.

## Architecture

- `app/Services/Intelligence/` — domain dashboards and assistant
- `app/Services/Analytics/` — trend and anomaly analysis
- `app/Services/Forecasting/` — statistical forecasts (moving average, linear regression)
- `app/Services/Scoring/` — business health score
- `app/Services/Alerts/` — smart alerts
- `app/Services/Recommendations/` — advisory recommendations
- Existing `app/Services/Bi/` — reused for KPIs and charts

## UI routes

- `/intelligence/executive` — executive overview
- `/intelligence/financial` — financial intelligence
- `/intelligence/sales` — sales analytics
- `/intelligence/purchasing` — purchasing analytics
- `/intelligence/inventory` — inventory analytics
- `/intelligence/customers` — customer intelligence (RFM)
- `/intelligence/suppliers` — supplier intelligence
- `/intelligence/operations` — operational intelligence
- `/intelligence/forecasts` — statistical forecasts
- `/intelligence/alerts` — smart alert center
- `/intelligence/recommendations` — recommendations
- `/intelligence/assistant` — rule-based smart assistant

## API

Read-only endpoints under `/api/v1/intelligence/*` with Sanctum abilities `intelligence.read` and `intelligence.manage`.

## FREE / BUILT-IN

Local analytics, database aggregation, rule-based assistant, statistical forecasts, Laravel cache, database queue, scheduler, DomPDF, CSV export, Chart.js.

## NOT ENABLED

Generative AI APIs, paid forecasting platforms, paid BI tools, vector databases, cloud data warehouses.

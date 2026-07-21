# Intelligence API

Base path: `/api/v1/intelligence`

Authentication: Sanctum bearer token with `intelligence.read` or `intelligence.manage`.

## Read endpoints

| Method | Path | Description |
|--------|------|-------------|
| GET | `/executive` | Executive dashboard payload |
| GET | `/financial` | Financial analytics |
| GET | `/sales` | Sales analytics |
| GET | `/purchasing` | Purchasing analytics |
| GET | `/inventory` | Inventory analytics |
| GET | `/customers` | Customer intelligence |
| GET | `/suppliers` | Supplier intelligence |
| GET | `/operations` | Operations intelligence |
| GET | `/health-score` | Business health score |
| GET | `/forecasts` | Statistical forecasts |
| GET | `/alerts` | Active alerts |
| GET | `/recommendations` | Active recommendations |

Query parameters: `date_from`, `date_to`, `branch_id` (where authorized).

## Write endpoints

| Method | Path | Ability |
|--------|------|---------|
| POST | `/alerts/{id}/acknowledge` | `intelligence.manage` |
| POST | `/alerts/{id}/dismiss` | `intelligence.manage` |
| POST | `/recommendations/{id}/acknowledge` | `intelligence.manage` |
| POST | `/recommendations/{id}/dismiss` | `intelligence.manage` |

## Response envelope

Standard API v1 envelope with `success`, `message`, `data`, `meta`.

OpenAPI spec includes intelligence paths via `App\Support\Api\OpenApiSpec`.

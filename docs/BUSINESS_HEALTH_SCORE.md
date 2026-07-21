# Business Health Score

Advisory decision-support score (0–100). **Not** an audited financial rating or credit score.

## Categories

Weights configured in `config/intelligence.php` under `health_score_weights`:

- Financial health
- Sales momentum
- Cash collection
- Purchasing stability
- Inventory health
- Customer health
- Supplier health
- Operational efficiency
- System operations health

Missing categories are excluded from the total and listed as unavailable — they do not silently become zero.

## Labels

| Score | Label |
|-------|-------|
| 85–100 | Excellent |
| 70–84 | Healthy |
| 55–69 | Stable |
| 40–54 | Needs Attention |
| 0–39 | High Risk |
| — | Insufficient Data |

## Drivers

Each score includes positive and negative drivers with plain-language explanations via `InsightExplanationService`.

## Usage restrictions

- Do not use for automatic approval/rejection
- Do not present as audited financial truth
- Show data freshness and coverage metadata

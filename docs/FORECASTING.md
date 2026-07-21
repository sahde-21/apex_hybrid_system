# Statistical Forecasting

Phase F provides **local, explainable** forecasts only. No external APIs or ML services are used.

## Methods

| Method | Description |
|--------|-------------|
| Simple moving average | Average of the last N periods |
| Weighted moving average | Recent periods weighted more heavily |
| Linear regression | Least-squares trend line extrapolation |

Configuration: `config/intelligence.php` (`forecast_horizon`, `min_historical_points`, `moving_average_window`).

## Supported forecasts

- Sales revenue
- Purchase spend
- Cash collection proxy
- Product demand (where history exists)

## Output metadata

Every forecast includes:

- `method` — algorithm name
- `historical_window` — number of data points used
- `forecast` — estimated future values
- `confidence` — `high`, `medium`, `low`, or `none`
- `is_estimate` — always `true`

## Insufficient data

When fewer than `min_historical_points` (default: 3) exist, forecasting returns an empty result with `confidence: none`. Missing periods are **not** fabricated.

## Future integration

`ForecastEngine` interface allows optional external engines. No external engine is enabled in v1.0.

## NOT ENABLED

Paid forecasting APIs, cloud ML platforms, GPU services.

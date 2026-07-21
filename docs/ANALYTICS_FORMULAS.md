# Analytics Formulas

## Trend analysis

- **Absolute change:** `last_value - first_value`
- **Percentage change:** `(absolute / |first_value|) * 100` (null if first value is zero)
- **Moving average:** mean of last N periods (default N=3)
- **Direction:** classified using `intelligence.trend_sensitivity` (default 5%)

## Statistical forecasts

- **Moving average:** repeats last window mean for horizon periods
- **Linear regression:** least-squares slope on index vs value; projects forward
- Minimum history: `intelligence.min_historical_points` (default 3)
- All forecasts labeled **estimated** — never posted to accounting

## Business health score

Weighted average of category scores (0–100) using `intelligence.health_score_weights`. Missing categories are excluded from the denominator and listed as unavailable.

## RFM segmentation (customers)

Based on transaction behavior only:

- **Recency:** days since last invoice in period
- **Frequency:** invoice count in period
- **Monetary:** sum of invoice amounts in period

Segments: Champions, Loyal, At risk, Dormant — advisory only.

## Gross profit proxy (executive KPIs)

When GL detail is unavailable to the viewer, gross profit may use purchase-order totals as a COGS proxy. Financial intelligence page prefers ledger data when `ledgers.read` is granted.

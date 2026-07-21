# Intelligence Operations

## Scheduled jobs

| Job | Schedule | Purpose |
|-----|----------|---------|
| `GenerateDailyExecutiveSnapshotJob` | Daily | Executive snapshot |
| `EvaluateSmartAlertsJob` | Hourly | Alert evaluation |
| `RefreshRecommendationsJob` | Daily | Recommendation refresh |
| `PruneExpiredIntelligenceSnapshotsJob` | Weekly | Remove expired snapshots |

Configured in `bootstrap/app.php`. Queue: database (default).

## Cache

Analytics results cached with keys scoped by user, branch, locale, currency, and date range. TTLs in `config/intelligence.php`.

## Snapshots

`intelligence_snapshots` stores periodic payloads. `intelligence_runs` logs job execution metadata.

## Export limits

`max_export_rows` in config prevents oversized exports. Large exports should use queue (future enhancement).

## Monitoring

Slow analytics operations logged when exceeding threshold. Data freshness timestamp shown on dashboards.

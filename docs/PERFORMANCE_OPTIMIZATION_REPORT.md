# Performance Optimization Report (Sprint G2)

## Query optimizations

### Customer RFM (intelligence)

**Before:** N+1 — one invoice query per customer.  
**After:** Single grouped query on `invoices` + in-memory segmentation (`DomainAnalyticsService::rfmSegments`).

**Impact:** Customer intelligence page scales with one DB round-trip for invoice aggregates instead of O(customers) queries.

### Existing indexes (unchanged)

Prior phases added BI indexes including `invoices_contact_date_bi_idx`, `invoices_status_date_idx`, `products_stock_levels_bi_idx`.

### New index (G2 migration)

`database/migrations/2026_07_22_100000_add_g2_supplier_analytics_index.php`

- **Table:** `bills`
- **Index:** `(contact_id, bill_date)` as `bills_contact_date_g2_idx`
- **Purpose:** Supplier spend aggregation in intelligence purchasing/suppliers analytics

Run when ready: `php artisan migrate`

## Livewire / frontend

- Intelligence workspace: debounced date filters, loading overlay, disabled assistant submit while loading
- Products index: already uses `wire:model.live.debounce.300ms` on search
- Dashboard: existing `wire:loading.class` on main section

## Caching

No changes to cache keys or TTLs in G2. Intelligence cache scoping from Phase F/G1 preserved.

## Exports / PDF

Sprint G1 export domain whitelist and permission checks **unchanged** — not weakened.

## Baseline methodology

Measure in local/staging with `MeasureRequestPerformance` middleware logs and `debugbar` if enabled:

| Page | Metric |
|------|--------|
| Dashboard | Response time, query count |
| Products index | Query count with search |
| Intelligence customers | Query count (RFM) |
| Invoice PDF | Generation time, memory |

**G2 qualitative improvement:** RFM query count reduced from 1+N to ~2 queries (customers + aggregate).

Record environment (PHP version, DB driver) when comparing before/after.

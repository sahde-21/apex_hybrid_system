# Sprint G1 — System Health Report

**SCF Enterprise Suite v1.0 · Enterprise Audit & Critical Bug Fixes**  
**Date:** 2026-07-22  
**Scope:** Audit and fix only (no new features, no redesign)

---

## 1. Modules audited

| Area | Coverage |
|------|----------|
| Foundation & auth | Routes, middleware, Fortify/settings, active-user gates |
| ERP core | Products, contacts, branches, users |
| Sales & purchasing | Workflows, documents, API v1 resources |
| Accounting & GL | Journal engine, fiscal periods, financial reports |
| Inventory & logistics | Warehouses, transfers, adjustments |
| HR, CRM, projects, support | Models, policies, repositories (sampled) |
| Workflow & activity | Engine, timeline, audit |
| BI & intelligence | Analytics, forecasts, alerts, assistant, exports |
| API platform | Sanctum, abilities, OpenAPI spec, idempotency |
| Deployment & ops | Scheduler, backup, deploy-check, release metadata |
| Localization | EN / AR / CKB (spot checks + existing parity tests) |

**Approximate files in audit scope:** 1,034 PHP files under `app/`, `routes/`, `config/`, `database/`, `tests/`.

---

## 2. Files inspected

- Full route table (614 routes) via `php artisan route:list`
- Intelligence, security, and permission test suites (89 tests in G1 regression batch)
- `app/Support/Navigation.php`, `app/Support/Analytics/ScopesAnalytics.php`
- Intelligence export/API controllers and domain analytics service
- Livewire intelligence workspace (`resources/views/pages/intelligence/⚡workspace.blade.php`)
- `bootstrap/app.php` exception handling and scheduler
- `config/intelligence.php`, `routes/intelligence.php`, `routes/settings.php`
- Repository layer (54 repositories — pattern consistency reviewed)
- Debug/debt scan: `dd()`, `dump()`, `TODO`, `FIXME`, `console.log` (none in application code)

---

## 3. Critical bugs found

| # | Issue | Severity |
|---|--------|----------|
| 1 | `ScopesAnalytics::canView()` treated `intelligence.view` as a wildcard for **all** domain permissions (API/export/service bypass of granular permissions) | **Critical** |
| 2 | Intelligence CSV/PDF export accepted arbitrary `{domain}` strings | **High** |
| 3 | Forecasts UI/API required `intelligence.executive.view` instead of `intelligence.forecasts.view` | **High** |
| 4 | `settings` redirect route had no name (inconsistent route naming) | **Low** |
| 5 | `Navigation::permissionForRoute()` mapped all `intelligence.*` routes to base `intelligence.view` only | **Medium** |

**Previously fixed (Phase F, verified in G1):** `SmartAlertService` PSR-4 namespace mismatch (`App\Services\Intelligence` in `app/Services/Alerts/`).

---

## 4. Critical bugs fixed

| # | Fix |
|---|-----|
| 1 | Removed `intelligence.view` bypass in `ScopesAnalytics`; domain access now requires the specific permission |
| 2 | Whitelisted export domains + per-domain permission checks in `IntelligenceExportController` |
| 3 | Added `ExecutiveAnalyticsService::forecasts()` with `intelligence.forecasts.view`; updated Livewire workspace, API, and assistant |
| 4 | Named `settings` redirect route `settings` in `routes/settings.php` |
| 5 | Added `Navigation::intelligencePermissionForRoute()` for granular intelligence route permissions |

**Tests added:** domain API forbidden without financial permission; export 404 for invalid domain.

---

## 5. Route issues fixed

- **Duplicate named routes:** 0 (verified programmatically)
- **Unnamed routes:** 13 — all framework assets (`flux/*`, `livewire-*`, `up`) except `settings` (now named)
- **Intelligence export:** invalid domains return 404 instead of leaking partial data
- **API intelligence `{domain}`:** already constrained via `whereIn`; service layer now 404s unknown domains

---

## 6. Permission issues fixed

- Granular intelligence permissions enforced at service layer (not only Livewire middleware)
- Export requires `intelligence.export` **and** domain-specific view permission
- Navigation helper aligns with per-page intelligence permissions
- Regression: 89 tests passing including `AuthorizationHardeningTest`, `ModulePermissionsTest`, `SuperAdminAuthorizationTest`, `IntelligencePhaseTest` (19 tests)

---

## 7. Architecture improvements

- Clear separation: **forecasts** endpoint/page no longer depends on executive dashboard permission
- Intelligence domain validation centralized (export whitelist + `DomainAnalyticsService` allow-list)
- Navigation permission map extended without changing sidebar behavior (sidebar already used granular `can()` checks)

No module rewrites. No new packages.

---

## 8. Dead code removed

- No large obsolete trees identified
- No `dd()` / `dump()` / debug `console.log` in application source
- **Not removed:** generated `storage/framework/views` (runtime cache)
- **Not removed:** flat `App\Enums` namespace pattern (established project convention, not a defect)

---

## 9. Database improvements

**Review method:** migration spot-check + intelligence schema from Phase F.

| Item | Status |
|------|--------|
| Intelligence tables indexes | Present on status, category, rule_key, dates |
| Foreign keys on alert/recommendation user refs | `acknowledged_by`, `dismissed_by` constrained |
| `branch_id` on intelligence tables | Nullable, no FK (consistent with soft branch scoping elsewhere) |

**No migration changes in G1** — no schema defects requiring immediate correction.

---

## 10. Remaining issues (non-critical / deferred to G2+)

| Item | Notes |
|------|--------|
| Custom branded error pages | No `resources/views/errors/*`; Laravel defaults used |
| API ability vs Spatie permission | Tokens use `intelligence.read`; user still needs matching Spatie permissions (by design) |
| Customer RFM full-table scan | Performance at very large scale (G2 optimization) |
| Intelligence `intelligence.configure` settings UI | Permission reserved, page not implemented |
| Enum namespace flat `App\Enums` | Historical convention; PSR-4 path mismatch is intentional |
| Full Pest suite | G1 ran targeted security/intelligence/permission suites only |

---

## 11. Overall project health score

| Dimension | Score (0–100) | Notes |
|-----------|---------------|--------|
| Architecture stability | **88** | Layered services; critical intel leak closed |
| Route integrity | **92** | No duplicate names; settings named |
| Authorization | **90** | Granular intel fixed; policies present for intel entities |
| Code hygiene | **91** | No debug debt found in scan |
| Database integrity | **87** | Solid; minor optional FK on branch_id |
| Test signal (G1 subset) | **93** | 89/89 targeted tests pass |
| **Overall** | **90 / 100** | **Production-audit ready for Sprint G2** |

---

## 12. Recommendation for Sprint G2

1. **UI polish** — branded 403/404/419/500 pages, loading/empty states consistency pass  
2. **Performance** — RFM/customer analytics query optimization, intelligence snapshot chunking review  
3. **Regression breadth** — wider automated route→permission matrix test  
4. **Error observability** — slow query logging thresholds for analytics endpoints  
5. **Optional** — `intelligence.configure` admin settings page (feature-flag toggles only)

---

## G1 verification commands run

```bash
php artisan route:list          # 614 routes, 0 duplicate names
php artisan scf:deploy-check    # 40 passes, 0 failures
./vendor/bin/pest tests/Feature/Intelligence/IntelligencePhaseTest.php
./vendor/bin/pest tests/Feature/Security/AuthorizationHardeningTest.php
./vendor/bin/pest tests/Feature/Permissions/ModulePermissionsTest.php
./vendor/bin/pest tests/Feature/Security/PermissionCacheAndDashboardTest.php
./vendor/bin/pest tests/Feature/Erp/SuperAdminAuthorizationTest.php
```

**Result:** All G1 targeted tests passed (89 tests, 141 assertions).

---

## Files changed in Sprint G1

- `app/Support/Analytics/ScopesAnalytics.php`
- `app/Services/Intelligence/DomainAnalyticsService.php`
- `app/Services/Intelligence/ExecutiveAnalyticsService.php`
- `app/Services/Intelligence/SmartAssistantService.php`
- `app/Http/Controllers/IntelligenceExportController.php`
- `app/Http/Controllers/Api/V1/IntelligenceController.php`
- `app/Support/Navigation.php`
- `resources/views/pages/intelligence/⚡workspace.blade.php`
- `routes/settings.php`
- `tests/Feature/Intelligence/IntelligencePhaseTest.php`
- `docs/SPRINT_G1_SYSTEM_HEALTH_REPORT.md` (this document)

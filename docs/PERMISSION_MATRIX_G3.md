# G3 Permission Matrix (Summary)

Generated for Sprint G3 release certification. Source: `RolePermissionSeeder` + `ModulePermissions`.

## Role overview

| Role | Scope | Notable restrictions |
|------|--------|----------------------|
| **super-admin** | All permissions | None (privileged bypass via `Gate::before`) |
| **owner** | All permissions | Same as super-admin (privileged) |
| **manager** | All except `users.*`, `settings.*` | No user/settings admin; full intelligence |
| **cashier** | POS, sales docs, contacts, products (module CRUD+export+print), coupons, gift cards, loyalty | No purchasing, accounting GL, HR, users |
| **warehouse** | Products, warehouses, inventory, transfers, shipping, delivery | No sales, accounting, users |
| **sales** | Sales pipeline, CRM, campaigns, POS, documents | No purchasing, payroll, user admin |
| **hr** | HR modules + workflow actions | No sales, inventory admin, users |
| **purchasing** | PR, RFQ, PO, bills, supplier eval, vendor payments | No sales, payroll, users |
| **accountant** | GL, journals, fiscal, financial reports, AR/AP docs | No POS, user admin |
| **customer-support** | Tickets, KB, CRM interactions, feedback | No financial posting, users |

## Intelligence (granular — G1 hardened)

| Permission | Typical roles |
|------------|----------------|
| `intelligence.view` | Manager (+ route group gate) |
| `intelligence.executive.view` | Manager |
| `intelligence.financial.view` | Manager |
| `intelligence.*.view` | Manager (per domain) |
| `intelligence.export` | Manager (plus domain permission on export) |
| `intelligence.alerts.manage` | Manager |

**Service layer:** `ScopesAnalytics` requires domain permission (no `intelligence.view` wildcard).

## API (Sanctum)

- Token abilities: `products.read`, `intelligence.read`, etc. (`ApiAbilities`)
- **Dual check:** token ability **and** Spatie permission / policy (e.g. `ProductController::index` → `viewAny` policy)

## Denied patterns (verified in tests)

| Actor | Denied |
|-------|--------|
| User with no roles | Dashboard, API products (policy) |
| Warehouse | `users.index` |
| HR | `pos.terminal` |
| `intelligence.export` only (no executive) | Executive CSV export |
| No `analytics.read` | Analytics hub |

## Module permission pattern

`{module}.{action}` where actions include: `read`, `create`, `update`, `delete`, `approve`, `export`, `print` (+ module extras in `EXTRA_PERMISSIONS`).

Total registered permissions: **448** (per `scf:release-readiness`).

# UI / UX Design System (SCF)

## Foundations

Design tokens live in `resources/css/app.css`:

- Spacing: `--scf-space-page`, `--scf-space-card`, `--scf-space-section`
- Radius: `--scf-radius-card`, `--scf-radius-control`
- Shadows: `--scf-shadow-sm`, `--scf-shadow-md`
- Brand: `--color-scf-primary`, semantic success/warning/danger

## Layout primitives

| Class | Use |
|-------|-----|
| `.scf-page` | Max-width page container |
| `.scf-page-header` | Title + actions row |
| `.scf-card` | Content panels |
| `.scf-kpi` / `.scf-kpi-value` | Dashboard metrics |
| `.scf-table-wrap` | Tables with sticky header |
| `.scf-toolbar` | Search/filter/action bars |
| `.scf-empty` | Empty states |

## Shared Blade components

| Component | Purpose |
|-----------|---------|
| `<x-page-header>` | Title, subtitle, optional breadcrumbs & actions |
| `<x-breadcrumbs>` | Accessible breadcrumb trail |
| `<x-empty-state>` | Empty data guidance |
| `<x-no-permission-state>` | Permission denial (empty-state variant) |
| `<x-loading-state>` | Livewire loading overlay / inline spinner |
| `<x-skeleton>` | KPI/table/card skeletons |
| `<x-module-toolbar>` | Standard list page toolbar |

## Action hierarchy

- **Primary:** save, create, confirm (Flux `variant="primary"`)
- **Secondary:** cancel, back (ghost)
- **Destructive:** delete, void (`variant="danger"` + confirmation modal)

## Page header pattern

```blade
<x-page-header
    :title="__('Products')"
    :subtitle="__('Manage products and inventory levels.')"
    :breadcrumbs="[['label' => __('scf.dashboard'), 'href' => route('dashboard')], ['label' => __('Products')]]"
/>
```

## Error pages

Branded layouts: `resources/views/errors/{403,404,419,429,500,503}.blade.php` using `x-layouts.error`.

Copy: `lang/*/scf.php` → `errors.*` and `ui.*`.

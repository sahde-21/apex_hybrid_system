# Responsive, RTL/LTR & Accessibility Guide

## Responsive breakpoints

Tailwind defaults: `sm` 640px, `md` 768px, `lg` 1024px, `xl` 1280px.

- Sidebar: Flux collapsible on mobile; skip link targets `#scf-main-content`
- Tables: use `.scf-table-wrap` with horizontal scroll; optional `.scf-table-wrap--responsive-cards` for card layout on small screens (add `data-label` on cells when adopting)
- Touch targets: PWA rules enforce min 44px on controls

## RTL / LTR

- `dir` on `<html>` from locale (`ar`, `ckb` → RTL)
- Use logical properties (`inset-inline-start`, `ms-auto`, `ps-5`) in new CSS
- Numbers, SKUs, currencies: keep `tabular-nums` for readability in RTL
- Do not mirror semantic icons (e.g. lock, chart)

## Accessibility

- Skip links: `scf.ui.skip_to_main` on app shell; error pages include skip to content
- Focus: `.scf-focus-ring` on interactive breadcrumb links
- Headings: one `h1` per page (page-header or error layout)
- Loading: `aria-live="polite"` on loading overlay
- Status: combine badge color + text (Flux badges)
- Forms: Flux labels; server validation via Livewire/Form Requests

## Dark mode

- Surfaces: `zinc-900/80` cards, not pure black
- Borders: `zinc-800` in dark mode
- Charts: use existing Chart.js theme hooks in BI pages

## Intelligence pages

- `dir="auto"` on intelligence workspace for mixed numerals
- Debounced date filters (`debounce.400ms`) to reduce Livewire churn

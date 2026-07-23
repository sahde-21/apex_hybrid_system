# G2 Manual QA Checklist

## Roles

- [ ] Super Admin
- [ ] Administrator / Manager
- [ ] Accountant
- [ ] Sales user
- [ ] Warehouse user
- [ ] HR user
- [ ] Read-only user

## Locales & themes

- [ ] English LTR — light
- [ ] English LTR — dark
- [ ] Arabic RTL — light / dark
- [ ] Kurdish RTL — light / dark

## Viewports

- [ ] Mobile (~375px)
- [ ] Tablet (~768px)
- [ ] Desktop (≥1280px)

## Flows (each role as permitted)

- [ ] Login / logout
- [ ] Sidebar navigation — only authorized links visible
- [ ] Dashboard loads with KPIs
- [ ] List page: search, filter, pagination
- [ ] Create form — validation errors shown
- [ ] Edit form — save success toast
- [ ] Delete — confirmation modal
- [ ] Print / export (if permitted)
- [ ] Intelligence page — filters, loading state, export
- [ ] Unauthorized URL — 403 or policy denial
- [ ] Invalid URL — branded 404
- [ ] Empty list — empty state message

## Error pages

- [ ] 404 — branded, localized, dashboard/login CTA
- [ ] 403 — export or page without permission
- [ ] 419 — session expired (optional manual CSRF test)

## Accessibility spot checks

- [ ] Tab to skip link → main content
- [ ] Visible focus on buttons and links
- [ ] Icon-only buttons have accessible names (Flux)

## Sign-off

| Tester | Date | Pass / Fail | Notes |
|--------|------|-------------|-------|
| | | | |

# Frontend Build Readiness

## Requirements

- Node.js compatible with Vite 8 (Node 20+ recommended)
- npm

## Install and build

```bash
npm ci
npm run build
```

Production output is written to `public/build/` with `manifest.json`.

## Verification

```bash
test -f public/build/manifest.json && echo "manifest ok"
php artisan scf:deploy-check
```

## Runtime assumptions

- Livewire and Flux UI assets are bundled by Vite.
- Dark mode and RTL are supported through existing layout and Tailwind configuration.
- `APP_URL` must match the public URL used by browsers and Sanctum stateful domains.

## Clean installation

```bash
rm -rf node_modules public/build
npm ci
npm run build
```

Paid CI build platforms are optional and not required.

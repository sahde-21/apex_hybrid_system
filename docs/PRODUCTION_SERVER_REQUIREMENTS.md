# Production Server Requirements — SCF Enterprise Suite 1.0

Enterprise sizing for self-hosted commercial deployments. SQLite is **development only**.

---

## 1. Operating system

| Tier | Supported | Notes |
|------|-----------|-------|
| **Recommended** | Ubuntu 22.04 / 24.04 LTS | Widest ops familiarity |
| **Supported** | Debian 12 | Stable enterprise base |
| **Supported** | Rocky Linux 9 / AlmaLinux 9 | RHEL-compatible |
| Not targeted | Windows Server as app host | Use Linux VMs/containers |

Keep OS packages patched on a monthly cadence.

---

## 2. Compute sizing

| Profile | CPU | RAM | Disk (app + logs) | Swap | Concurrent users (approx.) |
|---------|-----|-----|-------------------|------|----------------------------|
| **Minimum** | 2 vCPU | 4 GB | 40 GB SSD | 2 GB | ≤ 15 |
| **Recommended** | 4 vCPU | 8 GB | 80–160 GB SSD | 4 GB | 15–75 |
| **Growth** | 8 vCPU | 16 GB | 250 GB+ SSD | 4–8 GB | 75–200 |

Separate database servers for Recommended+ when possible. Keep at least **20% free disk** for logs, backups, and PDF/export temp files.

---

## 3. Web server

| Option | Status | Guidance |
|--------|--------|----------|
| **Nginx** | **Recommended** | Terminate TLS; proxy to PHP-FPM |
| Apache (mpm_event + php-fpm) | Supported | Prefer Nginx for static + TLS |

TLS 1.2+ required. HTTP → HTTPS redirect at the reverse proxy. See `docs/infrastructure/NGINX_EXAMPLE.md`.

---

## 4. PHP

| Item | Requirement |
|------|-------------|
| **Minimum** | PHP **8.3** (`composer.json`: `^8.3`) |
| **Recommended** | PHP **8.4** |
| SAPI | **php-fpm** (not `php artisan serve` in production) |
| `memory_limit` | **256M** minimum; **512M** recommended for exports/PDF |
| `max_execution_time` | 60 (web); CLI workers use their own timeouts |
| **OPcache** | **Required** in production (`opcache.enable=1`) |

### Required extensions

`bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `json`, `mbstring`, `openssl`, `pdo`, `tokenizer`, `xml`

Plus database driver: `pdo_pgsql` (recommended) or `pdo_mysql`.

Optional: `redis` (only if using Redis drivers), `intl` (locale formatting).

---

## 5. Composer / Node

| Tool | Requirement |
|------|-------------|
| Composer | **2.x** |
| Node.js | **20 LTS+** (build-time only) |
| npm | Bundled with Node |

Production servers may omit Node if assets are built in CI and `public/build` is deployed as artifacts.

---

## 6. Database

| Engine | Role |
|--------|------|
| **PostgreSQL 14–16** | **Recommended production** |
| MySQL 8.0+ / MariaDB 10.11+ | Supported |
| **SQLite** | **Development / tests only** — not certified for multi-user production |

Production DB recommendations:

- Dedicated instance or managed DB
- Automated backups + point-in-time if available
- Connection limits sized for PHP-FPM pool + queue workers
- UTF8 (`utf8mb4` / PostgreSQL UTF8)

---

## 7. Redis

| Use | Status |
|-----|--------|
| Queue / cache / session | **Optional** |
| Default production | `database` drivers for queue, cache, and session (no Redis required) |

Introduce Redis when concurrency or cache hit rates justify it. Not required for Version 1.0 certification.

---

## 8. Application drivers (production defaults)

| Concern | Recommended | Acceptable |
|---------|-------------|------------|
| `APP_ENV` | `production` | — |
| `APP_DEBUG` | `false` | — |
| `APP_URL` | `https://…` | — |
| `QUEUE_CONNECTION` | `database` | `redis` |
| `CACHE_STORE` | `database` | `redis`, `file` |
| `SESSION_DRIVER` | `database` | `redis` |
| `SESSION_SECURE_COOKIE` | `true` | — |
| `SESSION_ENCRYPT` | `true` | — |
| `MAIL_MAILER` | `smtp` (or SES/etc.) | Never `log` in live ops |
| `FILESYSTEM_DISK` | `local` | `s3` optional |
| `LOG_CHANNEL` / `LOG_STACK` | `stack` / `daily` | — |
| `TRUSTED_PROXIES` | `*` or proxy IPs | Required behind reverse proxy |
| `ALLOW_DESTRUCTIVE_DB` | `false` | — |

---

## 9. Process model

| Process | Requirement |
|---------|-------------|
| PHP-FPM pool | Dedicated user (`www-data` / `scf`) |
| Queue worker | Supervisor-managed `queue:work` (see `QUEUE_SCHEDULER.md`) |
| Scheduler | Single system cron: `* * * * * php artisan schedule:run` |
| Firewall | Allow 80/443 only publicly; SSH restricted |

---

## 10. SSL / firewall / time

- Valid certificate (Let’s Encrypt or corporate PKI)
- NTP synchronized timezone (`APP_TIMEZONE` / OS timezone aligned)
- Locale: `APP_LOCALE` one of `en`, `ar`, `ckb`

---

## Related

- Installation: `INSTALLATION_GUIDE.md`
- Deployment: `DEPLOYMENT.md`
- Nginx example: `infrastructure/NGINX_EXAMPLE.md`
- Supervisor example: `infrastructure/SUPERVISOR_EXAMPLE.md`

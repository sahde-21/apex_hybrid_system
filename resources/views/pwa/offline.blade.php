<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar', 'ckb'], true) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="{{ config('pwa.theme_color') }}">
    <title>{{ __('You are offline') }} — {{ config('pwa.short_name') }}</title>
    <link rel="manifest" href="/manifest.webmanifest">
    <style>
        :root {
            color-scheme: dark;
            --bg: #020617;
            --card: rgba(15, 23, 42, 0.85);
            --text: #e2e8f0;
            --muted: #94a3b8;
            --accent: #38bdf8;
            --btn: #2563eb;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100dvh;
            display: grid;
            place-items: center;
            padding: max(1.5rem, env(safe-area-inset-top)) 1.25rem max(1.5rem, env(safe-area-inset-bottom));
            font-family: "Instrument Sans", ui-sans-serif, system-ui, sans-serif;
            background:
                radial-gradient(ellipse 70% 50% at 50% -10%, rgba(37, 99, 235, 0.45), transparent 55%),
                var(--bg);
            color: var(--text);
        }
        .card {
            width: min(100%, 26rem);
            padding: 2rem;
            border-radius: 1.5rem;
            border: 1px solid rgba(148, 163, 184, 0.2);
            background: var(--card);
            backdrop-filter: blur(16px);
            text-align: center;
            box-shadow: 0 24px 60px rgba(2, 6, 23, 0.45);
        }
        img { width: 5.5rem; height: 5.5rem; border-radius: 1.25rem; margin: 0 auto 1.25rem; display: block; }
        h1 { margin: 0; font-size: 1.5rem; letter-spacing: -0.02em; }
        p { margin: 0.75rem 0 0; color: var(--muted); line-height: 1.55; font-size: 0.95rem; }
        .actions { display: flex; flex-wrap: wrap; gap: 0.75rem; justify-content: center; margin-top: 1.5rem; }
        button, a.button {
            appearance: none;
            border: 0;
            border-radius: 0.85rem;
            padding: 0.75rem 1.15rem;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            text-decoration: none;
            touch-action: manipulation;
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .primary { background: var(--btn); color: white; }
        .ghost { background: rgba(255,255,255,0.06); color: var(--text); border: 1px solid rgba(255,255,255,0.12); }
    </style>
</head>
<body>
    <main class="card">
        <img src="/icons/icon-192x192.png" alt="{{ config('pwa.short_name') }}" width="192" height="192">
        <h1>{{ __('You are offline') }}</h1>
        <p>{{ __('Check your internet connection, then try again. Cached pages and assets may still be available when you reconnect.') }}</p>
        <div class="actions">
            <button class="primary" type="button" onclick="location.reload()">{{ __('Try again') }}</button>
            <a class="button ghost" href="/dashboard">{{ __('Go to dashboard') }}</a>
        </div>
    </main>
</body>
</html>

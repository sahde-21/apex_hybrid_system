<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
<meta name="theme-color" content="#f8fafc" media="(prefers-color-scheme: light)" />
<meta name="theme-color" content="{{ config('pwa.theme_color', '#020617') }}" media="(prefers-color-scheme: dark)" />
<meta name="theme-color" content="{{ config('pwa.theme_color', '#020617') }}" />
<meta name="mobile-web-app-capable" content="yes" />
<meta name="application-name" content="{{ config('pwa.short_name', 'SCF') }}" />
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
<meta name="apple-mobile-web-app-title" content="{{ config('pwa.short_name', 'SCF') }}" />
<meta name="format-detection" content="telephone=no" />
<meta name="msapplication-TileColor" content="{{ config('pwa.theme_color', '#020617') }}" />
<meta name="msapplication-TileImage" content="/icons/icon-144x144.png" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'SCF') : config('app.name', 'SCF') }}
</title>

<link rel="manifest" href="{{ route('pwa.manifest') }}">
<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<link rel="apple-touch-icon" sizes="180x180" href="/icons/icon-180x180.png">
<link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192x192.png">
<link rel="icon" type="image/png" sizes="512x512" href="/icons/icon-512x512.png">

{{-- iOS splash screens --}}
<link rel="apple-touch-startup-image" href="/splash/splash-iphone5.png" media="(device-width: 320px) and (device-height: 568px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)">
<link rel="apple-touch-startup-image" href="/splash/splash-iphone6.png" media="(device-width: 375px) and (device-height: 667px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)">
<link rel="apple-touch-startup-image" href="/splash/splash-iphone6plus.png" media="(device-width: 414px) and (device-height: 736px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)">
<link rel="apple-touch-startup-image" href="/splash/splash-iphone-x.png" media="(device-width: 375px) and (device-height: 812px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)">
<link rel="apple-touch-startup-image" href="/splash/splash-iphone-xr.png" media="(device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)">
<link rel="apple-touch-startup-image" href="/splash/splash-iphone-xs-max.png" media="(device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)">
<link rel="apple-touch-startup-image" href="/splash/splash-ipad.png" media="(device-width: 768px) and (device-height: 1024px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)">
<link rel="apple-touch-startup-image" href="/splash/splash-ipadpro11.png" media="(device-width: 834px) and (device-height: 1194px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)">
<link rel="apple-touch-startup-image" href="/splash/splash-ipadpro12.png" media="(device-width: 1024px) and (device-height: 1366px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)">

<meta name="pwa-cache-version" content="{{ config('pwa.cache_version') }}">

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\View\View;

class PwaController extends Controller
{
    public function manifest(): Response
    {
        $config = config('pwa');

        $manifest = [
            'id' => '/',
            'name' => $config['name'],
            'short_name' => $config['short_name'],
            'description' => $config['description'],
            'start_url' => $config['start_url'],
            'scope' => $config['scope'],
            'display' => $config['display'],
            'display_override' => $config['display_override'],
            'orientation' => $config['orientation'],
            'theme_color' => $config['theme_color'],
            'background_color' => $config['background_color'],
            'lang' => app()->getLocale() ?: $config['lang'],
            'dir' => in_array(app()->getLocale(), ['ar', 'ckb'], true) ? 'rtl' : 'ltr',
            'categories' => $config['categories'],
            'prefer_related_applications' => $config['prefer_related_applications'],
            'icons' => $config['icons'],
            'shortcuts' => [
                [
                    'name' => __('Dashboard'),
                    'short_name' => __('Dashboard'),
                    'description' => __('scf.dashboard_page.subtitle'),
                    'url' => '/dashboard',
                    'icons' => [['src' => '/icons/icon-192x192.png', 'sizes' => '192x192']],
                ],
                [
                    'name' => __('Products'),
                    'short_name' => __('Products'),
                    'url' => '/inventory/products',
                    'icons' => [['src' => '/icons/icon-192x192.png', 'sizes' => '192x192']],
                ],
                [
                    'name' => __('Invoices'),
                    'short_name' => __('Invoices'),
                    'url' => '/sales/invoices',
                    'icons' => [['src' => '/icons/icon-192x192.png', 'sizes' => '192x192']],
                ],
            ],
            'handle_links' => 'preferred',
            'launch_handler' => [
                'client_mode' => 'focus-existing',
            ],
        ];

        return response(json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 200, [
            'Content-Type' => 'application/manifest+json; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function offline(): View
    {
        return view('pwa.offline');
    }

    public function serviceWorker(): Response
    {
        $version = config('pwa.cache_version');
        $offlineUrl = config('pwa.offline_url');
        $theme = config('pwa.theme_color');

        $script = <<<JS
/* SCF Enterprise Suite Service Worker — Livewire-safe */
const CACHE_VERSION = '{$version}';
const OFFLINE_URL = '{$offlineUrl}';
const STATIC_CACHE = CACHE_VERSION + '-static';
const RUNTIME_CACHE = CACHE_VERSION + '-runtime';
const FONT_CACHE = CACHE_VERSION + '-fonts';
const IMAGE_CACHE = CACHE_VERSION + '-images';

const PRECACHE_URLS = [
  OFFLINE_URL,
  '/manifest.webmanifest',
  '/icons/icon-192x192.png',
  '/icons/icon-512x512.png',
  '/icons/maskable-192x192.png',
  '/icons/maskable-512x512.png',
  '/apple-touch-icon.png',
  '/favicon.ico',
  '/favicon.svg',
];

const NETWORK_ONLY_PATHS = [
  '/livewire',
  '/login',
  '/logout',
  '/register',
  '/forgot-password',
  '/reset-password',
  '/email',
  '/two-factor-challenge',
  '/user/confirm-password',
  '/user/two-factor',
  '/passkeys',
  '/sanctum',
  '/_boost',
];

self.addEventListener('install', (event) => {
  event.waitUntil((async () => {
    const cache = await caches.open(STATIC_CACHE);
    await cache.addAll(PRECACHE_URLS);
    await self.skipWaiting();
  })());
});

self.addEventListener('activate', (event) => {
  event.waitUntil((async () => {
    const keys = await caches.keys();
    await Promise.all(
      keys
        .filter((key) => !key.startsWith(CACHE_VERSION))
        .map((key) => caches.delete(key))
    );
    await self.clients.claim();
  })());
});

self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});

function isNetworkOnly(url) {
  const path = url.pathname;
  return NETWORK_ONLY_PATHS.some((prefix) => path === prefix || path.startsWith(prefix + '/'));
}

function isStaticAsset(url) {
  return (
    url.pathname.startsWith('/build/') ||
    url.pathname.startsWith('/icons/') ||
    url.pathname.startsWith('/splash/') ||
    /\\.(?:css|js|mjs|woff2?|ttf|otf|eot|svg|png|jpe?g|gif|webp|ico|map)$/i.test(url.pathname)
  );
}

function isFont(url) {
  return /\\.(?:woff2?|ttf|otf|eot)$/i.test(url.pathname) || url.hostname.includes('fonts.');
}

function isImage(url) {
  return /\\.(?:png|jpe?g|gif|webp|svg|ico)$/i.test(url.pathname) || url.pathname.startsWith('/icons/') || url.pathname.startsWith('/splash/');
}

async function cacheFirst(request, cacheName) {
  const cache = await caches.open(cacheName);
  const cached = await cache.match(request);
  if (cached) {
    return cached;
  }
  const response = await fetch(request);
  if (response && response.ok) {
    cache.put(request, response.clone());
  }
  return response;
}

async function staleWhileRevalidate(request, cacheName) {
  const cache = await caches.open(cacheName);
  const cached = await cache.match(request);
  const networkPromise = fetch(request)
    .then((response) => {
      if (response && response.ok) {
        cache.put(request, response.clone());
      }
      return response;
    })
    .catch(() => cached);
  return cached || networkPromise;
}

async function networkFirstNavigation(request) {
  try {
    const response = await fetch(request);
    return response;
  } catch (error) {
    const cache = await caches.open(STATIC_CACHE);
    const offline = await cache.match(OFFLINE_URL);
    return offline || new Response('Offline', { status: 503, statusText: 'Offline' });
  }
}

self.addEventListener('fetch', (event) => {
  const request = event.request;
  if (request.method !== 'GET') {
    return;
  }

  const url = new URL(request.url);

  if (url.origin !== self.location.origin && !isFont(url)) {
    return;
  }

  if (isNetworkOnly(url)) {
    return;
  }

  if (request.mode === 'navigate' || (request.headers.get('accept') || '').includes('text/html')) {
    event.respondWith(networkFirstNavigation(request));
    return;
  }

  if (isFont(url)) {
    event.respondWith(staleWhileRevalidate(request, FONT_CACHE));
    return;
  }

  if (isImage(url)) {
    event.respondWith(staleWhileRevalidate(request, IMAGE_CACHE));
    return;
  }

  if (isStaticAsset(url)) {
    event.respondWith(staleWhileRevalidate(request, RUNTIME_CACHE));
    return;
  }
});
JS;

        return response($script, 200, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Service-Worker-Allowed' => '/',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }
}

<?php

use function Pest\Laravel\get;

it('serves the web app manifest', function () {
    get(route('pwa.manifest'))
        ->assertOk()
        ->assertHeader('content-type', 'application/manifest+json; charset=UTF-8')
        ->assertJsonPath('short_name', config('pwa.short_name'))
        ->assertJsonPath('display', 'standalone')
        ->assertJsonStructure(['icons', 'start_url', 'theme_color', 'background_color']);
});

it('serves the service worker script', function () {
    get(route('pwa.sw'))
        ->assertOk()
        ->assertHeader('content-type', 'application/javascript; charset=UTF-8')
        ->assertHeader('Service-Worker-Allowed', '/')
        ->assertSee('CACHE_VERSION', false)
        ->assertSee('/offline', false);
});

it('serves the offline fallback page', function () {
    get(route('pwa.offline'))
        ->assertOk()
        ->assertSee(__('You are offline'), false);
});

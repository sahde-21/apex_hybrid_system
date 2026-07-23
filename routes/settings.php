<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'throttle:settings'])->group(function () {
    Route::redirect('settings', 'settings/profile')->name('settings');

    Route::livewire('settings/profile', 'pages::settings.profile')->name('profile.edit');
    Route::livewire('settings/appearance', 'pages::settings.appearance')->name('appearance.edit');
    Route::livewire('settings/security', 'pages::settings.security')->name('security.edit');
});

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ])->header('Cache-Control', 'public, max-age=3600');
})->middleware('throttle:60,1')->name('well-known.passkeys');

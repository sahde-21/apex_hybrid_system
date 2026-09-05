<?php

use App\Services\UserService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Storage::fake('public');
    test()->seed(RolePermissionSeeder::class);
});

test('user service accepts valid jpeg avatar', function () {
    $avatar = UploadedFile::fake()->image('avatar.jpg', 100, 100);

    $user = app(UserService::class)->createUser([
        'name' => 'Avatar Ok',
        'email' => 'avatar-ok@example.com',
        'password' => 'password',
        'is_active' => true,
    ], ['cashier'], [], $avatar);

    expect($user->avatar_path)->not->toBeNull()
        ->and(Storage::disk('public')->exists($user->avatar_path))->toBeTrue();
});

test('user service rejects non image avatar at service layer', function () {
    $avatar = UploadedFile::fake()->create('malware.exe', 100, 'application/octet-stream');

    expect(fn () => app(UserService::class)->createUser([
        'name' => 'Avatar Bad',
        'email' => 'avatar-bad@example.com',
        'password' => 'password',
        'is_active' => true,
    ], ['cashier'], [], $avatar))->toThrow(ValidationException::class);
});

test('user service rejects svg avatar even with image mime spoof', function () {
    $avatar = UploadedFile::fake()->createWithContent(
        'avatar.svg',
        '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"></svg>'
    );

    expect(fn () => app(UserService::class)->createUser([
        'name' => 'Avatar Svg',
        'email' => 'avatar-svg@example.com',
        'password' => 'password',
        'is_active' => true,
    ], ['cashier'], [], $avatar))->toThrow(ValidationException::class);
});

test('user service rejects mime extension mismatch for avatar', function () {
    $avatar = UploadedFile::fake()->create('avatar.png', 100, 'image/jpeg');

    expect(fn () => app(UserService::class)->createUser([
        'name' => 'Avatar Mismatch',
        'email' => 'avatar-mismatch@example.com',
        'password' => 'password',
        'is_active' => true,
    ], ['cashier'], [], $avatar))->toThrow(ValidationException::class);
});

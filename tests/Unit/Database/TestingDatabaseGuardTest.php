<?php

use App\Support\Database\TestingDatabaseGuard;

it('allows in-memory sqlite for tests', function () {
    config([
        'database.default' => 'sqlite',
        'database.connections.sqlite.database' => ':memory:',
    ]);

    TestingDatabaseGuard::assertSafe(app());

    expect(config('database.connections.sqlite.database'))->toBe(':memory:');
});

it('rejects the development sqlite file path', function () {
    $original = config('database.connections.sqlite.database');

    config([
        'database.default' => 'sqlite',
        'database.connections.sqlite.database' => database_path('database.sqlite'),
    ]);

    try {
        TestingDatabaseGuard::assertSafe(app());
    } finally {
        config(['database.connections.sqlite.database' => $original]);
    }
})->throws(RuntimeException::class, 'development SQLite database');

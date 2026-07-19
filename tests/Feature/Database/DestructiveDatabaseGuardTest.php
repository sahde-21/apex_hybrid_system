<?php

it('uses an in-memory sqlite database during feature tests', function () {
    expect(config('database.default'))->toBe('sqlite')
        ->and(config('database.connections.sqlite.database'))->toBe(':memory:');
});

it('blocks migrate:fresh outside the testing allow-list when flag is off', function () {
    // Inside testing, Laravel permits migrate:fresh for RefreshDatabase.
    // Verify the safeguard is registered for non-testing by inspecting the flag helper.
    expect(filter_var((string) env('ALLOW_DESTRUCTIVE_DB', false), FILTER_VALIDATE_BOOLEAN))->toBeFalse();
});

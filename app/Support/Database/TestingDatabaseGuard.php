<?php

namespace App\Support\Database;

use Illuminate\Foundation\Application;
use RuntimeException;

class TestingDatabaseGuard
{
    /**
     * Absolute paths that must never be targeted by automated tests.
     *
     * @return list<string>
     */
    public static function forbiddenSqlitePaths(Application $app): array
    {
        return [
            $app->databasePath('database.sqlite'),
            $app->basePath('database/database.sqlite'),
        ];
    }

    public static function assertSafe(Application $app): void
    {
        if (! $app->environment('testing')) {
            throw new RuntimeException('TestingDatabaseGuard may only run in the testing environment.');
        }

        $connection = (string) $app['config']->get('database.default');
        $database = (string) $app['config']->get("database.connections.{$connection}.database");

        if ($database === '' || $database === '0') {
            throw new RuntimeException('Testing aborted: database path is empty.');
        }

        if ($database === ':memory:') {
            return;
        }

        $realDatabase = realpath($database) ?: $database;
        $testingSqlite = $app->databasePath('testing.sqlite');

        foreach (self::forbiddenSqlitePaths($app) as $forbidden) {
            $realForbidden = realpath($forbidden) ?: $forbidden;

            if ($realDatabase === $realForbidden || $database === $forbidden) {
                throw new RuntimeException(
                    'Testing aborted: refusing to use the development SQLite database '
                    ."[{$database}]. Use :memory: or database/testing.sqlite."
                );
            }
        }

        $isTestingFile = $database === $testingSqlite
            || str_ends_with($database, DIRECTORY_SEPARATOR.'testing.sqlite')
            || str_ends_with($database, '/testing.sqlite');

        if (! $isTestingFile) {
            throw new RuntimeException(
                "Testing aborted: unexpected database [{$database}] on connection [{$connection}]. "
                .'Expected :memory: or database/testing.sqlite.'
            );
        }
    }
}

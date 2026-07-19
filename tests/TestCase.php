<?php

namespace Tests;

use App\Support\Database\TestingDatabaseGuard;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    /**
     * Create the application, discarding any cached config so phpunit.xml / .env.testing win.
     */
    public function createApplication()
    {
        $this->flushBootstrapConfigCache();

        $app = parent::createApplication();

        TestingDatabaseGuard::assertSafe($app);

        return $app;
    }

    protected function flushBootstrapConfigCache(): void
    {
        $cachedConfig = dirname(__DIR__).'/bootstrap/cache/config.php';

        if (is_file($cachedConfig)) {
            @unlink($cachedConfig);
        }
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}

<?php

namespace App\Console\Commands;

use App\Services\Performance\HealthCheckService;
use Illuminate\Console\Command;

class HealthCheckCommand extends Command
{
    protected $signature = 'scf:health {--detailed : Include driver details in output}';

    protected $description = 'Run application readiness health checks';

    public function handle(HealthCheckService $health): int
    {
        $result = $health->readiness((bool) $this->option('detailed'));

        foreach ($result['checks'] as $name => $passed) {
            $this->line(sprintf('[%s] %s', $passed ? 'PASS' : 'FAIL', $name));
        }

        if (isset($result['details'])) {
            $this->newLine();
            foreach ($result['details'] as $key => $value) {
                $this->line("{$key}: {$value}");
            }
        }

        if ($result['status'] !== 'ok') {
            $this->error(__('scf.performance.health_degraded'));

            return self::FAILURE;
        }

        $this->info(__('scf.performance.health_ok'));

        return self::SUCCESS;
    }
}

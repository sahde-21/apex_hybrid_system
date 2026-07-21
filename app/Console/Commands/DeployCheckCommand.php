<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\RendersDeploymentChecks;
use App\Services\Deployment\DeploymentCheckService;
use Illuminate\Console\Command;

class DeployCheckCommand extends Command
{
  use RendersDeploymentChecks;

  protected $signature = 'scf:deploy-check
                            {--json : Output machine-readable JSON}
                            {--production : Enforce production expectations}';

  protected $description = 'Run read-only pre-deployment validation checks';

  public function handle(DeploymentCheckService $checks): int
  {
    $strict = $this->option('production') || app()->isProduction();
    $results = $checks->preDeployChecks($strict);

    $this->info(__('scf.release.deploy_check_heading'));
    $exit = $this->renderChecks($results, (bool) $this->option('json'));

    if ($exit === self::SUCCESS) {
      $this->info(__('scf.release.deploy_check_passed'));
    } else {
      $this->error(__('scf.release.deploy_check_failed'));
    }

    return $exit;
  }
}

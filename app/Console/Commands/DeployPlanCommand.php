<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DeployPlanCommand extends Command
{
  protected $signature = 'scf:deploy-plan
                            {--execute : Print mutable step commands without running them}
                            {--step= : Run a single step number only (still manual)}';

  protected $description = 'Display or validate the safe deployment workflow (dry-run by default)';

  /**
   * @var list<array{step: int, title: string, command: string|null, mutable: bool}>
   */
  protected array $steps = [
    ['step' => 1, 'title' => 'Enter maintenance mode', 'command' => 'php artisan down --render="errors::503"', 'mutable' => true],
    ['step' => 2, 'title' => 'Create database backup', 'command' => 'php artisan db:backup --label=pre-deploy', 'mutable' => true],
    ['step' => 3, 'title' => 'Deploy new release code', 'command' => '# upload or pull release artifacts manually', 'mutable' => false],
    ['step' => 4, 'title' => 'Install production Composer dependencies', 'command' => 'composer install --no-dev --optimize-autoloader', 'mutable' => true],
    ['step' => 5, 'title' => 'Build frontend assets', 'command' => 'npm ci && npm run build', 'mutable' => true],
    ['step' => 6, 'title' => 'Validate environment', 'command' => 'php artisan scf:deploy-check --production', 'mutable' => false],
    ['step' => 7, 'title' => 'Run database migrations', 'command' => 'php artisan migrate --force', 'mutable' => true],
    ['step' => 8, 'title' => 'Cache config, routes, views, and events', 'command' => 'php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache', 'mutable' => true],
    ['step' => 9, 'title' => 'Verify storage link', 'command' => 'php artisan storage:link', 'mutable' => true],
    ['step' => 10, 'title' => 'Restart queue workers', 'command' => 'php artisan queue:restart', 'mutable' => true],
    ['step' => 11, 'title' => 'Verify scheduler registration', 'command' => 'php artisan scf:schedule:list', 'mutable' => false],
    ['step' => 12, 'title' => 'Run post-deployment verification', 'command' => 'php artisan scf:deploy-verify', 'mutable' => false],
    ['step' => 13, 'title' => 'Exit maintenance mode', 'command' => 'php artisan up', 'mutable' => true],
    ['step' => 14, 'title' => 'Verify health endpoints', 'command' => 'php artisan scf:health --detailed', 'mutable' => false],
    ['step' => 15, 'title' => 'Record release metadata', 'command' => 'php artisan scf:release-info', 'mutable' => false],
  ];

  public function handle(): int
  {
    $this->info(__('scf.release.deploy_plan_heading'));
    $this->warn(__('scf.release.deploy_plan_dry_run'));

    $onlyStep = $this->option('step');

    foreach ($this->steps as $step) {
      if ($onlyStep !== null && (int) $onlyStep !== $step['step']) {
        continue;
      }

      $mutable = $step['mutable'] ? __('scf.release.deploy_plan_mutable') : __('scf.release.deploy_plan_readonly');
      $this->line(sprintf('%02d. %s [%s]', $step['step'], $step['title'], $mutable));

      if ($step['command']) {
        $this->line('    '.$step['command']);
      }
    }

    if ($this->option('execute')) {
      $this->newLine();
      $this->comment(__('scf.release.deploy_plan_execute_note'));
    }

    return self::SUCCESS;
  }
}

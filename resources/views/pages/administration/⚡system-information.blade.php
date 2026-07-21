<?php

use App\Services\Deployment\DeploymentCheckService;
use App\Services\Deployment\QueueStatusService;
use App\Services\Performance\HealthCheckService;
use App\Support\Database\DatabaseBackupService;
use App\Support\Release\ReleaseMetadata;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('System information')] class extends Component {
    #[Computed]
    public function release(): array
    {
        return ReleaseMetadata::runtime();
    }

    #[Computed]
    public function health(): array
    {
        return app(HealthCheckService::class)->readiness(false);
    }

    #[Computed]
    public function queue(): array
    {
        return app(QueueStatusService::class)->status();
    }

    #[Computed]
    public function backups(): array
    {
        return app(DatabaseBackupService::class)->listBackups();
    }

    #[Computed]
    public function readiness(): array
    {
        $service = app(DeploymentCheckService::class);
        $checks = $service->readinessChecks();

        return $service->summarize($checks);
    }

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('settings.read'), 403);
    }
}; ?>

<section class="scf-page space-y-6" dir="auto">
    <x-page-header
        :title="__('scf.release.system_info_title')"
        :subtitle="__('scf.release.system_info_subtitle')"
    />

    <div class="grid gap-6 lg:grid-cols-2">
        <flux:card class="space-y-4">
            <flux:heading size="lg">{{ __('scf.release.system_release_section') }}</flux:heading>
            <dl class="grid gap-3 text-sm">
                @foreach ($this->release as $key => $value)
                    <div class="flex justify-between gap-4 border-b border-zinc-100 pb-2 dark:border-zinc-800">
                        <dt class="text-zinc-500">{{ str_replace('_', ' ', ucfirst($key)) }}</dt>
                        <dd class="font-medium text-end">{{ is_bool($value) ? ($value ? __('Yes') : __('No')) : ($value ?? '—') }}</dd>
                    </div>
                @endforeach
            </dl>
        </flux:card>

        <flux:card class="space-y-4">
            <flux:heading size="lg">{{ __('scf.release.system_health_section') }}</flux:heading>
            <div class="flex flex-wrap gap-2">
                @foreach ($this->health['checks'] as $name => $ok)
                    <flux:badge :color="$ok ? 'green' : 'red'">{{ $name }}</flux:badge>
                @endforeach
            </div>
            <flux:text>{{ __('scf.release.system_health_status', ['status' => $this->health['status']]) }}</flux:text>
        </flux:card>

        <flux:card class="space-y-4">
            <flux:heading size="lg">{{ __('scf.release.system_queue_section') }}</flux:heading>
            <dl class="grid gap-2 text-sm">
                <div class="flex justify-between"><dt>{{ __('scf.release.queue_driver') }}</dt><dd>{{ $this->queue['driver'] }}</dd></div>
                <div class="flex justify-between"><dt>{{ __('scf.release.queue_pending') }}</dt><dd>{{ $this->queue['pending_jobs'] ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt>{{ __('scf.release.queue_failed') }}</dt><dd>{{ $this->queue['failed_jobs'] ?? '—' }}</dd></div>
            </dl>
            @foreach ($this->queue['warnings'] as $warning)
                <flux:callout variant="warning">{{ $warning }}</flux:callout>
            @endforeach
        </flux:card>

        <flux:card class="space-y-4">
            <flux:heading size="lg">{{ __('scf.release.system_backup_section') }}</flux:heading>
            @if ($this->backups === [])
                <flux:text>{{ __('scf.release.backup_list_empty') }}</flux:text>
            @else
                <ul class="space-y-2 text-sm">
                    @foreach (array_slice($this->backups, 0, 5) as $backup)
                        <li class="flex justify-between gap-4">
                            <span>{{ $backup['filename'] }}</span>
                            <span class="text-zinc-500">{{ number_format($backup['size'] / 1024, 1) }} KB</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </flux:card>
    </div>

    <flux:card class="space-y-3">
        <flux:heading size="lg">{{ __('scf.release.system_readiness_section') }}</flux:heading>
        <flux:badge :color="match($this->readiness['status']) {
            'ready' => 'green',
            'ready_with_warnings' => 'amber',
            default => 'red',
        }">
            {{ __('scf.release.readiness_'.$this->readiness['status']) }}
        </flux:badge>
        <flux:text>
            {{ __('scf.release.summary_line', [
                'passes' => $this->readiness['passes'],
                'warnings' => $this->readiness['warnings'],
                'failures' => $this->readiness['failures'],
            ]) }}
        </flux:text>
    </flux:card>
</section>

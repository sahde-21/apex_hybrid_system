<?php

use App\Models\AuditLog;
use App\Services\Audit\AuditLogService;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Audit log detail')] class extends Component {
    public AuditLog $auditLog;

    public function mount(AuditLog $auditLog): void
    {
        $this->authorize('view', $auditLog);
        $this->auditLog = app(AuditLogService::class)->find(auth()->user(), $auditLog->id);
    }
}; ?>

<section class="scf-page space-y-6" dir="auto">
    <x-page-header
        :title="__('scf.activity.audit_detail')"
        :subtitle="class_basename($auditLog->auditable_type).' #'.$auditLog->auditable_id"
    >
        <x-slot:actions>
            <flux:button :href="route('audit-logs.index')" variant="ghost" wire:navigate>{{ __('Back') }}</flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-zinc-500">{{ __('User') }}</dt>
                    <dd class="font-medium">{{ $auditLog->user?->name ?? __('scf.activity.system') }}</dd>
                </div>
                <div>
                    <dt class="text-zinc-500">{{ __('Action') }}</dt>
                    <dd><flux:badge size="sm">{{ $auditLog->action }}</flux:badge></dd>
                </div>
                <div>
                    <dt class="text-zinc-500">{{ __('Date') }}</dt>
                    <dd>{{ $auditLog->created_at?->format('Y-m-d H:i:s') }}</dd>
                </div>
                <div>
                    <dt class="text-zinc-500">{{ __('IP address') }}</dt>
                    <dd>{{ $auditLog->ip_address ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-zinc-500">{{ __('scf.activity.user_agent') }}</dt>
                    <dd class="break-all text-xs text-zinc-600 dark:text-zinc-300">{{ $auditLog->user_agent ?? '—' }}</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="sm" class="mb-3">{{ __('scf.activity.field_changes') }}</flux:heading>
            <x-activity.audit-value-diff :changes="$auditLog->meaningfulChanges()" />
        </div>
    </div>
</section>

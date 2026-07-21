<?php

use App\Models\AuditLog;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Audit logs')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $action = '';

    #[Url]
    public string $model = '';

    #[Url]
    public string $user_id = '';

    #[Url]
    public string $date_from = '';

    #[Url]
    public string $date_to = '';

    #[Computed]
    public function auditLogs()
    {
        return app(AuditLogService::class)->paginate(auth()->user(), [
            'search' => $this->search ?: null,
            'action' => $this->action ?: null,
            'model' => $this->model ?: null,
            'user_id' => $this->user_id !== '' ? (int) $this->user_id : null,
            'date_from' => $this->date_from ?: null,
            'date_to' => $this->date_to ?: null,
        ], 20);
    }

    #[Computed]
    public function users()
    {
        return User::query()->where('is_active', true)->orderBy('name')->limit(200)->get(['id', 'name']);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedAction(): void
    {
        $this->resetPage();
    }

    public function updatedModel(): void
    {
        $this->resetPage();
    }

    public function updatedUserId(): void
    {
        $this->resetPage();
    }
}; ?>

<section class="scf-page space-y-6" dir="auto">
    <x-page-header
        :title="__('scf.activity.audit_title')"
        :subtitle="__('scf.activity.audit_subtitle')"
    />

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('scf.activity.audit_search')" />
        <flux:select wire:model.live="action" :placeholder="__('scf.activity.filter_action')">
            <flux:select.option value="">{{ __('scf.activity.filter_action') }}</flux:select.option>
            <flux:select.option value="created">{{ __('Created') }}</flux:select.option>
            <flux:select.option value="updated">{{ __('Updated') }}</flux:select.option>
            <flux:select.option value="deleted">{{ __('Deleted') }}</flux:select.option>
            <flux:select.option value="restored">{{ __('Restored') }}</flux:select.option>
            <flux:select.option value="notification.dispatched">notification.dispatched</flux:select.option>
        </flux:select>
        <flux:input wire:model.live.debounce.300ms="model" :placeholder="__('scf.activity.filter_model')" />
        <flux:select wire:model.live="user_id" :placeholder="__('scf.activity.filter_user')">
            <flux:select.option value="">{{ __('scf.activity.filter_user') }}</flux:select.option>
            @foreach ($this->users as $user)
                <flux:select.option :value="$user->id">{{ $user->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input type="date" wire:model.live="date_from" :label="__('scf.activity.date_from')" />
        <flux:input type="date" wire:model.live="date_to" :label="__('scf.activity.date_to')" />
    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->auditLogs">
            <flux:table.columns>
                <flux:table.column>{{ __('Date') }}</flux:table.column>
                <flux:table.column>{{ __('User') }}</flux:table.column>
                <flux:table.column>{{ __('Action') }}</flux:table.column>
                <flux:table.column>{{ __('Model') }}</flux:table.column>
                <flux:table.column>{{ __('Record ID') }}</flux:table.column>
                <flux:table.column>{{ __('IP address') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->auditLogs as $auditLog)
                    <flux:table.row wire:key="audit-log-{{ $auditLog->id }}">
                        <flux:table.cell>{{ $auditLog->created_at?->format('Y-m-d H:i') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $auditLog->user?->name ?? __('scf.activity.system') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="match($auditLog->action) {
                                'created' => 'green',
                                'updated' => 'blue',
                                'deleted' => 'red',
                                'restored' => 'amber',
                                default => 'zinc',
                            }">{{ $auditLog->action }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="font-mono text-xs">{{ class_basename($auditLog->auditable_type) }}</flux:table.cell>
                        <flux:table.cell>{{ $auditLog->auditable_id }}</flux:table.cell>
                        <flux:table.cell>{{ $auditLog->ip_address ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:button size="sm" variant="ghost" :href="route('audit-logs.show', $auditLog)" wire:navigate>
                                {{ __('scf.activity.view') }}
                            </flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7">
                            <x-empty-state icon="inbox" :title="__('scf.activity.audit_empty')" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</section>

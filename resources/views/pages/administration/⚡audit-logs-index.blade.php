<?php

use App\Models\AuditLog;
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

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, AuditLog>
     */
    #[Computed]
    public function auditLogs()
    {
        return AuditLog::query()
            ->with('user')
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('auditable_type', 'like', "%{$this->search}%")
                        ->orWhere('action', 'like', "%{$this->search}%")
                        ->orWhereHas('user', fn ($q) => $q->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->action, fn ($query) => $query->where('action', $this->action))
            ->latest()
            ->paginate(15);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedAction(): void
    {
        $this->resetPage();
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Audit logs')"
        :subtitle="__('View system activity and change history')"
    />

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search by user, action, or model...')" />

        <flux:select wire:model.live="action" :placeholder="__('All actions')">
            <flux:select.option value="">{{ __('All actions') }}</flux:select.option>
            <flux:select.option value="created">{{ __('Created') }}</flux:select.option>
            <flux:select.option value="updated">{{ __('Updated') }}</flux:select.option>
            <flux:select.option value="deleted">{{ __('Deleted') }}</flux:select.option>
            <flux:select.option value="restored">{{ __('Restored') }}</flux:select.option>
        </flux:select>
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
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->auditLogs as $auditLog)
                    <flux:table.row wire:key="audit-log-{{ $auditLog->id }}">
                        <flux:table.cell>{{ $auditLog->created_at?->format('Y-m-d H:i') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $auditLog->user?->name ?? __('System') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="match($auditLog->action) {
                                'created' => 'green',
                                'updated' => 'blue',
                                'deleted' => 'red',
                                'restored' => 'amber',
                                default => 'zinc',
                            }">{{ ucfirst($auditLog->action) }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="font-mono text-xs">{{ class_basename($auditLog->auditable_type) }}</flux:table.cell>
                        <flux:table.cell>{{ $auditLog->auditable_id }}</flux:table.cell>
                        <flux:table.cell>{{ $auditLog->ip_address ?? '—' }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6">
                            <x-empty-state icon="inbox" :title="__('No audit logs found.')" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</section>

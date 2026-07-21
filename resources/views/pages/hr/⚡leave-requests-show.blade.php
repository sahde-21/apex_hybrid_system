<?php

use App\Models\LeaveRequest;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Leave request')] class extends Component {
    public LeaveRequest $leaveRequest;

    public function mount(LeaveRequest $leaveRequest): void
    {
        $this->authorize('view', $leaveRequest);
        $this->leaveRequest = $leaveRequest->load(['employee', 'workflowInstance.histories.user', 'workflowInstance.approvals.user']);
    }

    #[On('workflow-updated')]
    public function refreshLeaveRequest(): void
    {
        $this->leaveRequest->refresh()->load(['employee', 'workflowInstance.histories.user', 'workflowInstance.approvals.user']);
    }
}; ?>

<section class="scf-page space-y-6" dir="auto">
    <x-page-header
        :title="__('scf.workflow.leave_show_title')"
        :subtitle="__('scf.workflow.leave_show_subtitle')"
    >
        <x-slot:actions>
            <flux:button :href="route('leave-requests.index')" variant="ghost" wire:navigate>
                {{ __('scf.purchase_workflow.back') }}
            </flux:button>
            @can('update', $leaveRequest)
                <flux:button :href="route('leave-requests.edit', $leaveRequest)" icon="pencil-square" variant="ghost" wire:navigate>
                    {{ __('Edit') }}
                </flux:button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900 lg:col-span-1">
            <div class="flex items-center justify-between gap-2">
                <flux:heading size="md">{{ __('Details') }}</flux:heading>
                <x-workflow.status-badge :label="$leaveRequest->status->label()" :color="$leaveRequest->status->color()" />
            </div>
            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-zinc-500">{{ __('Employee Id') }}</dt>
                    <dd class="font-medium text-zinc-900 dark:text-zinc-100">
                        {{ $leaveRequest->employee?->name ?? $leaveRequest->employee?->fullName() ?? '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-zinc-500">{{ __('Leave Type') }}</dt>
                    <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $leaveRequest->leave_type ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-zinc-500">{{ __('Start Date') }}</dt>
                    <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $leaveRequest->start_date?->format('Y-m-d') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-zinc-500">{{ __('End Date') }}</dt>
                    <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $leaveRequest->end_date?->format('Y-m-d') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-zinc-500">{{ __('Reason') }}</dt>
                    <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $leaveRequest->reason ?: '—' }}</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900 lg:col-span-2">
            <livewire:workflow.workflow-panel :document="$leaveRequest" :key="'leave-workflow-'.$leaveRequest->id" />
        </div>
    </div>
</section>

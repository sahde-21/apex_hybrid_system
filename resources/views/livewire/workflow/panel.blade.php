<div class="space-y-6" dir="auto">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="lg">{{ __('scf.workflow.panel_title') }}</flux:heading>
            <flux:text class="mt-1">{{ __('scf.workflow.actions') }}</flux:text>
        </div>
        <x-workflow.status-badge :label="$this->statusLabel()" :color="$this->statusColor()" />
    </div>

    <x-workflow.actions :actions="$this->actions" />

    <div class="grid gap-6 lg:grid-cols-2">
        <x-workflow.approvals :approvals="$this->instance?->approvals ?? collect()" />
        <x-workflow.timeline :histories="$this->instance?->histories ?? collect()" />
    </div>

    <flux:modal wire:model="showCommentModal" class="max-w-md">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('scf.workflow.confirm_action') }}</flux:heading>
                <flux:text class="mt-1">
                    {{ __('scf.workflow.action_'.$pendingAction) }}
                </flux:text>
            </div>
            <flux:textarea
                wire:model="comment"
                :label="__('scf.workflow.comment')"
                :placeholder="__('scf.workflow.comment_placeholder')"
                rows="4"
            />
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('scf.workflow.cancel_modal') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" wire:click="confirmCommentedAction">{{ __('scf.workflow.apply') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>

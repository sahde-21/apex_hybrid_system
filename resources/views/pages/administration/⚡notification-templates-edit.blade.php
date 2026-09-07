<?php

use App\Models\NotificationTemplate;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;

new #[Title('Edit Notification templates')] class extends \App\Livewire\ConcernBases\NotificationTemplateValidationRulesBase {
    public NotificationTemplate $notificationTemplate;

    public string $name = '';
    public string $code = '';
    public string $channel = 'email';
    public string $subject = '';
    public string $body = '';
    public bool $is_active = true;

    public function mount(NotificationTemplate $notificationTemplate): void
    {
        $this->notificationTemplate = $notificationTemplate;
        $this->name = $notificationTemplate->name ?? '';
        $this->code = $notificationTemplate->code ?? '';
        $this->channel = $notificationTemplate->channel ?? '';
        $this->subject = $notificationTemplate->subject ?? '';
        $this->body = $notificationTemplate->body ?? '';
        $this->is_active = $notificationTemplate->is_active;
    }

    public function save(): void
    {
        $validated = $this->validate($this->notificationTemplateUpdateRules($this->notificationTemplate->id));

        $this->notificationTemplate->update($validated);

        Flux::toast(variant: 'success', text: __('Notification templates updated successfully.'));

        $this->redirect(route('notification-templates.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Edit Notification templates') }}</flux:heading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="name" :label="__('Name')" required />
        <flux:input wire:model="code" :label="__('Code')" required />
        <flux:input wire:model="channel" :label="__('Channel')" />
        <flux:input wire:model="subject" :label="__('Subject')" required />
        <flux:textarea wire:model="body" :label="__('Body')" />
        <flux:switch wire:model="is_active" :label="__('Is Active')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Save changes') }}</flux:button>
            <flux:button :href="route('notification-templates.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>

<?php

use App\Concerns\NotificationTemplateValidationRules;
use App\Models\NotificationTemplate;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create Notification templates')] class extends Component {
    use NotificationTemplateValidationRules;
    public string $name = '';
    public string $code = '';
    public string $channel = 'email';
    public string $subject = '';
    public string $body = '';
    public bool $is_active = true;

    public function mount(): void
    {
    }

    public function save(): void
    {
        $validated = $this->validate($this->notificationTemplateRules());

        NotificationTemplate::query()->create($validated);

        Flux::toast(variant: 'success', text: __('Notification templates created successfully.'));

        $this->redirect(route('notification-templates.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Create Notification templates') }}</flux:heading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="name" :label="__('Name')" required />
        <flux:input wire:model="code" :label="__('Code')" required />
        <flux:input wire:model="channel" :label="__('Channel')" />
        <flux:input wire:model="subject" :label="__('Subject')" required />
        <flux:textarea wire:model="body" :label="__('Body')" />
        <flux:switch wire:model="is_active" :label="__('Is Active')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Create') }}</flux:button>
            <flux:button :href="route('notification-templates.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>

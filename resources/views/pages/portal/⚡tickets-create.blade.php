<?php

use App\Services\Portal\PortalService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.portal')] #[Title('New ticket')] class extends Component {
    use WithFileUploads;

    public string $subject = '';

    public string $priority = 'medium';

    public string $description = '';

    /** @var array<int, mixed> */
    public array $attachments = [];

    public function save(PortalService $portal): void
    {
        $validated = $this->validate([
            'subject' => ['required', 'string', 'max:255'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'description' => ['required', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:5120', 'mimes:jpg,jpeg,png,pdf,doc,docx,txt'],
        ]);

        $ticket = $portal->createTicket(auth('portal')->user(), $validated, $this->attachments);

        $this->redirect(route('portal.tickets.show', $ticket), navigate: true);
    }
}; ?>

<section class="scf-page space-y-6">
    <div class="portal-glass mx-auto max-w-2xl rounded-2xl p-6">
        <flux:heading size="lg">{{ __('scf.portal.new_ticket') }}</flux:heading>
        <form wire:submit="save" class="mt-6 space-y-4">
            <flux:input wire:model="subject" :label="__('Subject')" required />
            <flux:select wire:model="priority" :label="__('Priority')">
                @foreach (['low', 'medium', 'high', 'urgent'] as $priority)
                    <option value="{{ $priority }}">{{ ucfirst($priority) }}</option>
                @endforeach
            </flux:select>
            <flux:textarea wire:model="description" :label="__('Description')" rows="6" required />
            <div>
                <flux:label>{{ __('Attachments') }}</flux:label>
                <input type="file" wire:model="attachments" multiple class="mt-2 block w-full text-sm" />
                @error('attachments.*') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div class="flex gap-2">
                <flux:button type="submit" variant="primary">{{ __('Submit') }}</flux:button>
                <flux:button :href="route('portal.tickets.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
            </div>
        </form>
    </div>
</section>

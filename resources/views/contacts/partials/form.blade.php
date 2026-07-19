<form wire:submit="{{ $submitAction }}" class="scf-page !max-w-4xl !space-y-5">
    <div class="scf-card space-y-5">
        <div>
            <flux:heading size="lg" class="tracking-tight">{{ __('Contact details') }}</flux:heading>
            <flux:subheading>{{ __('Core identity and communication fields') }}</flux:subheading>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus />

            <flux:select wire:model="type" :label="__('Type')" required>
                @foreach (\App\Enums\ContactType::options() as $value => $label)
                    <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="company_name" :label="__('Company name')" type="text" />
            <flux:input wire:model="email" :label="__('Email')" type="email" />
            <flux:input wire:model="phone" :label="__('Phone')" type="text" />
            <flux:input wire:model="tax_number" :label="__('Tax number')" type="text" />

            <div class="md:col-span-2">
                <flux:textarea wire:model="address" :label="__('Address')" rows="3" />
            </div>

            <div class="md:col-span-2">
                <flux:input wire:model="opening_balance" :label="__('Opening balance')" type="number" step="0.01" required />
                <flux:text class="mt-1.5 text-sm text-zinc-500">
                    {{ __('Positive values indicate receivable (they owe you). Negative values indicate payable (you owe them).') }}
                </flux:text>
            </div>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-end gap-2">
        <flux:button :href="route('contacts.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        <flux:button variant="primary" type="submit" icon="check">{{ $submitLabel }}</flux:button>
    </div>
</form>

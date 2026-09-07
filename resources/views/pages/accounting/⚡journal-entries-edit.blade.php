<?php

use App\Enums\JournalEntryStatus;
use App\Models\JournalEntry;
use Flux\Flux;
use Livewire\Attributes\Title;

new #[Title('Edit journal entry')] class extends \App\Livewire\ConcernBases\JournalEntryValidationRulesBase {

    public JournalEntry $journalEntry;

    public string $reference_number = '';
    public string $entry_date = '';
    public string $description = '';
    public string $status = 'draft';
    public string $total_debit = '0';
    public string $total_credit = '0';
    public string $notes = '';

    public function mount(JournalEntry $journalEntry): void
    {
        $this->journalEntry = $journalEntry;
        $this->reference_number = $journalEntry->reference_number;
        $this->entry_date = $journalEntry->entry_date->format('Y-m-d');
        $this->description = $journalEntry->description;
        $this->status = $journalEntry->status->value;
        $this->total_debit = (string) $journalEntry->total_debit;
        $this->total_credit = (string) $journalEntry->total_credit;
        $this->notes = $journalEntry->notes ?? '';
    }

    public function save(): void
    {
        $validated = $this->validate($this->journalEntryRules($this->journalEntry->id));

        $this->journalEntry->update($validated);

        Flux::toast(variant: 'success', text: __('Journal entry updated successfully.'));

        $this->redirect(route('journal-entries.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Edit journal entry') }}</flux:heading>
        <flux:subheading>{{ __('Update journal entry details') }}</flux:subheading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="reference_number" :label="__('Reference number')" required />
        <flux:input wire:model="entry_date" type="date" :label="__('Entry date')" required />
        <flux:input wire:model="description" :label="__('Description')" required />
        <flux:select wire:model="status" :label="__('Status')">
            @foreach (JournalEntryStatus::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="total_debit" type="number" step="0.01" :label="__('Total debit')" required />
        <flux:input wire:model="total_credit" type="number" step="0.01" :label="__('Total credit')" required />
        <flux:textarea wire:model="notes" :label="__('Notes')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Save changes') }}</flux:button>
            <flux:button :href="route('journal-entries.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>

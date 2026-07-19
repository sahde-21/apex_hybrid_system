<?php

use App\Models\Account;
use App\Services\Accounting\JournalEngineService;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create journal entry')] class extends Component {
    public string $entry_date = '';

    public string $description = '';

    public string $notes = '';

    public bool $post_immediately = false;

    /** @var list<array{account_id: string, description: string, debit: string, credit: string}> */
    public array $lines = [];

    public function mount(): void
    {
        $this->authorize('create', \App\Models\JournalEntry::class);
        $this->entry_date = now()->toDateString();
        $this->lines = [
            ['account_id' => '', 'description' => '', 'debit' => '0', 'credit' => '0'],
            ['account_id' => '', 'description' => '', 'debit' => '0', 'credit' => '0'],
        ];
    }

    public function addLine(): void
    {
        $this->lines[] = ['account_id' => '', 'description' => '', 'debit' => '0', 'credit' => '0'];
    }

    public function removeLine(int $index): void
    {
        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
    }

    #[Computed]
    public function accounts()
    {
        return Account::query()->where('is_active', true)->where('allow_manual_entry', true)->orderBy('code')->get(['id', 'code', 'name']);
    }

    #[Computed]
    public function totals(): array
    {
        $debit = '0.00';
        $credit = '0.00';
        foreach ($this->lines as $line) {
            $debit = bcadd($debit, number_format((float) ($line['debit'] ?? 0), 2, '.', ''), 2);
            $credit = bcadd($credit, number_format((float) ($line['credit'] ?? 0), 2, '.', ''), 2);
        }

        return ['debit' => $debit, 'credit' => $credit, 'balanced' => bccomp($debit, $credit, 2) === 0];
    }

    public function save(): void
    {
        $this->validate([
            'entry_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'integer', 'exists:accounts,id'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
        ]);

        app(JournalEngineService::class)->createDraft(auth()->user(), [
            'entry_date' => $this->entry_date,
            'description' => $this->description,
            'notes' => $this->notes,
            'auto_post' => $this->post_immediately,
        ], $this->lines);

        Flux::toast(variant: 'success', text: __('scf.accounting_engine.journal_saved'));
        $this->redirect(route('journal-entries.index'), navigate: true);
    }
}; ?>

<section class="scf-page space-y-6">
    <div class="portal-glass rounded-2xl p-5">
        <flux:heading size="xl">{{ __('scf.accounting_engine.create_journal') }}</flux:heading>
        <flux:subheading>{{ __('scf.accounting_engine.create_journal_subtitle') }}</flux:subheading>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="portal-glass grid gap-4 rounded-2xl p-5 md:grid-cols-2">
            <flux:input wire:model="entry_date" type="date" :label="__('scf.accounting_engine.entry_date')" required />
            <flux:input wire:model="description" :label="__('scf.accounting_engine.description')" required />
            <flux:textarea wire:model="notes" :label="__('Notes')" class="md:col-span-2" />
            <flux:checkbox wire:model="post_immediately" :label="__('scf.accounting_engine.post_immediately')" />
        </div>

        <div class="portal-glass overflow-hidden rounded-2xl">
            <div class="flex items-center justify-between border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
                <flux:heading size="sm">{{ __('scf.accounting_engine.lines') }}</flux:heading>
                <flux:button type="button" size="sm" wire:click="addLine" variant="ghost">{{ __('scf.accounting_engine.add_line') }}</flux:button>
            </div>
            <div class="space-y-3 p-4">
                @foreach ($lines as $index => $line)
                    <div class="grid gap-3 md:grid-cols-12" wire:key="line-{{ $index }}">
                        <div class="md:col-span-4">
                            <flux:select wire:model="lines.{{ $index }}.account_id" :label="__('scf.accounting_engine.account')">
                                <option value="">{{ __('Select') }}</option>
                                @foreach ($this->accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</option>
                                @endforeach
                            </flux:select>
                        </div>
                        <div class="md:col-span-3">
                            <flux:input wire:model="lines.{{ $index }}.description" :label="__('Description')" />
                        </div>
                        <div class="md:col-span-2">
                            <flux:input wire:model.live="lines.{{ $index }}.debit" type="number" step="0.01" :label="__('scf.accounting_engine.debit')" />
                        </div>
                        <div class="md:col-span-2">
                            <flux:input wire:model.live="lines.{{ $index }}.credit" type="number" step="0.01" :label="__('scf.accounting_engine.credit')" />
                        </div>
                        <div class="flex items-end md:col-span-1">
                            <flux:button type="button" size="sm" wire:click="removeLine({{ $index }})" variant="ghost" icon="trash" />
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="flex justify-end gap-6 border-t border-zinc-100 px-5 py-4 text-sm dark:border-zinc-800">
                <span>{{ __('scf.accounting_engine.debit') }}: <strong class="tabular-nums">{{ $this->totals['debit'] }}</strong></span>
                <span>{{ __('scf.accounting_engine.credit') }}: <strong class="tabular-nums">{{ $this->totals['credit'] }}</strong></span>
                <span class="{{ $this->totals['balanced'] ? 'text-emerald-600' : 'text-rose-600' }}">
                    {{ $this->totals['balanced'] ? __('scf.accounting_engine.balanced') : __('scf.accounting_engine.unbalanced_short') }}
                </span>
            </div>
        </div>

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            <flux:button :href="route('journal-entries.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>

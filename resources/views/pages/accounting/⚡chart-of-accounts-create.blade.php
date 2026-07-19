<?php

use App\Enums\AccountType;
use App\Enums\NormalBalance;
use App\Models\Account;
use App\Models\Currency;
use App\Services\Accounting\ChartOfAccountsService;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create Account')] class extends Component {
    public string $code = '';
    public string $name = '';
    public ?int $parent_id = null;
    public string $type = 'asset';
    public string $normal_balance = 'debit';
    public string $currency_code = '';
    public string $opening_balance = '0';
    public bool $is_active = true;
    public bool $allow_manual_entry = true;
    public string $description = '';

    public function mount(): void
    {
        $this->authorize('create', Account::class);
        $this->currency_code = (string) config('accounting.base_currency', 'IQD');
        $this->normal_balance = AccountType::Asset->normalBalance()->value;
    }

    public function updatedType(string $value): void
    {
        $this->normal_balance = AccountType::from($value)->normalBalance()->value;
    }

    #[Computed]
    public function parents()
    {
        return Account::query()->orderBy('code')->get(['id', 'code', 'name']);
    }

    #[Computed]
    public function currencies()
    {
        return Currency::query()->where('is_active', true)->orderBy('code')->get(['code', 'name']);
    }

    public function save(): void
    {
        $this->authorize('create', Account::class);

        $validated = $this->validate([
            'code' => ['required', 'string', 'max:32', 'unique:accounts,code'],
            'name' => ['required', 'string', 'max:180'],
            'parent_id' => ['nullable', 'exists:accounts,id'],
            'type' => ['required', 'in:'.implode(',', array_column(AccountType::cases(), 'value'))],
            'normal_balance' => ['required', 'in:'.implode(',', array_column(NormalBalance::cases(), 'value'))],
            'currency_code' => ['required', 'string', 'size:3'],
            'opening_balance' => ['required', 'numeric'],
            'is_active' => ['boolean'],
            'allow_manual_entry' => ['boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        app(ChartOfAccountsService::class)->create(auth()->user(), $validated);

        Flux::toast(variant: 'success', text: __('scf.accounting_engine.account_created_toast'));
        $this->redirect(route('chart-of-accounts.index'), navigate: true);
    }
}; ?>

<section class="scf-page space-y-6">
    <div class="portal-glass rounded-2xl p-5">
        <flux:heading size="xl">{{ __('scf.accounting_engine.create_account') }}</flux:heading>
        <flux:subheading>{{ __('scf.accounting_engine.coa_subtitle') }}</flux:subheading>
    </div>

    <form wire:submit="save" class="portal-glass grid max-w-2xl gap-5 rounded-2xl p-5">
        <flux:input wire:model="code" :label="__('scf.accounting_engine.code')" required />
        <flux:input wire:model="name" :label="__('Name')" required />
        <flux:select wire:model="parent_id" :label="__('scf.accounting_engine.parent_account')">
            <flux:select.option value="">{{ __('None') }}</flux:select.option>
            @foreach ($this->parents as $parent)
                <flux:select.option :value="$parent->id">{{ $parent->code }} — {{ $parent->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="type" :label="__('Type')" required>
            @foreach (AccountType::cases() as $case)
                <flux:select.option :value="$case->value">{{ $case->label() }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model="normal_balance" :label="__('scf.accounting_engine.normal_balance')" required>
            @foreach (NormalBalance::cases() as $case)
                <flux:select.option :value="$case->value">{{ $case->label() }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model="currency_code" :label="__('scf.accounting_engine.currency')" required>
            @foreach ($this->currencies as $currency)
                <flux:select.option :value="$currency->code">{{ $currency->code }} — {{ $currency->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="opening_balance" type="number" step="0.01" :label="__('scf.accounting_engine.opening_balance')" required />
        <flux:textarea wire:model="description" :label="__('Description')" />
        <flux:checkbox wire:model="is_active" :label="__('Active')" />
        <flux:checkbox wire:model="allow_manual_entry" :label="__('scf.accounting_engine.allow_manual_entry')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            <flux:button :href="route('chart-of-accounts.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>

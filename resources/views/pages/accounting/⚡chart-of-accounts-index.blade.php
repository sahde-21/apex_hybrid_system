<?php

use App\Models\Account;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Chart of Accounts')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Account::class);
    }

    #[Computed]
    public function accounts()
    {
        return Account::query()
            ->with('parent:id,code,name')
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(fn ($inner) => $inner->where('code', 'like', $term)->orWhere('name', 'like', $term));
            })
            ->orderBy('code')
            ->paginate(25);
    }
}; ?>

<section class="scf-page space-y-6">
    <div class="portal-glass rounded-2xl p-5">
        <flux:heading size="xl">{{ __('scf.accounting_engine.coa_title') }}</flux:heading>
        <flux:subheading>{{ __('scf.accounting_engine.coa_subtitle') }}</flux:subheading>
        <div class="mt-4 max-w-md">
            <flux:input wire:model.live.debounce.300ms="search" :label="__('Search')" icon="magnifying-glass" />
        </div>
    </div>

    <div class="portal-glass overflow-hidden rounded-2xl">
        <table class="min-w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                <tr>
                    <th class="px-4 py-3 text-start">{{ __('scf.accounting_engine.code') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('Name') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('Type') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('scf.accounting_engine.normal_balance') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('Status') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach ($this->accounts as $account)
                    <tr wire:key="acc-{{ $account->id }}">
                        <td class="px-4 py-3 font-mono">{{ $account->code }}</td>
                        <td class="px-4 py-3">
                            {{ $account->name }}
                            @if ($account->is_system)
                                <span class="ms-2 rounded bg-amber-50 px-1.5 py-0.5 text-[10px] text-amber-700 dark:bg-amber-950 dark:text-amber-300">{{ __('scf.accounting_engine.system') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $account->type->label() }}</td>
                        <td class="px-4 py-3">{{ $account->normal_balance->label() }}</td>
                        <td class="px-4 py-3">{{ $account->is_active ? __('Active') : __('Inactive') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="border-t border-zinc-100 px-4 py-3 dark:border-zinc-800">{{ $this->accounts->links() }}</div>
    </div>
</section>

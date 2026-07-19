<?php

use App\Enums\AccountType;
use App\Enums\NormalBalance;
use App\Models\Account;
use App\Models\Currency;
use App\Services\Accounting\ChartOfAccountsService;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Chart of Accounts')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public bool $archived = false;

    public function mount(): void
    {
        $this->authorize('viewAny', Account::class);
    }

    #[Computed]
    public function accounts()
    {
        $query = Account::query()
            ->with('parent:id,code,name')
            ->when($this->archived, fn ($q) => $q->onlyTrashed())
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(fn ($inner) => $inner->where('code', 'like', $term)->orWhere('name', 'like', $term));
            })
            ->orderBy('code');

        return $query->paginate(25);
    }

    public function archive(int $accountId): void
    {
        $account = Account::query()->findOrFail($accountId);
        $this->authorize('delete', $account);
        app(ChartOfAccountsService::class)->archive($account, auth()->user());
        Flux::toast(variant: 'success', text: __('scf.accounting_engine.account_archived_toast'));
        unset($this->accounts);
    }

    public function restore(int $accountId): void
    {
        $account = Account::onlyTrashed()->findOrFail($accountId);
        $this->authorize('restore', $account);
        app(ChartOfAccountsService::class)->restore($account, auth()->user());
        Flux::toast(variant: 'success', text: __('scf.accounting_engine.account_restored_toast'));
        unset($this->accounts);
    }

    public function delete(int $accountId): void
    {
        $account = Account::withTrashed()->findOrFail($accountId);
        $this->authorize('forceDelete', $account);
        app(ChartOfAccountsService::class)->delete($account, auth()->user());
        Flux::toast(variant: 'success', text: __('scf.accounting_engine.account_deleted_toast'));
        unset($this->accounts);
    }
}; ?>

<section class="scf-page space-y-6">
    <div class="portal-glass rounded-2xl p-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ __('scf.accounting_engine.coa_title') }}</flux:heading>
                <flux:subheading>{{ __('scf.accounting_engine.coa_subtitle') }}</flux:subheading>
            </div>
            @can('create', App\Models\Account::class)
                <flux:button variant="primary" :href="route('chart-of-accounts.create')" wire:navigate icon="plus">
                    {{ __('scf.accounting_engine.create_account') }}
                </flux:button>
            @endcan
        </div>
        <div class="mt-4 flex flex-wrap items-end gap-4">
            <div class="max-w-md flex-1">
                <flux:input wire:model.live.debounce.300ms="search" :label="__('Search')" icon="magnifying-glass" />
            </div>
            <flux:checkbox wire:model.live="archived" :label="__('scf.accounting_engine.show_archived')" />
        </div>
    </div>

    <div class="portal-glass overflow-hidden rounded-2xl">
        <table class="min-w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                <tr>
                    <th class="px-4 py-3 text-start">{{ __('scf.accounting_engine.code') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('Name') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('scf.accounting_engine.parent_account') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('Type') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('scf.accounting_engine.opening_balance') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('Status') }}</th>
                    <th class="px-4 py-3 text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($this->accounts as $account)
                    <tr wire:key="acc-{{ $account->id }}">
                        <td class="px-4 py-3 font-mono">{{ $account->code }}</td>
                        <td class="px-4 py-3">
                            {{ $account->name }}
                            @if ($account->is_system)
                                <span class="ms-2 rounded bg-amber-50 px-1.5 py-0.5 text-[10px] text-amber-700 dark:bg-amber-950 dark:text-amber-300">{{ __('scf.accounting_engine.system') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $account->parent?->label() ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $account->type->label() }}</td>
                        <td class="px-4 py-3 font-mono">{{ number_format((float) $account->opening_balance, 2) }}</td>
                        <td class="px-4 py-3">
                            @if ($account->trashed())
                                {{ __('scf.accounting_engine.archived') }}
                            @else
                                {{ $account->is_active ? __('Active') : __('Inactive') }}
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap justify-end gap-1">
                                @if ($account->trashed())
                                    @can('restore', $account)
                                        <flux:button size="sm" variant="ghost" wire:click="restore({{ $account->id }})">{{ __('scf.accounting_engine.restore') }}</flux:button>
                                    @endcan
                                    @can('forceDelete', $account)
                                        <flux:button size="sm" variant="danger" wire:click="delete({{ $account->id }})" wire:confirm="{{ __('Are you sure?') }}">{{ __('Delete') }}</flux:button>
                                    @endcan
                                @else
                                    @can('update', $account)
                                        <flux:button size="sm" variant="ghost" :href="route('chart-of-accounts.edit', $account)" wire:navigate>{{ __('Edit') }}</flux:button>
                                    @endcan
                                    @can('delete', $account)
                                        <flux:button size="sm" variant="ghost" wire:click="archive({{ $account->id }})" wire:confirm="{{ __('scf.accounting_engine.confirm_archive_account') }}">{{ __('scf.accounting_engine.archive') }}</flux:button>
                                        <flux:button size="sm" variant="danger" wire:click="delete({{ $account->id }})" wire:confirm="{{ __('Are you sure?') }}">{{ __('Delete') }}</flux:button>
                                    @endcan
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-zinc-500">{{ __('scf.no_records') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t border-zinc-100 px-4 py-3 dark:border-zinc-800">{{ $this->accounts->links() }}</div>
    </div>
</section>

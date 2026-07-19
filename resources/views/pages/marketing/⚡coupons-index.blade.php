<?php

use App\Models\Coupon;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Coupons')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public ?int $couponToDelete = null;

    public bool $showDeleteModal = false;

    #[Computed]
    public function coupons()
    {
        return Coupon::query()
            
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('code', 'like', "%{$this->search}%")
                        ->orWhere('code', 'like', "%{$this->search}%")
                        ->orWhere('discount_type', 'like', "%{$this->search}%");
                });
            })
            
            ->latest()
            ->paginate(10);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $id): void
    {
        $this->couponToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function deleteCoupon(): void
    {
        if ($this->couponToDelete === null) {
            return;
        }

        $model = Coupon::query()->findOrFail($this->couponToDelete);


        $this->authorize('delete', $model);


        $model->delete();

        $this->couponToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Coupons deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Coupons')"
        :subtitle="__('Manage Coupons')"
    >
        <x-slot:actions>
            <flux:button :href="route('coupons.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search...')" />

    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->coupons">
            <flux:table.columns>
                <flux:table.column>{{ __('Code') }}</flux:table.column>
                <flux:table.column>{{ __('Discount Type') }}</flux:table.column>
                <flux:table.column>{{ __('Discount Value') }}</flux:table.column>
                <flux:table.column>{{ __('Valid From') }}</flux:table.column>
                <flux:table.column>{{ __('Valid Until') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->coupons as $coupon)
                    <flux:table.row wire:key="coupons-{{ $coupon->id }}">
                        <flux:table.cell>{{ $coupon->code ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $coupon->discount_type ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ number_format((float) $coupon->discount_value, 2) }}</flux:table.cell>
                        <flux:table.cell>{{ $coupon->valid_from?->format('Y-m-d') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $coupon->valid_until?->format('Y-m-d') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('coupons.edit', $coupon)" wire:navigate />
                                @can('delete', $coupon)
                                <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete({{ $coupon->id }})" />
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6">
                            <x-empty-state icon="inbox" :title="__('No records found.')" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal wire:model="showDeleteModal" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Delete') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Are you sure? This action cannot be undone.') }}</flux:text>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                <flux:button variant="danger" wire:click="deleteCoupon">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>

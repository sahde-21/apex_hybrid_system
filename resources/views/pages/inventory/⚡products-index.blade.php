<?php

use App\Concerns\ProductValidationRules;
use App\Models\Product;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Products')] class extends Component {
    use ProductValidationRules;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'low')]
    public bool $lowStockOnly = false;

    public ?int $productToDelete = null;

    public bool $showDeleteModal = false;

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, Product>
     */
    #[Computed]
    public function products()
    {
        return Product::query()
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('sku', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%");
                });
            })
            ->when($this->lowStockOnly, function ($query) {
                $query->whereColumn('stock_quantity', '<=', 'minimum_stock_level');
            })
            ->latest()
            ->paginate(10);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedLowStockOnly(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $productId): void
    {
        $this->productToDelete = $productId;
        $this->showDeleteModal = true;
    }

    public function deleteProduct(): void
    {
        if ($this->productToDelete === null) {
            return;
        }

        $model = Product::query()->findOrFail($this->productToDelete);
        $this->authorize('delete', $model);
        $model->delete();

        $this->productToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Product deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Products')"
        :subtitle="__('Manage products and inventory levels')"
    />

    <x-module-toolbar
        export-type="products"
        create-permission="products.create"
        :create-route="route('products.create')"
        :create-label="__('Add product')"
    >
        <x-slot:search>
            <flux:input class="min-w-64 flex-1" wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search by name, SKU, or description...')" />
        </x-slot:search>
        <x-slot:filters>
            <flux:checkbox wire:model.live="lowStockOnly" :label="__('Show low stock only')" />
        </x-slot:filters>
    </x-module-toolbar>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->products">
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('SKU / Barcode') }}</flux:table.column>
                <flux:table.column>{{ __('Purchase price') }}</flux:table.column>
                <flux:table.column>{{ __('Sale price') }}</flux:table.column>
                <flux:table.column>{{ __('Stock') }}</flux:table.column>
                <flux:table.column>{{ __('Min. level') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->products as $product)
                    <flux:table.row wire:key="product-{{ $product->id }}">
                        <flux:table.cell>
                            <div class="font-medium">{{ $product->name }}</div>
                            @if ($product->description)
                                <flux:text class="mt-1 max-w-xs truncate text-sm">{{ $product->description }}</flux:text>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>{{ $product->sku }}</flux:table.cell>
                        <flux:table.cell>{{ number_format((float) $product->purchase_price, 2) }}</flux:table.cell>
                        <flux:table.cell>{{ number_format((float) $product->sale_price, 2) }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <span>{{ $product->stock_quantity }}</span>
                                @if ($product->isLowStock())
                                    <flux:badge size="sm" color="amber">{{ __('Low stock') }}</flux:badge>
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>{{ $product->minimum_stock_level }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                @can('update', $product)
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        icon="pencil-square"
                                        :href="route('products.edit', $product)"
                                        wire:navigate
                                    />
                                @endcan
                                @can('delete', $product)
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        icon="trash"
                                        wire:click="confirmDelete({{ $product->id }})"
                                    />
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7">
                            <x-empty-state icon="inbox" :title="__('No products found.')" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal wire:model="showDeleteModal" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Delete product') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Are you sure you want to delete this product? This action cannot be undone.') }}</flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="deleteProduct">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>

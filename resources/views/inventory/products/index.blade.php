<x-layouts::app :title="__('Products')">
    <section class="w-full">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="xl">{{ __('Products') }}</flux:heading>
                <flux:subheading>{{ __('Manage products and inventory levels') }}</flux:subheading>
            </div>

            <flux:button :href="route('products.create')" icon="plus" variant="primary" wire:navigate>
                {{ __('Add product') }}
            </flux:button>
        </div>

        @if (session('status'))
            <flux:callout variant="success" class="mt-6" icon="check-circle">
                {{ session('status') }}
            </flux:callout>
        @endif

        <form method="GET" action="{{ route('products.index') }}" class="mt-6 grid gap-4 md:grid-cols-2">
            <flux:input
                name="q"
                value="{{ request('q') }}"
                icon="magnifying-glass"
                :placeholder="__('Search by name, SKU, or description...')"
            />

            <div class="flex items-end gap-4">
                <flux:checkbox
                    name="low_stock"
                    value="1"
                    :label="__('Show low stock only')"
                    :checked="request()->boolean('low_stock')"
                />

                <flux:button type="submit" variant="primary">{{ __('Filter') }}</flux:button>
            </div>
        </form>

        <div class="mt-6">
            <flux:table :paginate="$products">
                <flux:table.columns>
                    <flux:table.column>{{ __('Name') }}</flux:table.column>
                    <flux:table.column>{{ __('SKU') }}</flux:table.column>
                    <flux:table.column>{{ __('Purchase price') }}</flux:table.column>
                    <flux:table.column>{{ __('Sale price') }}</flux:table.column>
                    <flux:table.column>{{ __('Stock') }}</flux:table.column>
                    <flux:table.column>{{ __('Min. level') }}</flux:table.column>
                    <flux:table.column>{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($products as $product)
                        <flux:table.row>
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
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        icon="pencil-square"
                                        :href="route('products.edit', $product)"
                                        wire:navigate
                                    />

                                    <form method="POST" action="{{ route('products.destroy', $product) }}">
                                        @csrf
                                        @method('DELETE')
                                        <flux:button
                                            size="sm"
                                            variant="ghost"
                                            icon="trash"
                                            type="submit"
                                            onclick="return confirm(@js(__('Are you sure you want to delete this product?')))"
                                        />
                                    </form>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="7">
                                <div class="py-8 text-center">
                                    <flux:text>{{ __('No products found.') }}</flux:text>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </section>
</x-layouts::app>

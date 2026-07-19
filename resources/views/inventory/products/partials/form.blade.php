<form method="POST" action="{{ $action }}" class="w-full max-w-4xl space-y-5">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="scf-card grid gap-5 md:grid-cols-2">
        <flux:field>
            <flux:label>{{ __('Name') }}</flux:label>
            <flux:input
                name="name"
                type="text"
                value="{{ old('name', $product?->name) }}"
                required
                autofocus
            />
            <flux:error name="name" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('SKU') }}</flux:label>
            <flux:input
                name="sku"
                type="text"
                value="{{ old('sku', $product?->sku) }}"
                required
            />
            <flux:error name="sku" />
        </flux:field>

        <div class="md:col-span-2">
            <flux:field>
                <flux:label>{{ __('Description') }}</flux:label>
                <flux:textarea name="description" rows="3">{{ old('description', $product?->description) }}</flux:textarea>
                <flux:error name="description" />
            </flux:field>
        </div>

        <flux:field>
            <flux:label>{{ __('Purchase price') }}</flux:label>
            <flux:input
                name="purchase_price"
                type="number"
                step="0.01"
                min="0"
                value="{{ old('purchase_price', $product?->purchase_price) }}"
                required
            />
            <flux:error name="purchase_price" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Sale price') }}</flux:label>
            <flux:input
                name="sale_price"
                type="number"
                step="0.01"
                min="0"
                value="{{ old('sale_price', $product?->sale_price) }}"
                required
            />
            <flux:error name="sale_price" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Stock quantity') }}</flux:label>
            <flux:input
                name="stock_quantity"
                type="number"
                min="0"
                value="{{ old('stock_quantity', $product?->stock_quantity ?? 0) }}"
                required
            />
            <flux:error name="stock_quantity" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Minimum stock level') }}</flux:label>
            <flux:input
                name="minimum_stock_level"
                type="number"
                min="0"
                value="{{ old('minimum_stock_level', $product?->minimum_stock_level ?? 0) }}"
                required
            />
            <flux:error name="minimum_stock_level" />
        </flux:field>
    </div>

    <div class="flex flex-wrap items-center justify-end gap-2">
        <flux:button :href="route('products.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        <flux:button variant="primary" type="submit" icon="check">{{ $submitLabel }}</flux:button>
    </div>
</form>

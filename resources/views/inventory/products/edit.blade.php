<x-layouts::app :title="__('Edit product')">
    <section class="w-full">
        <div class="mb-6">
            <flux:heading size="xl">{{ __('Edit product') }}</flux:heading>
            <flux:subheading>{{ __('Update product and inventory details') }}</flux:subheading>
        </div>

        @include('inventory.products.partials.form', [
            'product' => $product,
            'action' => route('products.update', $product),
            'method' => 'PUT',
            'submitLabel' => __('Save changes'),
        ])
    </section>
</x-layouts::app>

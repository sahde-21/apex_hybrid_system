<x-layouts::app :title="__('Create product')">
    <section class="w-full">
        <div class="mb-6">
            <flux:heading size="xl">{{ __('Create product') }}</flux:heading>
            <flux:subheading>{{ __('Add a new product to your inventory') }}</flux:subheading>
        </div>

        @include('inventory.products.partials.form', [
            'product' => null,
            'action' => route('products.store'),
            'method' => 'POST',
            'submitLabel' => __('Create product'),
        ])
    </section>
</x-layouts::app>

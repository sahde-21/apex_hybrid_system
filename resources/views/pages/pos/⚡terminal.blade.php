<?php

use App\Enums\PosPaymentMethod;
use App\Models\Contact;
use App\Models\Coupon;
use App\Models\PosFavorite;
use App\Models\PosRegister;
use App\Models\PosSale;
use App\Models\PosShift;
use App\Services\Pos\PosCatalogService;
use App\Services\Pos\PosCheckoutService;
use App\Services\Pos\PosPricingService;
use App\Services\Pos\PosRefundService;
use App\Services\Pos\PosShiftService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts.pos')] #[Title('Point of Sale')] class extends Component {
    #[Url]
    public string $search = '';

    public ?int $categoryId = null;

    public bool $favoritesOnly = false;

    public ?int $registerId = null;

    public ?int $shiftId = null;

    public string $openingFloat = '100';

    public string $closingCash = '0';

    public string $scanCode = '';

    /** @var list<array{key: string, product_id: int|null, variant_id: int|null, name: string, sku: string|null, barcode: string|null, quantity: int, unit_price: float, discount_amount: float, tax_rate: float}> */
    public array $cart = [];

    public ?int $contactId = null;

    public string $customerSearch = '';

    public string $newCustomerName = '';

    public string $newCustomerPhone = '';

    public string $couponCode = '';

    public string $cartDiscount = '0';

    public string $loyaltyPoints = '0';

    public string $taxRate = '0';

    public bool $showPayment = false;

    public bool $showCustomer = false;

    public bool $showCloseShift = false;

    public bool $showRefund = false;

    public ?int $refundSaleId = null;

    /** @var list<array{method: string, amount: string, gift_card_code: string, reference: string}> */
    public array $payments = [
        ['method' => 'cash', 'amount' => '', 'gift_card_code' => '', 'reference' => ''],
    ];

    public ?int $lastSaleId = null;

    public string $offlineNotice = '';

    public function mount(PosCatalogService $catalog): void
    {
        $this->taxRate = (string) $catalog->defaultTaxRate();

        $register = PosRegister::query()->where('is_active', true)->orderBy('name')->first();
        if ($register) {
            $this->registerId = $register->id;
            $shift = $register->openShift();
            if ($shift && $shift->user_id === Auth::id()) {
                $this->shiftId = $shift->id;
            }
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, PosRegister>
     */
    #[Computed]
    public function registers()
    {
        return PosRegister::query()->where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function currentShift(): ?PosShift
    {
        if (! $this->shiftId) {
            return null;
        }

        return PosShift::query()->with('register')->find($this->shiftId);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function products()
    {
        $catalog = app(PosCatalogService::class);
        $items = $catalog->search($this->search, $this->categoryId, Auth::id());

        if ($this->favoritesOnly) {
            return $items->filter(fn (array $p) => $p['is_favorite'])->values();
        }

        return $items;
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\ProductCategory>
     */
    #[Computed]
    public function categories()
    {
        return app(PosCatalogService::class)->categories();
    }

    /**
     * @return \Illuminate\Support\Collection<int, Contact>
     */
    #[Computed]
    public function customers()
    {
        return Contact::query()
            ->whereIn('type', ['customer', 'both'])
            ->when($this->customerSearch, function ($q): void {
                $term = '%'.$this->customerSearch.'%';
                $q->where(function ($inner) use ($term): void {
                    $inner->where('name', 'like', $term)
                        ->orWhere('phone', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get();
    }

    /**
     * @return array{subtotal: float, item_discount: float, coupon_discount: float, discount_total: float, tax: float, total: float, lines: list<array{unit_price: float, quantity: int, discount_amount: float, tax_rate: float, tax_amount: float, line_total: float}>}
     */
    #[Computed]
    public function totals(): array
    {
        $pricing = app(PosPricingService::class);
        $coupon = $this->couponCode
            ? Coupon::query()->where('code', $this->couponCode)->first()
            : null;

        try {
            return $pricing->calculate(
                $this->cart,
                (float) $this->cartDiscount,
                $coupon && $coupon->isRedeemable() ? $coupon : null,
                (float) $this->taxRate,
            );
        } catch (\Throwable) {
            return $pricing->calculate($this->cart, (float) $this->cartDiscount, null, (float) $this->taxRate);
        }
    }

    public function updatedSearch(): void
    {
        unset($this->products);
    }

    public function updatedCategoryId(): void
    {
        unset($this->products);
    }

    public function selectCategory(?int $id): void
    {
        $this->categoryId = $id;
        $this->favoritesOnly = false;
        unset($this->products);
    }

    public function toggleFavorites(): void
    {
        $this->favoritesOnly = ! $this->favoritesOnly;
        unset($this->products);
    }

    public function openShift(PosShiftService $shifts): void
    {
        $this->authorize('openShift', PosSale::class);

        $register = PosRegister::query()->findOrFail($this->registerId);

        try {
            $shift = $shifts->open($register, Auth::user(), (float) $this->openingFloat);
            $this->shiftId = $shift->id;
            Flux::toast(variant: 'success', text: __('Shift opened.'));
        } catch (\Throwable $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());
        }
    }

    public function closeShift(PosShiftService $shifts): void
    {
        $this->authorize('closeShift', PosSale::class);

        $shift = $this->currentShift;
        if (! $shift) {
            return;
        }

        try {
            $shifts->close($shift, (float) $this->closingCash);
            $this->shiftId = null;
            $this->showCloseShift = false;
            Flux::toast(variant: 'success', text: __('Shift closed.'));
        } catch (\Throwable $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());
        }
    }

    public function scan(PosCatalogService $catalog): void
    {
        $item = $catalog->findByScan($this->scanCode);
        $this->scanCode = '';

        if (! $item) {
            Flux::toast(variant: 'danger', text: __('Product not found.'));

            return;
        }

        $this->addMappedItem($item);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public function addMappedItem(array $item): void
    {
        $key = ($item['variant_id'] ?? 'p').'-'.($item['product_id'] ?? 0).'-'.($item['variant_id'] ?? 0);

        foreach ($this->cart as $index => $line) {
            if ($line['key'] === $key) {
                $this->cart[$index]['quantity']++;
                $this->persistCartOffline();

                return;
            }
        }

        $this->cart[] = [
            'key' => $key,
            'product_id' => $item['product_id'] ?? null,
            'variant_id' => $item['variant_id'] ?? null,
            'name' => $item['name'],
            'sku' => $item['sku'] ?? null,
            'barcode' => $item['barcode'] ?? null,
            'quantity' => 1,
            'unit_price' => (float) $item['sale_price'],
            'discount_amount' => 0,
            'tax_rate' => (float) $this->taxRate,
        ];

        $this->persistCartOffline();
    }

    public function addProduct(int $productId, PosCatalogService $catalog): void
    {
        $product = \App\Models\Product::query()->with('category')->find($productId);
        if (! $product) {
            return;
        }

        $this->addMappedItem($catalog->mapProduct($product, true));
    }

    public function updateQty(string $key, int $quantity): void
    {
        foreach ($this->cart as $index => $line) {
            if ($line['key'] === $key) {
                if ($quantity <= 0) {
                    unset($this->cart[$index]);
                    $this->cart = array_values($this->cart);
                } else {
                    $this->cart[$index]['quantity'] = $quantity;
                }
                $this->persistCartOffline();

                return;
            }
        }
    }

    public function removeLine(string $key): void
    {
        $this->cart = array_values(array_filter($this->cart, fn ($line) => $line['key'] !== $key));
        $this->persistCartOffline();
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->couponCode = '';
        $this->cartDiscount = '0';
        $this->loyaltyPoints = '0';
        $this->payments = [
            ['method' => 'cash', 'amount' => '', 'gift_card_code' => '', 'reference' => ''],
        ];
        $this->persistCartOffline();
    }

    public function toggleFavorite(int $productId): void
    {
        $existing = PosFavorite::query()
            ->where('user_id', Auth::id())
            ->where('product_id', $productId)
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            PosFavorite::query()->create([
                'user_id' => Auth::id(),
                'product_id' => $productId,
            ]);
        }

        unset($this->products);
    }

    public function createCustomer(PosCheckoutService $checkout): void
    {
        $this->validate([
            'newCustomerName' => ['required', 'string', 'max:255'],
            'newCustomerPhone' => ['nullable', 'string', 'max:50'],
        ]);

        $contact = $checkout->quickCreateCustomer(
            Auth::user(),
            $this->newCustomerName,
            $this->newCustomerPhone ?: null,
        );

        $this->contactId = $contact->id;
        $this->newCustomerName = '';
        $this->newCustomerPhone = '';
        $this->showCustomer = false;
        Flux::toast(variant: 'success', text: __('Customer created.'));
    }

    public function addPaymentLine(): void
    {
        $this->payments[] = ['method' => 'card', 'amount' => '', 'gift_card_code' => '', 'reference' => ''];
    }

    public function removePaymentLine(int $index): void
    {
        unset($this->payments[$index]);
        $this->payments = array_values($this->payments);
    }

    public function preparePayment(): void
    {
        if ($this->cart === []) {
            Flux::toast(variant: 'danger', text: __('Cart is empty.'));

            return;
        }

        $total = $this->totals['total'];
        $this->payments = [
            ['method' => 'cash', 'amount' => (string) $total, 'gift_card_code' => '', 'reference' => ''],
        ];
        $this->showPayment = true;
    }

    public function checkout(PosCheckoutService $checkout): void
    {
        $this->authorize('create', PosSale::class);

        $shift = $this->currentShift;
        if (! $shift) {
            Flux::toast(variant: 'danger', text: __('Open a shift first.'));

            return;
        }

        $paymentPayload = collect($this->payments)
            ->filter(fn ($p) => (float) ($p['amount'] ?? 0) > 0)
            ->map(fn ($p) => [
                'method' => $p['method'],
                'amount' => (float) $p['amount'],
                'gift_card_code' => $p['gift_card_code'] ?: null,
                'reference' => $p['reference'] ?: null,
            ])
            ->values()
            ->all();

        try {
            $sale = $checkout->checkout(
                shift: $shift,
                user: Auth::user(),
                items: $this->cart,
                payments: $paymentPayload,
                contactId: $this->contactId,
                cartDiscount: (float) $this->cartDiscount,
                couponCode: $this->couponCode ?: null,
                loyaltyPointsToRedeem: (float) $this->loyaltyPoints,
            );

            $this->lastSaleId = $sale->id;
            $this->showPayment = false;
            $this->clearCart();

            if ($sale->cash_drawer_opened) {
                $this->dispatch('pos-open-cash-drawer');
            }

            Flux::toast(variant: 'success', text: __('Sale completed: :ref', ['ref' => $sale->reference_number]));
            $this->dispatch('pos-print-receipt', saleId: $sale->id);
        } catch (\Throwable $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());
        }
    }

    public function refundLast(PosRefundService $refunds): void
    {
        if (! $this->lastSaleId && ! $this->refundSaleId) {
            return;
        }

        $sale = PosSale::query()->find($this->refundSaleId ?? $this->lastSaleId);
        if (! $sale) {
            return;
        }

        $this->authorize('refund', $sale);

        try {
            $refund = $refunds->refund($sale, Auth::user());
            Flux::toast(variant: 'success', text: __('Refund completed: :ref', ['ref' => $refund->reference_number]));
            $this->showRefund = false;
            $this->refundSaleId = null;
        } catch (\Throwable $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());
        }
    }

    public function restoreOfflineCart(array $cart): void
    {
        $this->cart = $cart;
    }

    protected function persistCartOffline(): void
    {
        $this->dispatch('pos-persist-cart', cart: $this->cart);
    }
}; ?>

<div
    class="flex min-h-screen flex-col"
    x-data="posTerminal({
        initialCart: @js($cart),
        printUrlBase: @js(url('/print/pos-sale')),
    })"
    x-on:keydown.window="onKey($event)"
    x-on:pos-persist-cart.window="persistCart($event.detail.cart ?? $event.detail[0]?.cart ?? [])"
    x-on:pos-print-receipt.window="printReceipt($event.detail.saleId ?? $event.detail[0]?.saleId)"
    x-on:pos-open-cash-drawer.window="kickDrawer()"
>
    <header class="sticky top-0 z-30 flex flex-wrap items-center gap-3 border-b border-zinc-200 bg-white/95 px-3 py-2 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/95 sm:px-4">
        <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-sky-600" wire:navigate>{{ __('scf.app_short') }}</a>
        <div class="text-lg font-semibold tracking-tight">{{ __('scf.pos') }}</div>

        <div class="ms-auto flex flex-wrap items-center gap-2">
            <span
                class="rounded-full px-2.5 py-1 text-xs font-medium"
                :class="online ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200'"
                x-text="online ? @js(__('Online')) : @js(__('Offline'))"
            ></span>

            @if ($this->currentShift)
                <span class="rounded-full bg-sky-100 px-2.5 py-1 text-xs font-medium text-sky-800 dark:bg-sky-900/40 dark:text-sky-200">
                    {{ $this->currentShift->register?->name }} · {{ __('Shift open') }}
                </span>
                <flux:button size="sm" variant="ghost" wire:click="$set('showCloseShift', true)">{{ __('Close shift') }}</flux:button>
            @endif

            <flux:button size="sm" variant="ghost" :href="route('pos.sales.index')" wire:navigate>{{ __('Sales') }}</flux:button>
            <flux:button size="sm" variant="ghost" :href="route('pos.summary')" wire:navigate>{{ __('Summary') }}</flux:button>
        </div>
    </header>

    @unless ($this->currentShift)
        <div class="mx-auto flex w-full max-w-xl flex-1 flex-col justify-center gap-4 p-6">
            <flux:heading size="xl">{{ __('Open POS shift') }}</flux:heading>
            <flux:subheading>{{ __('Select a register and opening cash float to begin.') }}</flux:subheading>
            <flux:select wire:model="registerId" :label="__('Register')">
                @foreach ($this->registers as $register)
                    <flux:select.option :value="$register->id">{{ $register->name }} ({{ $register->code }})</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="openingFloat" type="number" step="0.01" :label="__('Opening float')" />
            <flux:button variant="primary" wire:click="openShift" class="w-full">{{ __('Open shift') }}</flux:button>
            @if ($this->registers->isEmpty())
                <flux:callout variant="warning">{{ __('No active registers. Create one under POS registers.') }}</flux:callout>
                <flux:button :href="route('pos.registers.index')" wire:navigate>{{ __('Manage registers') }}</flux:button>
            @endif
        </div>
    @else
        <div class="grid flex-1 grid-cols-1 gap-0 lg:grid-cols-12">
            {{-- Catalog --}}
            <section class="flex flex-col border-b border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950 lg:col-span-7 lg:border-b-0 lg:border-e">
                <div class="flex flex-col gap-3 border-b border-zinc-100 p-3 dark:border-zinc-800 sm:p-4">
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <flux:input
                            wire:model.live.debounce.250ms="search"
                            :placeholder="__('Search products, SKU, barcode…')"
                            icon="magnifying-glass"
                            class="flex-1"
                        />
                        <form wire:submit="scan" class="flex gap-2">
                            <flux:input wire:model="scanCode" :placeholder="__('Scan barcode / QR')" class="min-w-[10rem]" />
                            <flux:button type="submit" variant="filled">{{ __('Add') }}</flux:button>
                        </form>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            wire:click="selectCategory(null)"
                            @class([
                                'rounded-lg px-3 py-2 text-sm font-medium touch-manipulation',
                                'bg-sky-600 text-white' => $categoryId === null && ! $favoritesOnly,
                                'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200' => ! ($categoryId === null && ! $favoritesOnly),
                            ])
                        >{{ __('All') }}</button>
                        <button
                            type="button"
                            wire:click="toggleFavorites"
                            @class([
                                'rounded-lg px-3 py-2 text-sm font-medium touch-manipulation',
                                'bg-amber-500 text-white' => $favoritesOnly,
                                'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200' => ! $favoritesOnly,
                            ])
                        >{{ __('Favorites') }}</button>
                        @foreach ($this->categories as $category)
                            <button
                                type="button"
                                wire:click="selectCategory({{ $category->id }})"
                                @class([
                                    'rounded-lg px-3 py-2 text-sm font-medium touch-manipulation',
                                    'bg-sky-600 text-white' => $categoryId === $category->id && ! $favoritesOnly,
                                    'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200' => ! ($categoryId === $category->id && ! $favoritesOnly),
                                ])
                            >{{ $category->name }}</button>
                        @endforeach
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 overflow-y-auto p-3 sm:grid-cols-3 sm:p-4 xl:grid-cols-4" style="max-height: calc(100vh - 9rem);">
                    @forelse ($this->products as $product)
                        <button
                            type="button"
                            wire:click="addProduct({{ $product['product_id'] }})"
                            class="group relative flex min-h-[7.5rem] flex-col rounded-xl border border-zinc-200 bg-zinc-50 p-3 text-start transition hover:border-sky-400 hover:bg-sky-50 touch-manipulation dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-sky-500 dark:hover:bg-zinc-800"
                        >
                            <span class="line-clamp-2 text-sm font-semibold text-zinc-900 dark:text-zinc-50">{{ $product['name'] }}</span>
                            <span class="mt-1 text-xs text-zinc-500">{{ $product['sku'] }}</span>
                            <span class="mt-auto pt-2 text-base font-bold text-sky-700 dark:text-sky-300">{{ number_format($product['sale_price'], 2) }}</span>
                            <span class="text-[11px] text-zinc-500">{{ __('Stock') }}: {{ $product['stock_quantity'] }}</span>
                            <span
                                role="button"
                                wire:click.stop="toggleFavorite({{ $product['product_id'] }})"
                                class="absolute end-2 top-2 text-lg {{ $product['is_favorite'] ? 'text-amber-500' : 'text-zinc-300 group-hover:text-amber-400' }}"
                            >★</span>
                        </button>
                    @empty
                        <div class="col-span-full py-16 text-center text-zinc-500">{{ __('No products found.') }}</div>
                    @endforelse
                </div>
            </section>

            {{-- Cart --}}
            <aside class="flex flex-col bg-zinc-50 dark:bg-zinc-900/50 lg:col-span-5">
                <div class="flex items-center justify-between gap-2 border-b border-zinc-200 p-3 dark:border-zinc-800">
                    <div>
                        <div class="text-sm font-semibold">{{ __('Cart') }}</div>
                        <button type="button" class="text-xs text-sky-600" wire:click="$set('showCustomer', true)">
                            {{ $contactId ? ($this->customers->firstWhere('id', $contactId)?->name ?? __('Customer')) : __('Select customer') }}
                        </button>
                    </div>
                    <div class="flex gap-1">
                        <flux:button size="sm" variant="ghost" wire:click="clearCart">{{ __('Clear') }}</flux:button>
                    </div>
                </div>

                <div class="flex-1 space-y-2 overflow-y-auto p-3" style="max-height: calc(100vh - 22rem);">
                    @forelse ($cart as $line)
                        <div class="rounded-xl border border-zinc-200 bg-white p-3 dark:border-zinc-800 dark:bg-zinc-950">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <div class="text-sm font-semibold">{{ $line['name'] }}</div>
                                    <div class="text-xs text-zinc-500">{{ number_format($line['unit_price'], 2) }}</div>
                                </div>
                                <button type="button" class="text-xs text-red-500" wire:click="removeLine('{{ $line['key'] }}')">{{ __('Remove') }}</button>
                            </div>
                            <div class="mt-2 flex items-center gap-2">
                                <flux:button size="sm" wire:click="updateQty('{{ $line['key'] }}', {{ $line['quantity'] - 1 }})">−</flux:button>
                                <span class="min-w-8 text-center font-semibold">{{ $line['quantity'] }}</span>
                                <flux:button size="sm" wire:click="updateQty('{{ $line['key'] }}', {{ $line['quantity'] + 1 }})">+</flux:button>
                                <span class="ms-auto font-semibold">{{ number_format($line['unit_price'] * $line['quantity'], 2) }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="py-10 text-center text-sm text-zinc-500">{{ __('Scan or tap products to add to cart.') }}</div>
                    @endforelse
                </div>

                <div class="space-y-3 border-t border-zinc-200 bg-white p-3 dark:border-zinc-800 dark:bg-zinc-950">
                    <div class="grid grid-cols-2 gap-2">
                        <flux:input wire:model.live="couponCode" :label="__('Coupon')" />
                        <flux:input wire:model.live="cartDiscount" type="number" step="0.01" :label="__('Discount')" />
                    </div>
                    <div class="space-y-1 text-sm">
                        <div class="flex justify-between"><span>{{ __('Subtotal') }}</span><span>{{ number_format($this->totals['subtotal'], 2) }}</span></div>
                        <div class="flex justify-between"><span>{{ __('Discount') }}</span><span>{{ number_format($this->totals['discount_total'], 2) }}</span></div>
                        <div class="flex justify-between"><span>{{ __('Tax') }}</span><span>{{ number_format($this->totals['tax'], 2) }}</span></div>
                        <div class="flex justify-between text-lg font-bold"><span>{{ __('Total') }}</span><span>{{ number_format($this->totals['total'], 2) }}</span></div>
                    </div>
                    <flux:button variant="primary" class="w-full !py-3 text-base" wire:click="preparePayment" :disabled="count($cart) === 0">
                        {{ __('Pay') }} · {{ number_format($this->totals['total'], 2) }}
                    </flux:button>
                    <p class="text-center text-[11px] text-zinc-500">
                        {{ __('Shortcuts') }}: F2 {{ __('Pay') }} · F3 {{ __('Search') }} · Esc {{ __('Clear') }}
                    </p>
                </div>
            </aside>
        </div>
    @endunless

    <flux:modal wire:model="showPayment" class="max-w-lg">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Payment') }}</flux:heading>
            <flux:subheading>{{ __('Total due') }}: <strong>{{ number_format($this->totals['total'], 2) }}</strong></flux:subheading>

            @foreach ($payments as $index => $payment)
                <div class="grid gap-2 rounded-xl border border-zinc-200 p-3 dark:border-zinc-800 sm:grid-cols-2">
                    <flux:select wire:model="payments.{{ $index }}.method" :label="__('Method')">
                        @foreach (PosPaymentMethod::options() as $value => $label)
                            <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="payments.{{ $index }}.amount" type="number" step="0.01" :label="__('Amount')" />
                    @if (($payment['method'] ?? '') === 'gift_card')
                        <flux:input wire:model="payments.{{ $index }}.gift_card_code" :label="__('Gift card code')" class="sm:col-span-2" />
                    @endif
                    @if (count($payments) > 1)
                        <flux:button size="sm" variant="danger" wire:click="removePaymentLine({{ $index }})" class="sm:col-span-2">{{ __('Remove') }}</flux:button>
                    @endif
                </div>
            @endforeach

            <flux:button variant="ghost" wire:click="addPaymentLine">{{ __('Add split payment') }}</flux:button>
            <flux:input wire:model="loyaltyPoints" type="number" step="0.01" :label="__('Redeem loyalty points')" />

            <div class="flex gap-2">
                <flux:button variant="primary" class="flex-1" wire:click="checkout">{{ __('Complete sale') }}</flux:button>
                <flux:button variant="ghost" wire:click="$set('showPayment', false)">{{ __('Cancel') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model="showCustomer" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Customer') }}</flux:heading>
            <flux:input wire:model.live.debounce.250ms="customerSearch" :placeholder="__('Search customers…')" />
            <div class="max-h-48 space-y-1 overflow-y-auto">
                <button type="button" class="block w-full rounded-lg px-3 py-2 text-start hover:bg-zinc-100 dark:hover:bg-zinc-800" wire:click="$set('contactId', null); $set('showCustomer', false)">
                    {{ __('Walk-in customer') }}
                </button>
                @foreach ($this->customers as $customer)
                    <button type="button" class="block w-full rounded-lg px-3 py-2 text-start hover:bg-zinc-100 dark:hover:bg-zinc-800" wire:click="$set('contactId', {{ $customer->id }}); $set('showCustomer', false)">
                        <div class="font-medium">{{ $customer->name }}</div>
                        <div class="text-xs text-zinc-500">{{ $customer->phone }}</div>
                    </button>
                @endforeach
            </div>
            <div class="border-t border-zinc-200 pt-3 dark:border-zinc-800">
                <flux:heading size="sm">{{ __('Quick create') }}</flux:heading>
                <div class="mt-2 space-y-2">
                    <flux:input wire:model="newCustomerName" :label="__('Name')" />
                    <flux:input wire:model="newCustomerPhone" :label="__('Phone')" />
                    <flux:button variant="primary" wire:click="createCustomer">{{ __('Create customer') }}</flux:button>
                </div>
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model="showCloseShift" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Close shift') }}</flux:heading>
            @if ($this->currentShift)
                @php($summary = app(\App\Services\Pos\PosShiftService::class)->summary($this->currentShift))
                <div class="space-y-1 text-sm">
                    <div class="flex justify-between"><span>{{ __('Sales') }}</span><span>{{ $summary['sales_count'] }}</span></div>
                    <div class="flex justify-between"><span>{{ __('Net sales') }}</span><span>{{ number_format($summary['net_sales'], 2) }}</span></div>
                    <div class="flex justify-between"><span>{{ __('Cash') }}</span><span>{{ number_format($summary['cash_sales'], 2) }}</span></div>
                </div>
            @endif
            <flux:input wire:model="closingCash" type="number" step="0.01" :label="__('Counted cash')" />
            <div class="flex gap-2">
                <flux:button variant="primary" wire:click="closeShift">{{ __('Close shift') }}</flux:button>
                <flux:button variant="ghost" wire:click="$set('showCloseShift', false)">{{ __('Cancel') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    <script>
        function posTerminal(config) {
            return {
                online: navigator.onLine,
                cartKey: 'scf-pos-cart',
                init() {
                    window.addEventListener('online', () => this.online = true);
                    window.addEventListener('offline', () => this.online = false);
                    const saved = localStorage.getItem(this.cartKey);
                    if (saved && (!config.initialCart || config.initialCart.length === 0)) {
                        try {
                            const cart = JSON.parse(saved);
                            if (Array.isArray(cart) && cart.length) {
                                @this.call('restoreOfflineCart', cart);
                            }
                        } catch (e) {}
                    }
                },
                persistCart(cart) {
                    try { localStorage.setItem(this.cartKey, JSON.stringify(cart ?? [])); } catch (e) {}
                },
                printReceipt(saleId) {
                    if (!saleId) return;
                    window.open(`${config.printUrlBase}/${saleId}?layout=thermal_80mm`, '_blank');
                },
                kickDrawer() {
                    window.dispatchEvent(new CustomEvent('scf-cash-drawer'));
                    console.info('Cash drawer open signal dispatched');
                },
                onKey(e) {
                    if (e.key === 'F2') { e.preventDefault(); @this.call('preparePayment'); }
                    if (e.key === 'Escape') { e.preventDefault(); @this.call('clearCart'); }
                }
            }
        }
    </script>
</div>

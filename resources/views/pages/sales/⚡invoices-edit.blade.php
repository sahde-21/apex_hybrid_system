<?php

use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\SaleOrder;
use App\Services\Sales\InvoiceWorkflowService;
use App\Support\Sales\DocumentLineCalculator;
use Flux\Flux;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit invoice')] class extends Component {
    public Invoice $invoice;

    public string $reference_number = '';
    public ?int $contact_id = null;
    public ?int $sale_order_id = null;
    public string $invoice_date = '';
    public string $due_date = '';
    public string $currency_code = '';
    public string $payment_terms = '';
    public string $notes = '';

    /** @var list<array{product_id:?int,description:string,quantity:string,unit_price:string,discount_amount:string,tax_amount:string}> */
    public array $lines = [];

    public function mount(Invoice $invoice): void
    {
        $this->authorize('update', $invoice);

        if (! $invoice->status->isEditable()) {
            $this->redirect(route('invoices.show', $invoice), navigate: true);
            return;
        }

        $this->invoice = $invoice->load('lines.product');
        $this->reference_number = $invoice->reference_number;
        $this->contact_id = $invoice->contact_id;
        $this->sale_order_id = $invoice->sale_order_id;
        $this->invoice_date = $invoice->invoice_date->format('Y-m-d');
        $this->due_date = $invoice->due_date?->format('Y-m-d') ?? '';
        $this->currency_code = $invoice->currency_code;
        $this->payment_terms = $invoice->payment_terms ?? '';
        $this->notes = $invoice->notes ?? '';

        $this->lines = $invoice->lines->map(fn ($line) => [
            'product_id' => $line->product_id,
            'description' => $line->description ?? '',
            'quantity' => (string) $line->quantity,
            'unit_price' => (string) $line->unit_price,
            'discount_amount' => (string) $line->discount_amount,
            'tax_amount' => (string) $line->tax_amount,
        ])->toArray();

        if (empty($this->lines)) {
            $this->lines = [['product_id' => null, 'description' => '', 'quantity' => '1', 'unit_price' => '0', 'discount_amount' => '0', 'tax_amount' => '0']];
        }
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, Contact> */
    #[Computed]
    public function contacts()
    {
        return Contact::query()->orderBy('name')->get();
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, SaleOrder> */
    #[Computed]
    public function saleOrders()
    {
        return SaleOrder::query()->orderBy('reference_number')->limit(200)->get();
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, Product> */
    #[Computed]
    public function products()
    {
        return Product::query()->orderBy('name')->limit(200)->get();
    }

    #[Computed]
    public function totals(): array
    {
        return DocumentLineCalculator::summarize($this->lines);
    }

    public function addLine(): void
    {
        $this->lines[] = ['product_id' => null, 'description' => '', 'quantity' => '1', 'unit_price' => '0', 'discount_amount' => '0', 'tax_amount' => '0'];
    }

    public function removeLine(int $index): void
    {
        if (count($this->lines) > 1) {
            array_splice($this->lines, $index, 1);
        }
    }

    public function updatedLines($value, $key): void
    {
        if (str_ends_with($key, '.product_id') && $value) {
            $idx = (int) explode('.', $key)[0];
            $product = Product::find($value);
            if ($product && empty(trim($this->lines[$idx]['description']))) {
                $this->lines[$idx]['description'] = $product->name;
                $this->lines[$idx]['unit_price'] = (string) ($product->selling_price ?? $product->price ?? 0);
            }
        }
        unset($this->totals);
    }

    public function save(): void
    {
        $this->validate([
            'reference_number' => 'required|string|max:255',
            'contact_id' => 'nullable|integer|exists:contacts,id',
            'sale_order_id' => 'nullable|integer|exists:sale_orders,id',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:invoice_date',
            'currency_code' => 'required|string|max:10',
            'payment_terms' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:5000',
            'lines' => 'required|array|min:1',
            'lines.*.product_id' => 'nullable|integer|exists:products,id',
            'lines.*.description' => 'nullable|string|max:500',
            'lines.*.quantity' => 'required|numeric|min:0',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.discount_amount' => 'required|numeric|min:0',
            'lines.*.tax_amount' => 'required|numeric|min:0',
        ]);

        try {
            $invoice = app(InvoiceWorkflowService::class)->update($this->invoice, auth()->user(), [
                'reference_number' => $this->reference_number,
                'contact_id' => $this->contact_id,
                'sale_order_id' => $this->sale_order_id,
                'invoice_date' => $this->invoice_date,
                'due_date' => $this->due_date ?: null,
                'currency_code' => $this->currency_code,
                'payment_terms' => $this->payment_terms ?: null,
                'notes' => $this->notes ?: null,
            ], $this->lines);

            Flux::toast(variant: 'success', text: __('Invoice updated successfully.'));
            $this->redirect(route('invoices.show', $invoice), navigate: true);
        } catch (ValidationException $e) {
            Flux::toast(variant: 'danger', text: collect($e->errors())->flatten()->first());
        }
    }
}; ?>

<section class="scf-page">
    <div class="mb-6 flex items-start justify-between">
        <div>
            <flux:heading size="xl">{{ __('Edit invoice') }}</flux:heading>
            <flux:subheading>{{ $invoice->reference_number }}</flux:subheading>
        </div>
        <flux:button :href="route('invoices.show', $invoice)" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="scf-card grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <flux:input wire:model="reference_number" :label="__('Reference number')" required />
            <flux:select wire:model="contact_id" :label="__('Customer')" :placeholder="__('Select customer')">
                <flux:select.option value="">{{ __('None') }}</flux:select.option>
                @foreach ($this->contacts as $contact)
                    <flux:select.option :value="$contact->id">{{ $contact->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="sale_order_id" :label="__('Sale order')" :placeholder="__('None')">
                <flux:select.option value="">{{ __('None') }}</flux:select.option>
                @foreach ($this->saleOrders as $so)
                    <flux:select.option :value="$so->id">{{ $so->reference_number }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="invoice_date" type="date" :label="__('Invoice date')" required />
            <flux:input wire:model="due_date" type="date" :label="__('Due date')" />
            <flux:input wire:model="currency_code" :label="__('Currency')" />
            <flux:input wire:model="payment_terms" :label="__('Payment terms')" class="sm:col-span-2" />
        </div>

        <div class="scf-card overflow-x-auto">
            <div class="mb-3">
                <flux:heading size="lg">{{ __('Line items') }}</flux:heading>
            </div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700 text-left">
                        <th class="pb-2 pr-2 font-medium text-zinc-500 w-40">{{ __('Product') }}</th>
                        <th class="pb-2 pr-2 font-medium text-zinc-500">{{ __('Description') }}</th>
                        <th class="pb-2 pr-2 font-medium text-zinc-500 w-20 text-right">{{ __('Qty') }}</th>
                        <th class="pb-2 pr-2 font-medium text-zinc-500 w-28 text-right">{{ __('Unit price') }}</th>
                        <th class="pb-2 pr-2 font-medium text-zinc-500 w-24 text-right">{{ __('Discount') }}</th>
                        <th class="pb-2 pr-2 font-medium text-zinc-500 w-24 text-right">{{ __('Tax') }}</th>
                        <th class="pb-2 font-medium text-zinc-500 w-28 text-right">{{ __('Total') }}</th>
                        <th class="pb-2 w-8"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($lines as $i => $line)
                        <tr wire:key="line-{{ $i }}" class="border-b border-zinc-100 dark:border-zinc-800">
                            <td class="py-2 pr-2">
                                <select wire:model.live="lines.{{ $i }}.product_id"
                                        class="w-full rounded border border-zinc-300 bg-white px-2 py-1.5 text-sm dark:border-zinc-600 dark:bg-zinc-900">
                                    <option value="">—</option>
                                    @foreach ($this->products as $product)
                                        <option value="{{ $product->id }}" @selected((int) ($line['product_id'] ?? 0) === $product->id)>
                                            {{ $product->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="py-2 pr-2">
                                <input wire:model.live.debounce.300ms="lines.{{ $i }}.description" type="text"
                                       class="w-full rounded border border-zinc-300 bg-white px-2 py-1.5 text-sm dark:border-zinc-600 dark:bg-zinc-900" />
                            </td>
                            <td class="py-2 pr-2">
                                <input wire:model.live="lines.{{ $i }}.quantity" type="number" step="0.01" min="0"
                                       class="w-full rounded border border-zinc-300 bg-white px-2 py-1.5 text-sm text-right dark:border-zinc-600 dark:bg-zinc-900" />
                            </td>
                            <td class="py-2 pr-2">
                                <input wire:model.live="lines.{{ $i }}.unit_price" type="number" step="0.01" min="0"
                                       class="w-full rounded border border-zinc-300 bg-white px-2 py-1.5 text-sm text-right dark:border-zinc-600 dark:bg-zinc-900" />
                            </td>
                            <td class="py-2 pr-2">
                                <input wire:model.live="lines.{{ $i }}.discount_amount" type="number" step="0.01" min="0"
                                       class="w-full rounded border border-zinc-300 bg-white px-2 py-1.5 text-sm text-right dark:border-zinc-600 dark:bg-zinc-900" />
                            </td>
                            <td class="py-2 pr-2">
                                <input wire:model.live="lines.{{ $i }}.tax_amount" type="number" step="0.01" min="0"
                                       class="w-full rounded border border-zinc-300 bg-white px-2 py-1.5 text-sm text-right dark:border-zinc-600 dark:bg-zinc-900" />
                            </td>
                            <td class="py-2 pr-2 text-right font-medium">
                                @php
                                    $qty = max(0, (float) ($line['quantity'] ?? 0));
                                    $price = max(0, (float) ($line['unit_price'] ?? 0));
                                    $disc = max(0, (float) ($line['discount_amount'] ?? 0));
                                    $tax = max(0, (float) ($line['tax_amount'] ?? 0));
                                    echo number_format(round(($qty * $price) - $disc + $tax, 2), 2);
                                @endphp
                            </td>
                            <td class="py-2 pl-1">
                                @if (count($lines) > 1)
                                    <button type="button" wire:click="removeLine({{ $i }})" class="text-zinc-400 hover:text-red-500">
                                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="8" class="pt-2">
                            <flux:button type="button" wire:click="addLine" size="sm" variant="ghost" icon="plus">
                                {{ __('Add line') }}
                            </flux:button>
                        </td>
                    </tr>
                    <tr class="border-t border-zinc-200 dark:border-zinc-700">
                        <td colspan="6" class="pt-3 pr-2 text-right text-zinc-500">{{ __('Subtotal') }}</td>
                        <td class="pt-3 text-right font-medium" colspan="2">{{ number_format($this->totals['subtotal'], 2) }}</td>
                    </tr>
                    @if ($this->totals['discount'] > 0)
                        <tr>
                            <td colspan="6" class="pt-1 pr-2 text-right text-zinc-500">{{ __('Discount') }}</td>
                            <td class="pt-1 text-right text-red-600" colspan="2">−{{ number_format($this->totals['discount'], 2) }}</td>
                        </tr>
                    @endif
                    @if ($this->totals['tax'] > 0)
                        <tr>
                            <td colspan="6" class="pt-1 pr-2 text-right text-zinc-500">{{ __('Tax') }}</td>
                            <td class="pt-1 text-right" colspan="2">{{ number_format($this->totals['tax'], 2) }}</td>
                        </tr>
                    @endif
                    <tr class="font-semibold">
                        <td colspan="6" class="pt-2 pr-2 text-right">{{ __('Total') }}</td>
                        <td class="pt-2 text-right" colspan="2">{{ number_format($this->totals['total'], 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="scf-card">
            <flux:textarea wire:model="notes" :label="__('Notes')" rows="3" />
        </div>

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Save changes') }}</flux:button>
            <flux:button :href="route('invoices.show', $invoice)" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>

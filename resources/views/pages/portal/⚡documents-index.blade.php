<?php

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\SaleOrder;
use App\Support\ScopesToPortalContact;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.portal')] #[Title('Documents')] class extends Component {
    use ScopesToPortalContact;

    #[Computed]
    public function documents(): array
    {
        $contactId = $this->portalContactId();

        return [
            'invoices' => Invoice::query()->where('contact_id', $contactId)->whereNot('status', 'draft')->latest()->limit(20)->get(),
            'quotations' => Quotation::query()->where('contact_id', $contactId)->whereNot('status', 'draft')->latest()->limit(20)->get(),
            'orders' => SaleOrder::query()->where('contact_id', $contactId)->latest()->limit(20)->get(),
            'payments' => Payment::query()->where('contact_id', $contactId)->latest()->limit(20)->get(),
            'contracts' => Contract::query()->where('contact_id', $contactId)->latest()->limit(20)->get(),
        ];
    }
}; ?>

<section class="scf-page space-y-6">
    <div class="portal-glass rounded-2xl p-5">
        <flux:heading size="lg">{{ __('scf.portal.documents') }}</flux:heading>
        <flux:subheading class="mt-1">{{ __('scf.portal.documents_subtitle') }}</flux:subheading>
    </div>

    @foreach ([
        'invoices' => ['label' => __('scf.portal.invoices'), 'type' => 'invoice', 'route' => 'portal.invoices.show', 'pdf' => true],
        'quotations' => ['label' => __('scf.portal.quotations'), 'type' => 'quotation', 'route' => 'portal.quotations.show', 'pdf' => false],
        'orders' => ['label' => __('scf.portal.orders'), 'type' => 'sale-order', 'route' => 'portal.orders.show', 'pdf' => false],
        'payments' => ['label' => __('scf.portal.payments'), 'type' => 'payment', 'route' => null, 'pdf' => false],
        'contracts' => ['label' => __('scf.portal.contracts'), 'type' => null, 'route' => null, 'pdf' => false],
    ] as $key => $meta)
        <div class="portal-glass rounded-2xl p-5">
            <flux:heading size="md">{{ $meta['label'] }}</flux:heading>
            <div class="mt-3 divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($this->documents[$key] as $doc)
                    <div class="flex flex-wrap items-center justify-between gap-3 py-3 text-sm">
                        <div>
                            <p class="font-medium">{{ $doc->reference_number ?? $doc->title ?? ('#'.$doc->id) }}</p>
                            <p class="text-xs text-zinc-500">{{ $doc->created_at?->format('Y-m-d') }}</p>
                        </div>
                        <div class="flex gap-1">
                            @if ($meta['route'])
                                <flux:button size="sm" variant="ghost" :href="route($meta['route'], $doc)" wire:navigate>{{ __('View') }}</flux:button>
                            @endif
                            @if ($meta['type'])
                                <flux:button size="sm" variant="ghost" icon="printer" :href="route('portal.print', ['type' => $meta['type'], 'id' => $doc->id])" target="_blank" />
                            @endif
                            @if ($meta['pdf'])
                                <flux:button size="sm" variant="ghost" icon="arrow-down-tray" :href="route('portal.pdf', ['type' => 'invoice', 'id' => $doc->id])" />
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="py-3 text-sm text-zinc-500">{{ __('No documents') }}</p>
                @endforelse
            </div>
        </div>
    @endforeach
</section>

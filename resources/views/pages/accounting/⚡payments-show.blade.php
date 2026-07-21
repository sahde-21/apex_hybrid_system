<?php

use App\Models\AccountingPosting;
use App\Models\Payment;
use App\Services\Sales\PaymentWorkflowService;
use Flux\Flux;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Payment')] class extends Component {
    public Payment $payment;

    public string $reverseReason = '';
    public bool $showReverseModal = false;
    public bool $showCancelModal = false;

    public function mount(Payment $payment): void
    {
        $this->authorize('view', $payment);
        $this->payment = $payment->load([
            'contact',
            'invoice',
            'events.user',
            'postedBy',
        ]);
    }

    #[Computed]
    public function journalEntry()
    {
        $event = $this->payment->type === \App\Enums\PaymentType::Incoming
            ? 'payment.incoming'
            : 'payment.outgoing';

        $posting = AccountingPosting::query()
            ->where('source_type', $this->payment->getMorphClass())
            ->where('source_id', $this->payment->id)
            ->where('event', $event)
            ->first();

        return $posting?->journalEntry;
    }

    public function post(): void
    {
        try {
            app(PaymentWorkflowService::class)->post($this->payment, auth()->user());
            Flux::toast(variant: 'success', text: __('scf.sales_workflow.payment_posted'));
            $this->payment->refresh();
            unset($this->journalEntry);
        } catch (ValidationException $e) {
            Flux::toast(variant: 'danger', text: collect($e->errors())->flatten()->first());
        }
    }

    public function openReverseModal(): void
    {
        $this->reverseReason = '';
        $this->showReverseModal = true;
    }

    public function reverse(): void
    {
        $this->validate(['reverseReason' => 'required|string|max:500']);
        try {
            app(PaymentWorkflowService::class)->reverse($this->payment, auth()->user(), $this->reverseReason);
            Flux::toast(variant: 'success', text: __('scf.sales_workflow.payment_reversed'));
            $this->showReverseModal = false;
            $this->payment->refresh();
        } catch (ValidationException $e) {
            Flux::toast(variant: 'danger', text: collect($e->errors())->flatten()->first());
        }
    }

    public function cancel(): void
    {
        try {
            app(PaymentWorkflowService::class)->cancel($this->payment, auth()->user());
            Flux::toast(variant: 'success', text: __('scf.sales_workflow.payment_cancelled'));
            $this->payment->refresh();
        } catch (ValidationException $e) {
            Flux::toast(variant: 'danger', text: collect($e->errors())->flatten()->first());
        }
    }
}; ?>

<section class="scf-page">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <flux:heading size="xl">{{ $payment->reference_number }}</flux:heading>
                <flux:badge :color="$payment->status->color()">{{ $payment->status->label() }}</flux:badge>
                <flux:badge :color="$payment->type->color()">{{ $payment->type->label() }}</flux:badge>
            </div>
            <flux:subheading class="mt-1">
                {{ $payment->contact?->name ?? __('No contact') }} ·
                {{ $payment->payment_date->format('d M Y') }}
            </flux:subheading>
        </div>

        <div class="flex flex-wrap gap-2">
            @can('update', $payment)
                @if ($payment->status === \App\Enums\PaymentStatus::Draft)
                    <flux:button :href="route('payments.edit', $payment)" icon="pencil-square" variant="primary" wire:navigate>
                        {{ __('Edit') }}
                    </flux:button>
                @endif
            @endcan

            @can('payments.post')
                @if ($payment->status->canTransitionTo(\App\Enums\PaymentStatus::Posted))
                    <flux:button wire:click="post" icon="check-circle" variant="filled">
                        {{ __('Post') }}
                    </flux:button>
                @endif
            @endcan

            @can('payments.reverse')
                @if ($payment->status->canTransitionTo(\App\Enums\PaymentStatus::Reversed))
                    <flux:button wire:click="openReverseModal" icon="arrow-uturn-left" variant="ghost">
                        {{ __('Reverse') }}
                    </flux:button>
                @endif
            @endcan

            @can('update', $payment)
                @if ($payment->status === \App\Enums\PaymentStatus::Draft)
                    <flux:button wire:click="cancel" icon="x-mark" variant="ghost"
                                 wire:confirm="{{ __('Cancel this payment?') }}">
                        {{ __('Cancel') }}
                    </flux:button>
                @endif
            @endcan

            @if (Route::has('print.payment'))
                <x-print-button type="payment" :id="$payment->id" />
            @endif

            <flux:button :href="route('payments.index')" variant="ghost" wire:navigate>{{ __('Back') }}</flux:button>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Details --}}
        <div class="scf-card space-y-3 lg:col-span-1">
            <flux:heading size="lg">{{ __('Details') }}</flux:heading>
            <div class="space-y-2 text-sm">
                <p><span class="text-zinc-500">{{ __('Contact') }}:</span> {{ $payment->contact?->name ?? '—' }}</p>
                <p><span class="text-zinc-500">{{ __('Date') }}:</span> {{ $payment->payment_date->format('d M Y') }}</p>
                <p><span class="text-zinc-500">{{ __('Amount') }}:</span>
                    <span class="font-semibold">{{ number_format((float) $payment->amount, 2) }}</span>
                </p>
                <p><span class="text-zinc-500">{{ __('Method') }}:</span> {{ $payment->payment_method ?? '—' }}</p>
                <p><span class="text-zinc-500">{{ __('Account') }}:</span> {{ $payment->account_label ?? '—' }}</p>
                @if ($payment->posted_at)
                    <p><span class="text-zinc-500">{{ __('Posted at') }}:</span> {{ $payment->posted_at->format('d M Y H:i') }}</p>
                    <p><span class="text-zinc-500">{{ __('Posted by') }}:</span> {{ $payment->postedBy?->name ?? '—' }}</p>
                @endif
                @if ($payment->reversal_reason)
                    <p><span class="text-zinc-500">{{ __('Reversal reason') }}:</span> {{ $payment->reversal_reason }}</p>
                @endif
            </div>

            @if ($payment->notes)
                <div class="border-t border-zinc-100 pt-3 dark:border-zinc-800">
                    <p class="text-xs font-medium text-zinc-500 uppercase tracking-wide">{{ __('Notes') }}</p>
                    <p class="mt-1 text-sm">{{ $payment->notes }}</p>
                </div>
            @endif
        </div>

        <div class="space-y-6 lg:col-span-2">
            {{-- Invoice link --}}
            @if ($payment->invoice)
                <div class="scf-card">
                    <flux:heading size="lg" class="mb-3">{{ __('Applied to invoice') }}</flux:heading>
                    @can('view', $payment->invoice)
                        <a href="{{ route('invoices.show', $payment->invoice) }}" wire:navigate
                           class="flex items-center justify-between rounded-lg border border-zinc-200 px-4 py-3 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">
                            <div class="text-sm">
                                <p class="font-medium">{{ $payment->invoice->reference_number }}</p>
                                <p class="text-xs text-zinc-500">{{ $payment->invoice->invoice_date->format('d M Y') }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <flux:badge size="sm" :color="$payment->invoice->status->color()">
                                    {{ $payment->invoice->status->label() }}
                                </flux:badge>
                                <span class="text-sm font-medium">{{ number_format((float) $payment->invoice->total_amount, 2) }}</span>
                            </div>
                        </a>
                    @else
                        <div class="flex items-center justify-between rounded-lg border border-zinc-200 px-4 py-3 dark:border-zinc-700">
                            <span class="text-sm font-medium">{{ $payment->invoice->reference_number }}</span>
                            <flux:badge size="sm" :color="$payment->invoice->status->color()">{{ $payment->invoice->status->label() }}</flux:badge>
                        </div>
                    @endcan
                </div>
            @endif

            {{-- Related documents (journal entry) --}}
            <x-sales.related-documents
                :quotation="null"
                :sale-order="null"
                :invoice="$payment->invoice"
                :payment="null"
                :journal-entry="$this->journalEntry"
            />

            <livewire:activity.activity-timeline :subject="$payment" :key="'activity-payment-'.$payment->id" />
        </div>
    </div>

    {{-- Reverse modal --}}
    <flux:modal wire:model="showReverseModal" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Reverse payment') }}</flux:heading>
            <flux:text class="text-sm text-zinc-600">{{ __('A reversal entry will be created and the invoice balance will be updated.') }}</flux:text>
            <flux:textarea wire:model="reverseReason" :label="__('Reason (required)')" rows="3" required />
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="reverse">{{ __('Reverse payment') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>

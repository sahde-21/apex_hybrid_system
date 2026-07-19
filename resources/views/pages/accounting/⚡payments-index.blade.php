<?php

use App\Enums\PaymentType;
use App\Models\Payment;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Payments')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $type = '';

    public ?int $paymentToDelete = null;

    public bool $showDeleteModal = false;

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, Payment>
     */
    #[Computed]
    public function payments()
    {
        return Payment::query()
            ->with('contact')
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('reference_number', 'like', "%{$this->search}%")
                        ->orWhere('payment_method', 'like', "%{$this->search}%")
                        ->orWhere('notes', 'like', "%{$this->search}%")
                        ->orWhereHas('contact', fn ($q) => $q->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->type, fn ($query) => $query->where('type', $this->type))
            ->latest('payment_date')
            ->paginate(10);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $paymentId): void
    {
        $this->paymentToDelete = $paymentId;
        $this->showDeleteModal = true;
    }

    public function deletePayment(): void
    {
        if ($this->paymentToDelete === null) {
            return;
        }

        $model = Payment::query()->findOrFail($this->paymentToDelete);

        if (! $model->status->isEditable()) {
            $this->paymentToDelete = null;
            $this->showDeleteModal = false;
            Flux::toast(variant: 'danger', text: __('scf.sales_workflow.immutable_posted'));

            return;
        }

        $this->authorize('delete', $model);

        $model->delete();

        $this->paymentToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Payment deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Payments')"
        :subtitle="__('Record incoming and outgoing payments')"
    >
        <x-slot:actions>
            <flux:button :href="route('payments.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add payment') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search by reference, contact, payment method, or notes...')" />

        <flux:select wire:model.live="type" :placeholder="__('All types')">
            <flux:select.option value="">{{ __('All types') }}</flux:select.option>
            @foreach (PaymentType::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->payments">
            <flux:table.columns>
                <flux:table.column>{{ __('Reference') }}</flux:table.column>
                <flux:table.column>{{ __('Contact') }}</flux:table.column>
                <flux:table.column>{{ __('Payment date') }}</flux:table.column>
                <flux:table.column>{{ __('Type') }}</flux:table.column>
                <flux:table.column>{{ __('Amount') }}</flux:table.column>
                <flux:table.column>{{ __('Payment method') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->payments as $payment)
                    <flux:table.row wire:key="payment-{{ $payment->id }}">
                        <flux:table.cell class="font-medium">
                            <a href="{{ route('payments.show', $payment) }}" wire:navigate class="hover:underline">
                                {{ $payment->reference_number }}
                            </a>
                        </flux:table.cell>
                        <flux:table.cell>{{ $payment->contact?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $payment->payment_date->format('Y-m-d') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$payment->type->color()">{{ $payment->type->label() }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ number_format((float) $payment->amount, 2) }}</flux:table.cell>
                        <flux:table.cell>{{ $payment->payment_method ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="eye"
                                    :href="route('payments.show', $payment)"
                                    wire:navigate
                                />
                                <x-print-button type="payment" :id="$payment->id" />
                                
@can('update', $payment)
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="pencil-square"
                                    :href="route('payments.edit', $payment)"
                                    wire:navigate
                                />
                                
@endcan
                                @can('delete', $payment)
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="trash"
                                    wire:click="confirmDelete({{ $payment->id }})"
                                />
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7">
                            <x-empty-state icon="inbox" :title="__('No payments found.')" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal wire:model="showDeleteModal" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Delete payment') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Are you sure you want to delete this payment? This action cannot be undone.') }}</flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="deletePayment">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>

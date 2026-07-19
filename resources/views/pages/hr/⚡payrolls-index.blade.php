<?php

use App\Enums\PayrollStatus;
use App\Models\Payroll;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Payrolls')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    public ?int $payrollToDelete = null;

    public bool $showDeleteModal = false;

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, Payroll>
     */
    #[Computed]
    public function payrolls()
    {
        return Payroll::query()
            ->with('employee')
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('reference_number', 'like', "%{$this->search}%")
                        ->orWhere('notes', 'like', "%{$this->search}%")
                        ->orWhereHas('employee', function ($q) {
                            $q->where('employee_number', 'like', "%{$this->search}%")
                                ->orWhere('first_name', 'like', "%{$this->search}%")
                                ->orWhere('last_name', 'like', "%{$this->search}%");
                        });
                });
            })
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->latest('pay_period_end')
            ->paginate(10);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $payrollId): void
    {
        $this->payrollToDelete = $payrollId;
        $this->showDeleteModal = true;
    }

    public function deletePayroll(): void
    {
        if ($this->payrollToDelete === null) {
            return;
        }

        $model = Payroll::query()->findOrFail($this->payrollToDelete);


        $this->authorize('delete', $model);


        $model->delete();

        $this->payrollToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Payroll deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Payrolls')"
        :subtitle="__('Process employee pay periods and compensation')"
    >
        <x-slot:actions>
            <flux:button :href="route('payrolls.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add payroll') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search by reference, employee, or notes...')" />

        <flux:select wire:model.live="status" :placeholder="__('All statuses')">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            @foreach (PayrollStatus::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->payrolls">
            <flux:table.columns>
                <flux:table.column>{{ __('Reference') }}</flux:table.column>
                <flux:table.column>{{ __('Employee') }}</flux:table.column>
                <flux:table.column>{{ __('Pay period') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Gross') }}</flux:table.column>
                <flux:table.column>{{ __('Net') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->payrolls as $payroll)
                    <flux:table.row wire:key="payroll-{{ $payroll->id }}">
                        <flux:table.cell class="font-medium">{{ $payroll->reference_number }}</flux:table.cell>
                        <flux:table.cell>{{ $payroll->employee?->fullName() ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            {{ $payroll->pay_period_start->format('Y-m-d') }} — {{ $payroll->pay_period_end->format('Y-m-d') }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$payroll->status->color()">{{ $payroll->status->label() }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ number_format((float) $payroll->gross_amount, 2) }}</flux:table.cell>
                        <flux:table.cell>{{ number_format((float) $payroll->net_amount, 2) }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                
@can('update', $payroll)
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="pencil-square"
                                    :href="route('payrolls.edit', $payroll)"
                                    wire:navigate
                                />
                                
@endcan
                                @can('delete', $payroll)
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="trash"
                                    wire:click="confirmDelete({{ $payroll->id }})"
                                />
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7">
                            <x-empty-state icon="inbox" :title="__('No payrolls found.')" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal wire:model="showDeleteModal" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Delete payroll') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Are you sure you want to delete this payroll? This action cannot be undone.') }}</flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="deletePayroll">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>

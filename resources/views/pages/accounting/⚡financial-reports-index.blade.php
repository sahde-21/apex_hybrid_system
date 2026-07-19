<?php

use App\Enums\FinancialReportStatus;
use App\Enums\FinancialReportType;
use App\Models\FinancialReport;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Financial reports')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $reportType = '';

    #[Url]
    public string $status = '';

    public ?int $financialReportToDelete = null;

    public bool $showDeleteModal = false;

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, FinancialReport>
     */
    #[Computed]
    public function financialReports()
    {
        return FinancialReport::query()
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('reference_number', 'like', "%{$this->search}%")
                        ->orWhere('name', 'like', "%{$this->search}%")
                        ->orWhere('notes', 'like', "%{$this->search}%");
                });
            })
            ->when($this->reportType, fn ($query) => $query->where('report_type', $this->reportType))
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->latest('period_end')
            ->paginate(10);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedReportType(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $financialReportId): void
    {
        $this->financialReportToDelete = $financialReportId;
        $this->showDeleteModal = true;
    }

    public function deleteFinancialReport(): void
    {
        if ($this->financialReportToDelete === null) {
            return;
        }

        $model = FinancialReport::query()->findOrFail($this->financialReportToDelete);


        $this->authorize('delete', $model);


        $model->delete();

        $this->financialReportToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Financial report deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Financial reports')"
        :subtitle="__('Generate and review profit, balance, and cash flow reports')"
    >
        <x-slot:actions>
            <flux:button :href="route('financial-reports.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add financial report') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6 grid gap-4 md:grid-cols-3">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search by reference, name, or notes...')" />

        <flux:select wire:model.live="reportType" :placeholder="__('All report types')">
            <flux:select.option value="">{{ __('All report types') }}</flux:select.option>
            @foreach (FinancialReportType::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="status" :placeholder="__('All statuses')">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            @foreach (FinancialReportStatus::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->financialReports">
            <flux:table.columns>
                <flux:table.column>{{ __('Reference') }}</flux:table.column>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Type') }}</flux:table.column>
                <flux:table.column>{{ __('Period') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Revenue') }}</flux:table.column>
                <flux:table.column>{{ __('Expenses') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->financialReports as $financialReport)
                    <flux:table.row wire:key="financial-report-{{ $financialReport->id }}">
                        <flux:table.cell class="font-medium">{{ $financialReport->reference_number }}</flux:table.cell>
                        <flux:table.cell>{{ $financialReport->name }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$financialReport->report_type->color()">{{ $financialReport->report_type->label() }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $financialReport->period_start->format('Y-m-d') }} — {{ $financialReport->period_end->format('Y-m-d') }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$financialReport->status->color()">{{ $financialReport->status->label() }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ number_format((float) $financialReport->total_revenue, 2) }}</flux:table.cell>
                        <flux:table.cell>{{ number_format((float) $financialReport->total_expenses, 2) }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                
@can('update', $financialReport)
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="pencil-square"
                                    :href="route('financial-reports.edit', $financialReport)"
                                    wire:navigate
                                />
                                
@endcan
                                @can('delete', $financialReport)
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="trash"
                                    wire:click="confirmDelete({{ $financialReport->id }})"
                                />
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="8">
                            <x-empty-state icon="inbox" :title="__('No financial reports found.')" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal wire:model="showDeleteModal" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Delete financial report') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Are you sure you want to delete this financial report? This action cannot be undone.') }}</flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="deleteFinancialReport">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>

<?php

use App\Concerns\FinancialReportValidationRules;
use App\Enums\FinancialReportStatus;
use App\Enums\FinancialReportType;
use App\Models\FinancialReport;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit financial report')] class extends Component {
    use FinancialReportValidationRules;

    public FinancialReport $financialReport;

    public string $reference_number = '';
    public string $name = '';
    public string $report_type = 'profit_loss';
    public string $period_start = '';
    public string $period_end = '';
    public string $status = 'draft';
    public string $total_revenue = '0';
    public string $total_expenses = '0';
    public string $notes = '';

    public function mount(FinancialReport $financialReport): void
    {
        $this->financialReport = $financialReport;
        $this->reference_number = $financialReport->reference_number;
        $this->name = $financialReport->name;
        $this->report_type = $financialReport->report_type->value;
        $this->period_start = $financialReport->period_start->format('Y-m-d');
        $this->period_end = $financialReport->period_end->format('Y-m-d');
        $this->status = $financialReport->status->value;
        $this->total_revenue = (string) $financialReport->total_revenue;
        $this->total_expenses = (string) $financialReport->total_expenses;
        $this->notes = $financialReport->notes ?? '';
    }

    public function save(): void
    {
        $validated = $this->validate($this->financialReportRules($this->financialReport->id));

        $this->financialReport->update($validated);

        Flux::toast(variant: 'success', text: __('Financial report updated successfully.'));

        $this->redirect(route('financial-reports.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Edit financial report') }}</flux:heading>
        <flux:subheading>{{ __('Update financial report details') }}</flux:subheading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="reference_number" :label="__('Reference number')" required />
        <flux:input wire:model="name" :label="__('Name')" required />
        <flux:select wire:model="report_type" :label="__('Report type')">
            @foreach (FinancialReportType::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="period_start" type="date" :label="__('Period start')" required />
        <flux:input wire:model="period_end" type="date" :label="__('Period end')" required />
        <flux:select wire:model="status" :label="__('Status')">
            @foreach (FinancialReportStatus::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="total_revenue" type="number" step="0.01" :label="__('Total revenue')" required />
        <flux:input wire:model="total_expenses" type="number" step="0.01" :label="__('Total expenses')" required />
        <flux:textarea wire:model="notes" :label="__('Notes')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Save changes') }}</flux:button>
            <flux:button :href="route('financial-reports.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>

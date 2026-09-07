<?php

use App\Enums\FinancialReportStatus;
use App\Enums\FinancialReportType;
use App\Models\FinancialReport;
use Flux\Flux;
use Livewire\Attributes\Title;

new #[Title('Create financial report')] class extends \App\Livewire\ConcernBases\FinancialReportValidationRulesBase {

    public string $reference_number = '';
    public string $name = '';
    public string $report_type = 'profit_loss';
    public string $period_start = '';
    public string $period_end = '';
    public string $status = 'draft';
    public string $total_revenue = '0';
    public string $total_expenses = '0';
    public string $notes = '';

    public function mount(): void
    {
        $this->period_start = now()->startOfMonth()->format('Y-m-d');
        $this->period_end = now()->format('Y-m-d');
    }

    public function save(): void
    {
        $validated = $this->validate($this->financialReportRules());

        FinancialReport::query()->create($validated);

        Flux::toast(variant: 'success', text: __('Financial report created successfully.'));

        $this->redirect(route('financial-reports.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Create financial report') }}</flux:heading>
        <flux:subheading>{{ __('Generate a new financial report') }}</flux:subheading>
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
            <flux:button type="submit" variant="primary">{{ __('Create financial report') }}</flux:button>
            <flux:button :href="route('financial-reports.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>

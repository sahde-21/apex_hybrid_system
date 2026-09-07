<?php

use App\Enums\PayrollStatus;
use App\Models\Employee;
use App\Models\Payroll;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;

new #[Title('Create payroll')] class extends \App\Livewire\ConcernBases\PayrollValidationRulesBase {

    public string $reference_number = '';
    public ?int $employee_id = null;
    public string $pay_period_start = '';
    public string $pay_period_end = '';
    public string $gross_amount = '0';
    public string $deductions = '0';
    public string $net_amount = '0';
    public string $status = 'draft';
    public string $notes = '';

    public function mount(): void
    {
        $this->pay_period_start = now()->startOfMonth()->format('Y-m-d');
        $this->pay_period_end = now()->endOfMonth()->format('Y-m-d');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Employee>
     */
    #[Computed]
    public function employees()
    {
        return Employee::query()->orderBy('first_name')->orderBy('last_name')->get();
    }

    public function save(): void
    {
        $validated = $this->validate($this->payrollRules());

        Payroll::query()->create($validated);

        Flux::toast(variant: 'success', text: __('Payroll created successfully.'));

        $this->redirect(route('payrolls.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Create payroll') }}</flux:heading>
        <flux:subheading>{{ __('Record a new employee payroll') }}</flux:subheading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="reference_number" :label="__('Reference number')" required />
        <flux:select wire:model="employee_id" :label="__('Employee')" :placeholder="__('Select employee')" required>
            @foreach ($this->employees as $employee)
                <flux:select.option :value="$employee->id">{{ $employee->fullName() }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="pay_period_start" type="date" :label="__('Pay period start')" required />
        <flux:input wire:model="pay_period_end" type="date" :label="__('Pay period end')" required />
        <flux:input wire:model="gross_amount" type="number" step="0.01" :label="__('Gross amount')" required />
        <flux:input wire:model="deductions" type="number" step="0.01" :label="__('Deductions')" required />
        <flux:input wire:model="net_amount" type="number" step="0.01" :label="__('Net amount')" required />
        <flux:select wire:model="status" :label="__('Status')">
            @foreach (PayrollStatus::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:textarea wire:model="notes" :label="__('Notes')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Create payroll') }}</flux:button>
            <flux:button :href="route('payrolls.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>

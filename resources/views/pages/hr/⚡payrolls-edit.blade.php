<?php

use App\Concerns\PayrollValidationRules;
use App\Enums\PayrollStatus;
use App\Models\Employee;
use App\Models\Payroll;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit payroll')] class extends Component {
    use PayrollValidationRules;

    public Payroll $payroll;

    public string $reference_number = '';
    public ?int $employee_id = null;
    public string $pay_period_start = '';
    public string $pay_period_end = '';
    public string $gross_amount = '0';
    public string $deductions = '0';
    public string $net_amount = '0';
    public string $status = 'draft';
    public string $notes = '';

    public function mount(Payroll $payroll): void
    {
        $this->payroll = $payroll;
        $this->reference_number = $payroll->reference_number;
        $this->employee_id = $payroll->employee_id;
        $this->pay_period_start = $payroll->pay_period_start->format('Y-m-d');
        $this->pay_period_end = $payroll->pay_period_end->format('Y-m-d');
        $this->gross_amount = (string) $payroll->gross_amount;
        $this->deductions = (string) $payroll->deductions;
        $this->net_amount = (string) $payroll->net_amount;
        $this->status = $payroll->status->value;
        $this->notes = $payroll->notes ?? '';
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
        $validated = $this->validate($this->payrollRules($this->payroll->id));

        $this->payroll->update($validated);

        Flux::toast(variant: 'success', text: __('Payroll updated successfully.'));

        $this->redirect(route('payrolls.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Edit payroll') }}</flux:heading>
        <flux:subheading>{{ __('Update payroll details') }}</flux:subheading>
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
            <flux:button type="submit" variant="primary">{{ __('Save changes') }}</flux:button>
            <flux:button :href="route('payrolls.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>

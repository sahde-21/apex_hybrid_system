<?php

use App\Concerns\EmployeeValidationRules;
use App\Models\Employee;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create employee')] class extends Component {
    use EmployeeValidationRules;

    public string $employee_number = '';
    public string $first_name = '';
    public string $last_name = '';
    public string $email = '';
    public string $phone = '';
    public string $department = '';
    public string $job_title = '';
    public string $hire_date = '';
    public string $salary = '0';
    public bool $is_active = true;

    public function mount(): void
    {
        $this->hire_date = now()->format('Y-m-d');
    }

    public function save(): void
    {
        $validated = $this->validate($this->employeeRules());

        Employee::query()->create($validated);

        Flux::toast(variant: 'success', text: __('Employee created successfully.'));

        $this->redirect(route('employees.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Create employee') }}</flux:heading>
        <flux:subheading>{{ __('Add a new employee record') }}</flux:subheading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="employee_number" :label="__('Employee number')" required />
        <flux:input wire:model="first_name" :label="__('First name')" required />
        <flux:input wire:model="last_name" :label="__('Last name')" required />
        <flux:input wire:model="email" type="email" :label="__('Email')" />
        <flux:input wire:model="phone" :label="__('Phone')" />
        <flux:input wire:model="department" :label="__('Department')" />
        <flux:input wire:model="job_title" :label="__('Job title')" />
        <flux:input wire:model="hire_date" type="date" :label="__('Hire date')" required />
        <flux:input wire:model="salary" type="number" step="0.01" :label="__('Salary')" required />
        <flux:switch wire:model="is_active" :label="__('Active')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Create employee') }}</flux:button>
            <flux:button :href="route('employees.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>

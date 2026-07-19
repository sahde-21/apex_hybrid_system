<?php

use App\Concerns\AttendanceValidationRules;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Branch;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create Attendance')] class extends Component {
    use AttendanceValidationRules;
    public ?int $employee_id = null;
    public ?int $branch_id = null;
    public string $attendance_date = '';
    public string $check_in = '';
    public string $check_out = '';
    public string $status = 'present';
    public string $notes = '';

    public function mount(): void
    {
        $this->attendance_date = now()->format('Y-m-d');
    }

    #[Computed]
    public function employees()
    {
        return \App\Models\Employee::query()->orderBy('name')->get();
    }

    #[Computed]
    public function branches()
    {
        return \App\Models\Branch::query()->orderBy('name')->get();
    }

    public function save(): void
    {
        $validated = $this->validate($this->attendanceRules());

        Attendance::query()->create($validated);

        Flux::toast(variant: 'success', text: __('Attendance created successfully.'));

        $this->redirect(route('attendance.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Create Attendance') }}</flux:heading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:select wire:model="employee_id" :label="__('Employee Id')" :placeholder="__('Select')">
            <flux:select.option value="">{{ __('None') }}</flux:select.option>
            @foreach ($this->employees as $item)
                <flux:select.option :value="$item->id">{{ $item->fullName() }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model="branch_id" :label="__('Branch Id')" :placeholder="__('Select')">
            <flux:select.option value="">{{ __('None') }}</flux:select.option>
            @foreach ($this->branches as $item)
                <flux:select.option :value="$item->id">{{ $item->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="attendance_date" type="date" :label="__('Attendance Date')" required />
        <flux:input wire:model="check_in" type="time" :label="__('Check In')" />
        <flux:input wire:model="check_out" type="time" :label="__('Check Out')" />
        <flux:input wire:model="status" :label="__('Status')" />
        <flux:textarea wire:model="notes" :label="__('Notes')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Create') }}</flux:button>
            <flux:button :href="route('attendance.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>

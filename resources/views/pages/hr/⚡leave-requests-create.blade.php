<?php

use App\Concerns\LeaveRequestValidationRules;
use App\Models\LeaveRequest;
use App\Enums\LeaveRequestStatus;
use App\Models\Employee;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create Leave requests')] class extends Component {
    use LeaveRequestValidationRules;
    public ?int $employee_id = null;
    public string $leave_type = '';
    public string $start_date = '';
    public string $end_date = '';
    public string $status = 'pending';
    public string $reason = '';

    public function mount(): void
    {
        $this->start_date = now()->format('Y-m-d');
        $this->end_date = now()->format('Y-m-d');
    }

    #[Computed]
    public function employees()
    {
        return \App\Models\Employee::query()->orderBy('name')->get();
    }

    public function save(): void
    {
        $validated = $this->validate($this->leaveRequestRules());

        LeaveRequest::query()->create($validated);

        Flux::toast(variant: 'success', text: __('Leave requests created successfully.'));

        $this->redirect(route('leave-requests.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Create Leave requests') }}</flux:heading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:select wire:model="employee_id" :label="__('Employee Id')" :placeholder="__('Select')">
            <flux:select.option value="">{{ __('None') }}</flux:select.option>
            @foreach ($this->employees as $item)
                <flux:select.option :value="$item->id">{{ $item->fullName() }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="leave_type" :label="__('Leave Type')" required />
        <flux:input wire:model="start_date" type="date" :label="__('Start Date')" required />
        <flux:input wire:model="end_date" type="date" :label="__('End Date')" required />
        <flux:select wire:model="status" :label="__('Status')">
            @foreach (LeaveRequestStatus::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:textarea wire:model="reason" :label="__('Reason')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Create') }}</flux:button>
            <flux:button :href="route('leave-requests.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>

<?php

use App\Concerns\TimeLogValidationRules;
use App\Models\TimeLog;
use App\Models\ProjectTask;
use App\Models\Employee;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create Time logs')] class extends Component {
    use TimeLogValidationRules;
    public ?int $project_task_id = null;
    public ?int $employee_id = null;
    public string $log_date = '';
    public string $hours = '0';
    public string $description = '';

    public function mount(): void
    {
        $this->log_date = now()->format('Y-m-d');
    }

    #[Computed]
    public function projectTasks()
    {
        return \App\Models\ProjectTask::query()->orderBy('name')->get();
    }

    #[Computed]
    public function employees()
    {
        return \App\Models\Employee::query()->orderBy('name')->get();
    }

    public function save(): void
    {
        $validated = $this->validate($this->timeLogRules());

        TimeLog::query()->create($validated);

        Flux::toast(variant: 'success', text: __('Time logs created successfully.'));

        $this->redirect(route('time-logs.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Create Time logs') }}</flux:heading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:select wire:model="project_task_id" :label="__('Project Task Id')" :placeholder="__('Select')">
            <flux:select.option value="">{{ __('None') }}</flux:select.option>
            @foreach ($this->projectTasks as $item)
                <flux:select.option :value="$item->id">{{ $item->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model="employee_id" :label="__('Employee Id')" :placeholder="__('Select')">
            <flux:select.option value="">{{ __('None') }}</flux:select.option>
            @foreach ($this->employees as $item)
                <flux:select.option :value="$item->id">{{ $item->fullName() }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="log_date" type="date" :label="__('Log Date')" required />
        <flux:input wire:model="hours" type="number" step="0.01" :label="__('Hours')" required />
        <flux:textarea wire:model="description" :label="__('Description')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Create') }}</flux:button>
            <flux:button :href="route('time-logs.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>

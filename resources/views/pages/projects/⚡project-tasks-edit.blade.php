<?php

use App\Concerns\ProjectTaskValidationRules;
use App\Models\ProjectTask;
use App\Enums\ProjectTaskStatus;
use App\Models\Contract;
use App\Models\Employee;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit Project tasks')] class extends Component {
    use ProjectTaskValidationRules;
    public ProjectTask $projectTask;

    public ?int $contract_id = null;
    public ?int $employee_id = null;
    public string $title = '';
    public string $due_date = '';
    public string $priority = 'medium';
    public string $status = 'todo';
    public string $description = '';

    public function mount(ProjectTask $projectTask): void
    {
        $this->projectTask = $projectTask;
        $this->contract_id = $projectTask->contract_id;
        $this->employee_id = $projectTask->employee_id;
        $this->title = $projectTask->title ?? '';
        $this->due_date = $projectTask->due_date?->format('Y-m-d') ?? '';
        $this->priority = $projectTask->priority ?? '';
        $this->status = $projectTask->status->value;
        $this->description = $projectTask->description ?? '';
    }

    #[Computed]
    public function contracts()
    {
        return \App\Models\Contract::query()->orderBy('name')->get();
    }

    #[Computed]
    public function employees()
    {
        return \App\Models\Employee::query()->orderBy('name')->get();
    }

    public function save(): void
    {
        $validated = $this->validate($this->projectTaskUpdateRules($this->projectTask->id));

        $this->projectTask->update($validated);

        Flux::toast(variant: 'success', text: __('Project tasks updated successfully.'));

        $this->redirect(route('project-tasks.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Edit Project tasks') }}</flux:heading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:select wire:model="contract_id" :label="__('Contract Id')" :placeholder="__('Select')">
            <flux:select.option value="">{{ __('None') }}</flux:select.option>
            @foreach ($this->contracts as $item)
                <flux:select.option :value="$item->id">{{ $item->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model="employee_id" :label="__('Employee Id')" :placeholder="__('Select')">
            <flux:select.option value="">{{ __('None') }}</flux:select.option>
            @foreach ($this->employees as $item)
                <flux:select.option :value="$item->id">{{ $item->fullName() }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="title" :label="__('Title')" required />
        <flux:input wire:model="due_date" type="date" :label="__('Due Date')" />
        <flux:input wire:model="priority" :label="__('Priority')" />
        <flux:select wire:model="status" :label="__('Status')">
            @foreach (ProjectTaskStatus::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:textarea wire:model="description" :label="__('Description')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Save changes') }}</flux:button>
            <flux:button :href="route('project-tasks.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>

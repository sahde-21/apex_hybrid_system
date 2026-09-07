<?php

use App\Models\PerformanceReview;
use App\Enums\PerformanceReviewStatus;
use App\Models\Employee;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;

new #[Title('Edit Performance reviews')] class extends \App\Livewire\ConcernBases\PerformanceReviewValidationRulesBase {
    public PerformanceReview $performanceReview;

    public ?int $employee_id = null;
    public string $review_date = '';
    public int $rating = 0;
    public string $status = 'draft';
    public string $comments = '';

    public function mount(PerformanceReview $performanceReview): void
    {
        $this->performanceReview = $performanceReview;
        $this->employee_id = $performanceReview->employee_id;
        $this->review_date = $performanceReview->review_date?->format('Y-m-d') ?? '';
        $this->rating = (string) $performanceReview->rating;
        $this->status = $performanceReview->status->value;
        $this->comments = $performanceReview->comments ?? '';
    }

    #[Computed]
    public function employees()
    {
        return \App\Models\Employee::query()->orderBy('name')->get();
    }

    public function save(): void
    {
        $validated = $this->validate($this->performanceReviewUpdateRules($this->performanceReview->id));

        $this->performanceReview->update($validated);

        Flux::toast(variant: 'success', text: __('Performance reviews updated successfully.'));

        $this->redirect(route('performance-reviews.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Edit Performance reviews') }}</flux:heading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:select wire:model="employee_id" :label="__('Employee Id')" :placeholder="__('Select')">
            <flux:select.option value="">{{ __('None') }}</flux:select.option>
            @foreach ($this->employees as $item)
                <flux:select.option :value="$item->id">{{ $item->fullName() }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="review_date" type="date" :label="__('Review Date')" required />
        <flux:input wire:model="rating" type="number" :label="__('Rating')" />
        <flux:select wire:model="status" :label="__('Status')">
            @foreach (PerformanceReviewStatus::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:textarea wire:model="comments" :label="__('Comments')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Save changes') }}</flux:button>
            <flux:button :href="route('performance-reviews.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>

<?php

use App\Concerns\SupplierEvaluationValidationRules;
use App\Models\SupplierEvaluation;
use App\Models\Contact;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create Supplier evaluations')] class extends Component {
    use SupplierEvaluationValidationRules;
    public ?int $contact_id = null;
    public string $evaluation_date = '';
    public int $quality_score = 0;
    public int $delivery_score = 0;
    public int $price_score = 0;
    public int $overall_score = 0;
    public string $comments = '';

    public function mount(): void
    {
        $this->evaluation_date = now()->format('Y-m-d');
    }

    #[Computed]
    public function contacts()
    {
        return \App\Models\Contact::query()->orderBy('name')->get();
    }

    public function save(): void
    {
        $validated = $this->validate($this->supplierEvaluationRules());

        SupplierEvaluation::query()->create($validated);

        Flux::toast(variant: 'success', text: __('Supplier evaluations created successfully.'));

        $this->redirect(route('supplier-evaluations.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Create Supplier evaluations') }}</flux:heading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:select wire:model="contact_id" :label="__('Contact Id')" :placeholder="__('Select')">
            <flux:select.option value="">{{ __('None') }}</flux:select.option>
            @foreach ($this->contacts as $item)
                <flux:select.option :value="$item->id">{{ $item->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="evaluation_date" type="date" :label="__('Evaluation Date')" required />
        <flux:input wire:model="quality_score" type="number" :label="__('Quality Score')" />
        <flux:input wire:model="delivery_score" type="number" :label="__('Delivery Score')" />
        <flux:input wire:model="price_score" type="number" :label="__('Price Score')" />
        <flux:input wire:model="overall_score" type="number" :label="__('Overall Score')" />
        <flux:textarea wire:model="comments" :label="__('Comments')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Create') }}</flux:button>
            <flux:button :href="route('supplier-evaluations.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>

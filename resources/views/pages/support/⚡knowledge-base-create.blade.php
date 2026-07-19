<?php

use App\Concerns\KnowledgeBaseArticleValidationRules;
use App\Models\KnowledgeBaseArticle;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create Knowledge base')] class extends Component {
    use KnowledgeBaseArticleValidationRules;
    public string $title = '';
    public string $slug = '';
    public string $category = '';
    public string $content = '';
    public bool $is_published = false;

    public function mount(): void
    {
    }

    public function save(): void
    {
        $validated = $this->validate($this->knowledgeBaseArticleRules());

        KnowledgeBaseArticle::query()->create($validated);

        Flux::toast(variant: 'success', text: __('Knowledge base created successfully.'));

        $this->redirect(route('knowledge-base.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Create Knowledge base') }}</flux:heading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="title" :label="__('Title')" required />
        <flux:input wire:model="slug" :label="__('Slug')" required />
        <flux:input wire:model="category" :label="__('Category')" required />
        <flux:textarea wire:model="content" :label="__('Content')" />
        <flux:switch wire:model="is_published" :label="__('Is Published')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Create') }}</flux:button>
            <flux:button :href="route('knowledge-base.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>

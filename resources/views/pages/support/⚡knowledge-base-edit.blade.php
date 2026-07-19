<?php

use App\Concerns\KnowledgeBaseArticleValidationRules;
use App\Models\KnowledgeBaseArticle;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit Knowledge base')] class extends Component {
    use KnowledgeBaseArticleValidationRules;
    public KnowledgeBaseArticle $knowledgeBaseArticle;

    public string $title = '';
    public string $slug = '';
    public string $category = '';
    public string $content = '';
    public bool $is_published = false;

    public function mount(KnowledgeBaseArticle $knowledgeBaseArticle): void
    {
        $this->knowledgeBaseArticle = $knowledgeBaseArticle;
        $this->title = $knowledgeBaseArticle->title ?? '';
        $this->slug = $knowledgeBaseArticle->slug ?? '';
        $this->category = $knowledgeBaseArticle->category ?? '';
        $this->content = $knowledgeBaseArticle->content ?? '';
        $this->is_published = $knowledgeBaseArticle->is_published;
    }

    public function save(): void
    {
        $validated = $this->validate($this->knowledgeBaseArticleUpdateRules($this->knowledgeBaseArticle->id));

        $this->knowledgeBaseArticle->update($validated);

        Flux::toast(variant: 'success', text: __('Knowledge base updated successfully.'));

        $this->redirect(route('knowledge-base.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Edit Knowledge base') }}</flux:heading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="title" :label="__('Title')" required />
        <flux:input wire:model="slug" :label="__('Slug')" required />
        <flux:input wire:model="category" :label="__('Category')" required />
        <flux:textarea wire:model="content" :label="__('Content')" />
        <flux:switch wire:model="is_published" :label="__('Is Published')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Save changes') }}</flux:button>
            <flux:button :href="route('knowledge-base.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>

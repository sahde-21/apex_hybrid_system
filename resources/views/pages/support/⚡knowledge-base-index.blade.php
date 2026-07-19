<?php

use App\Models\KnowledgeBaseArticle;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Knowledge base')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public ?int $knowledgeBaseArticleToDelete = null;

    public bool $showDeleteModal = false;

    #[Computed]
    public function knowledgeBaseArticles()
    {
        return KnowledgeBaseArticle::query()
            
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('title', 'like', "%{$this->search}%")
                        ->orWhere('title', 'like', "%{$this->search}%")
                        ->orWhere('category', 'like', "%{$this->search}%")
                        ->orWhere('content', 'like', "%{$this->search}%");
                });
            })
            
            ->latest()
            ->paginate(10);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $id): void
    {
        $this->knowledgeBaseArticleToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function deleteKnowledgeBaseArticle(): void
    {
        if ($this->knowledgeBaseArticleToDelete === null) {
            return;
        }

        $model = KnowledgeBaseArticle::query()->findOrFail($this->knowledgeBaseArticleToDelete);


        $this->authorize('delete', $model);


        $model->delete();

        $this->knowledgeBaseArticleToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Knowledge base deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Knowledge base')"
        :subtitle="__('Manage Knowledge base')"
    >
        <x-slot:actions>
            <flux:button :href="route('knowledge-base.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search...')" />

    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->knowledgeBaseArticles">
            <flux:table.columns>
                <flux:table.column>{{ __('Title') }}</flux:table.column>
                <flux:table.column>{{ __('Slug') }}</flux:table.column>
                <flux:table.column>{{ __('Category') }}</flux:table.column>
                <flux:table.column>{{ __('Is Published') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->knowledgeBaseArticles as $knowledgeBaseArticle)
                    <flux:table.row wire:key="knowledge-base-{{ $knowledgeBaseArticle->id }}">
                        <flux:table.cell>{{ $knowledgeBaseArticle->title ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $knowledgeBaseArticle->slug ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $knowledgeBaseArticle->category ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $knowledgeBaseArticle->is_published ? __('Yes') : __('No') }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('knowledge-base.edit', $knowledgeBaseArticle)" wire:navigate />
                                @can('delete', $knowledgeBaseArticle)
                                <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete({{ $knowledgeBaseArticle->id }})" />
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5">
                            <x-empty-state icon="inbox" :title="__('No records found.')" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal wire:model="showDeleteModal" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Delete') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Are you sure? This action cannot be undone.') }}</flux:text>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                <flux:button variant="danger" wire:click="deleteKnowledgeBaseArticle">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>

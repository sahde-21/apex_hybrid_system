<?php

use App\Models\ManagedDocument;
use App\Services\Documents\ManagedDocumentService;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Recycle Bin')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', ManagedDocument::class);
    }

    #[Computed]
    public function documents()
    {
        return app(ManagedDocumentService::class)->search(auth()->user(), [
            'trashed' => true,
            'q' => $this->search,
        ], 20);
    }

    public function restore(int $id): void
    {
        $document = ManagedDocument::withTrashed()->findOrFail($id);
        $this->authorize('restore', $document);
        app(ManagedDocumentService::class)->restore($id, auth()->user());
        unset($this->documents);
        Flux::toast(variant: 'success', text: __('scf.dms.restored'));
    }

    public function forceDelete(int $id): void
    {
        $document = ManagedDocument::withTrashed()->findOrFail($id);
        $this->authorize('forceDelete', $document);
        app(ManagedDocumentService::class)->forceDelete($document, auth()->user());
        unset($this->documents);
        Flux::toast(variant: 'success', text: __('scf.dms.permanently_deleted'));
    }
}; ?>

<section class="scf-page space-y-6">
    <div class="portal-glass rounded-2xl p-5 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ __('scf.dms.recycle_bin') }}</flux:heading>
                <flux:subheading>{{ __('scf.dms.recycle_subtitle') }}</flux:subheading>
            </div>
            <flux:button :href="route('documents.index')" variant="ghost" icon="arrow-left" wire:navigate>
                {{ __('scf.dms.back_to_center') }}
            </flux:button>
        </div>
        <div class="mt-5 max-w-md">
            <flux:input wire:model.live.debounce.300ms="search" :label="__('scf.dms.search')" icon="magnifying-glass" />
        </div>
    </div>

    <div class="portal-glass overflow-hidden rounded-2xl">
        <table class="min-w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                <tr>
                    <th class="px-4 py-3 text-start">{{ __('scf.dms.document_name') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('scf.dms.deleted_at') }}</th>
                    <th class="px-4 py-3 text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($this->documents as $document)
                    <tr wire:key="trash-{{ $document->id }}">
                        <td class="px-4 py-3">{{ $document->name }}</td>
                        <td class="px-4 py-3 text-zinc-500">{{ $document->deleted_at?->diffForHumans() }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                @can('restore', $document)
                                    <flux:button size="sm" wire:click="restore({{ $document->id }})" variant="ghost">{{ __('scf.dms.restore') }}</flux:button>
                                @endcan
                                @can('forceDelete', $document)
                                    <flux:button size="sm" wire:click="forceDelete({{ $document->id }})" variant="danger">{{ __('scf.dms.delete_forever') }}</flux:button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-10 text-center text-zinc-500">{{ __('scf.dms.recycle_empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t border-zinc-100 px-4 py-3 dark:border-zinc-800">{{ $this->documents->links() }}</div>
    </div>
</section>

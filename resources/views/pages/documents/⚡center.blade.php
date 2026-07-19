<?php

use App\Enums\DocumentCategory;
use App\Models\Branch;
use App\Models\DocumentFolder;
use App\Models\ManagedDocument;
use App\Models\User;
use App\Services\Documents\DocumentFolderService;
use App\Services\Documents\DocumentShareService;
use App\Services\Documents\ManagedDocumentService;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new #[Title('Document Center')] class extends Component {
    use WithFileUploads, WithPagination;

    #[Url]
    public ?int $folder_id = null;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $category = '';

    #[Url]
    public ?int $branch_id = null;

    #[Url]
    public string $department = '';

    #[Url]
    public string $tag = '';

    #[Url]
    public ?int $owner_id = null;

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $uploads = [];

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $versionUpload = null;

    public string $uploadTags = '';

    public string $newFolderName = '';

    public bool $showFolderModal = false;

    public bool $showRenameModal = false;

    public bool $showShareModal = false;

    public bool $showHistoryModal = false;

    public bool $showMoveModal = false;

    public ?int $activeDocumentId = null;

    public string $renameName = '';

    public ?int $moveFolderId = null;

    public string $sharePassword = '';

    public ?string $shareExpiresAt = null;

    public ?int $shareDownloadLimit = null;

    public string $shareUrl = '';

    public function mount(): void
    {
        $this->authorize('viewAny', ManagedDocument::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    public function updatedBranchId(): void
    {
        $this->resetPage();
    }

    public function updatedDepartment(): void
    {
        $this->resetPage();
    }

    public function updatedTag(): void
    {
        $this->resetPage();
    }

    public function updatedOwnerId(): void
    {
        $this->resetPage();
    }

    public function updatedFrom(): void
    {
        $this->resetPage();
    }

    public function updatedTo(): void
    {
        $this->resetPage();
    }

    public function openFolder(?int $id): void
    {
        $this->folder_id = $id;
        $this->resetPage();
    }

    #[Computed]
    public function folders()
    {
        return DocumentFolder::query()
            ->where('parent_id', $this->folder_id)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function allFolders()
    {
        return DocumentFolder::query()->orderBy('name')->get(['id', 'name', 'parent_id']);
    }

    #[Computed]
    public function breadcrumb()
    {
        $trail = collect();
        $current = $this->folder_id
            ? DocumentFolder::query()->find($this->folder_id)
            : null;

        while ($current) {
            $trail->prepend($current);
            $current = $current->parent;
        }

        return $trail;
    }

    #[Computed]
    public function documents()
    {
        return app(ManagedDocumentService::class)->search(auth()->user(), [
            'folder_id' => $this->folder_id,
            'q' => $this->search,
            'category' => $this->category ?: null,
            'branch_id' => $this->branch_id,
            'department' => $this->department ?: null,
            'tag' => $this->tag ?: null,
            'owner_id' => $this->owner_id,
            'from' => $this->from ?: null,
            'to' => $this->to ?: null,
        ], 18);
    }

    #[Computed]
    public function branches()
    {
        return Branch::query()->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function owners()
    {
        return User::query()->orderBy('name')->limit(100)->get(['id', 'name']);
    }

    #[Computed]
    public function categories()
    {
        return DocumentCategory::options();
    }

    #[Computed]
    public function activeDocument()
    {
        return $this->activeDocumentId
            ? ManagedDocument::query()->with(['versions.creator', 'activities.user', 'shares'])->find($this->activeDocumentId)
            : null;
    }

    public function uploadFiles(): void
    {
        $this->authorize('create', ManagedDocument::class);

        $this->validate([
            'uploads' => ['required', 'array', 'min:1'],
            'uploads.*' => ['file', 'max:'.config('documents.max_upload_kb', 51200)],
            'uploadTags' => ['nullable', 'string', 'max:500'],
        ]);

        $tags = collect(explode(',', $this->uploadTags))
            ->map(fn ($tag) => trim($tag))
            ->filter()
            ->values()
            ->all();

        app(ManagedDocumentService::class)->uploadMany(auth()->user(), $this->uploads, [
            'folder_id' => $this->folder_id,
            'category' => $this->category ?: DocumentCategory::General->value,
            'branch_id' => $this->branch_id,
            'department' => $this->department ?: null,
            'tags' => $tags,
        ]);

        $this->uploads = [];
        $this->uploadTags = '';
        unset($this->documents);
        Flux::toast(variant: 'success', text: __('scf.dms.upload_success'));
    }

    public function createFolder(): void
    {
        $this->authorize('create', DocumentFolder::class);

        $this->validate(['newFolderName' => ['required', 'string', 'max:120']]);

        app(DocumentFolderService::class)->createFolder(auth()->user(), [
            'parent_id' => $this->folder_id,
            'name' => $this->newFolderName,
            'category' => $this->category ?: null,
            'branch_id' => $this->branch_id,
            'department' => $this->department ?: null,
        ]);

        $this->newFolderName = '';
        $this->showFolderModal = false;
        unset($this->folders, $this->allFolders);
        Flux::toast(variant: 'success', text: __('scf.dms.folder_created'));
    }

    public function deleteFolder(int $id): void
    {
        $folder = DocumentFolder::query()->findOrFail($id);
        $this->authorize('delete', $folder);
        app(DocumentFolderService::class)->deleteFolder($folder);
        unset($this->folders, $this->allFolders);
        Flux::toast(variant: 'success', text: __('scf.dms.folder_deleted'));
    }

    public function openRename(int $id): void
    {
        $document = ManagedDocument::query()->findOrFail($id);
        $this->authorize('update', $document);
        $this->activeDocumentId = $id;
        $this->renameName = $document->name;
        $this->showRenameModal = true;
    }

    public function renameDocument(): void
    {
        $document = ManagedDocument::query()->findOrFail($this->activeDocumentId);
        $this->authorize('update', $document);

        $this->validate(['renameName' => ['required', 'string', 'max:180']]);

        app(ManagedDocumentService::class)->rename($document, $this->renameName, auth()->user());
        $this->showRenameModal = false;
        unset($this->documents);
        Flux::toast(variant: 'success', text: __('scf.dms.renamed'));
    }

    public function openMove(int $id): void
    {
        $document = ManagedDocument::query()->findOrFail($id);
        $this->authorize('update', $document);
        $this->activeDocumentId = $id;
        $this->moveFolderId = $document->folder_id;
        $this->showMoveModal = true;
    }

    public function moveDocument(): void
    {
        $document = ManagedDocument::query()->findOrFail($this->activeDocumentId);
        $this->authorize('update', $document);

        app(ManagedDocumentService::class)->move($document, $this->moveFolderId ?: null, auth()->user());
        $this->showMoveModal = false;
        unset($this->documents);
        Flux::toast(variant: 'success', text: __('scf.dms.moved'));
    }

    public function deleteDocument(int $id): void
    {
        $document = ManagedDocument::query()->findOrFail($id);
        $this->authorize('delete', $document);
        app(ManagedDocumentService::class)->softDelete($document, auth()->user());
        unset($this->documents);
        Flux::toast(variant: 'success', text: __('scf.dms.moved_to_recycle'));
    }

    public function copyDocument(int $id): void
    {
        $document = ManagedDocument::query()->findOrFail($id);
        $this->authorize('view', $document);
        app(ManagedDocumentService::class)->copy($document, auth()->user(), $this->folder_id);
        unset($this->documents);
        Flux::toast(variant: 'success', text: __('scf.dms.copied'));
    }

    public function openShare(int $id): void
    {
        $document = ManagedDocument::query()->findOrFail($id);
        $this->authorize('share', $document);
        $this->activeDocumentId = $id;
        $this->shareUrl = '';
        $this->showShareModal = true;
    }

    public function createShareLink(): void
    {
        $document = ManagedDocument::query()->findOrFail($this->activeDocumentId);
        $this->authorize('share', $document);

        $share = app(DocumentShareService::class)->createShare($document, auth()->user(), [
            'password' => $this->sharePassword ?: null,
            'expires_at' => $this->shareExpiresAt,
            'download_limit' => $this->shareDownloadLimit,
        ]);

        $this->shareUrl = route('documents.share.show', $share->token);
        Flux::toast(variant: 'success', text: __('scf.dms.share_created'));
    }

    public function openHistory(int $id): void
    {
        $document = ManagedDocument::query()->findOrFail($id);
        $this->authorize('view', $document);
        $this->activeDocumentId = $id;
        $this->versionUpload = null;
        $this->showHistoryModal = true;
    }

    public function uploadNewVersion(): void
    {
        $document = ManagedDocument::query()->findOrFail($this->activeDocumentId);
        $this->authorize('update', $document);

        $this->validate([
            'versionUpload' => ['required', 'file', 'max:'.config('documents.max_upload_kb', 51200)],
        ]);

        app(ManagedDocumentService::class)->newVersion($document, $this->versionUpload, auth()->user());
        $this->versionUpload = null;
        unset($this->documents, $this->activeDocument);
        Flux::toast(variant: 'success', text: __('scf.dms.version_uploaded'));
    }
}; ?>

<section class="scf-page space-y-6">
    <div class="portal-glass rounded-2xl p-5 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-sky-700 dark:text-sky-300">{{ __('scf.dms.brand') }}</p>
                <flux:heading size="xl">{{ __('scf.dms.center_title') }}</flux:heading>
                <flux:subheading>{{ __('scf.dms.center_subtitle') }}</flux:subheading>
            </div>
            <div class="flex flex-wrap gap-2">
                @can('documents.delete')
                    <flux:button :href="route('documents.recycle-bin')" variant="ghost" icon="trash" wire:navigate>
                        {{ __('scf.dms.recycle_bin') }}
                    </flux:button>
                @endcan
                @can('documents.create')
                    <flux:button wire:click="$set('showFolderModal', true)" variant="ghost" icon="folder-plus">
                        {{ __('scf.dms.new_folder') }}
                    </flux:button>
                @endcan
            </div>
        </div>

        <nav class="mt-4 flex flex-wrap items-center gap-2 text-sm text-zinc-500">
            <button type="button" wire:click="openFolder(null)" class="hover:text-sky-600">{{ __('scf.dms.root') }}</button>
            @foreach ($this->breadcrumb as $crumb)
                <span>/</span>
                <button type="button" wire:click="openFolder({{ $crumb->id }})" class="hover:text-sky-600">{{ $crumb->name }}</button>
            @endforeach
        </nav>

        <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-7">
            <flux:input wire:model.live.debounce.300ms="search" :label="__('scf.dms.search')" icon="magnifying-glass" />
            <flux:select wire:model.live="category" :label="__('scf.dms.category')">
                <option value="">{{ __('All') }}</option>
                @foreach ($this->categories as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="branch_id" :label="__('scf.dms.branch')">
                <option value="">{{ __('All') }}</option>
                @foreach ($this->branches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                @endforeach
            </flux:select>
            <flux:input wire:model.live.debounce.300ms="department" :label="__('scf.dms.department')" />
            <flux:input wire:model.live.debounce.300ms="tag" :label="__('scf.dms.tag')" />
            <flux:select wire:model.live="owner_id" :label="__('scf.dms.owner')">
                <option value="">{{ __('All') }}</option>
                @foreach ($this->owners as $owner)
                    <option value="{{ $owner->id }}">{{ $owner->name }}</option>
                @endforeach
            </flux:select>
            <div class="grid grid-cols-2 gap-2 xl:col-span-1 2xl:col-span-1">
                <flux:input type="date" wire:model.live="from" :label="__('scf.dms.from')" />
                <flux:input type="date" wire:model.live="to" :label="__('scf.dms.to')" />
            </div>
        </div>

        @can('documents.create')
            <div
                class="mt-5 rounded-2xl border-2 border-dashed border-sky-200 bg-sky-50/50 p-6 text-center transition hover:border-sky-400 dark:border-sky-900 dark:bg-sky-950/20"
                x-data
                x-on:dragover.prevent
                x-on:drop.prevent="
                    if ($event.dataTransfer.files.length) {
                        $wire.uploadMultiple('uploads', $event.dataTransfer.files);
                    }
                "
            >
                <flux:icon.arrow-up-tray class="mx-auto size-8 text-sky-500" />
                <p class="mt-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('scf.dms.drop_files') }}</p>
                <p class="text-xs text-zinc-500">{{ __('scf.dms.drop_hint') }}</p>
                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    <flux:input wire:model="uploadTags" :label="__('scf.dms.tags_hint')" />
                    <div class="flex flex-wrap items-end justify-center gap-3 md:justify-start">
                        <input type="file" wire:model="uploads" multiple class="text-sm" />
                        <input type="file" wire:model="uploads" multiple webkitdirectory class="text-sm" title="{{ __('scf.dms.folder_upload') }}" />
                        <flux:button wire:click="uploadFiles" variant="primary" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="uploadFiles,uploads">{{ __('scf.dms.upload') }}</span>
                            <span wire:loading wire:target="uploadFiles,uploads">{{ __('scf.dms.uploading') }}</span>
                        </flux:button>
                    </div>
                </div>
                <div wire:loading wire:target="uploads" class="mt-3 h-1.5 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-800">
                    <div class="h-full w-2/3 animate-pulse rounded-full bg-sky-500"></div>
                </div>
            </div>
        @endcan
    </div>

    @if ($this->folders->isNotEmpty())
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($this->folders as $folder)
                <div class="portal-glass flex items-center justify-between gap-3 rounded-2xl p-4" wire:key="folder-{{ $folder->id }}">
                    <button type="button" wire:click="openFolder({{ $folder->id }})" class="flex min-w-0 flex-1 items-center gap-3 text-start">
                        <flux:icon.folder class="size-8 shrink-0 text-amber-500" />
                        <div class="min-w-0">
                            <p class="truncate font-medium text-zinc-900 dark:text-white">{{ $folder->name }}</p>
                            <p class="text-xs text-zinc-500">{{ __('scf.dms.folder') }}</p>
                        </div>
                    </button>
                    @can('delete', $folder)
                        <flux:button size="sm" wire:click="deleteFolder({{ $folder->id }})" variant="ghost" icon="trash" />
                    @endcan
                </div>
            @endforeach
        </div>
    @endif

    <div class="portal-glass overflow-hidden rounded-2xl">
        <div class="grid gap-3 p-4 sm:grid-cols-2 xl:grid-cols-3">
            @forelse ($this->documents as $document)
                <div class="rounded-xl border border-zinc-100 p-4 transition hover:-translate-y-0.5 dark:border-zinc-800" wire:key="doc-{{ $document->id }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate font-medium text-zinc-900 dark:text-white">{{ $document->name }}</p>
                            <p class="truncate text-xs text-zinc-500">{{ $document->original_name }}</p>
                        </div>
                        <span class="rounded-md bg-zinc-100 px-2 py-1 text-xs uppercase dark:bg-zinc-800">{{ $document->extension() }}</span>
                    </div>
                    <p class="mt-2 text-xs text-zinc-500">{{ $document->humanSize() }} · v{{ $document->version }}</p>
                    @if (! empty($document->tags))
                        <div class="mt-2 flex flex-wrap gap-1">
                            @foreach ($document->tags as $docTag)
                                <span class="rounded bg-sky-50 px-1.5 py-0.5 text-[10px] text-sky-700 dark:bg-sky-950 dark:text-sky-300">{{ $docTag }}</span>
                            @endforeach
                        </div>
                    @endif
                    <div class="mt-4 flex flex-wrap gap-2">
                        <flux:button size="sm" :href="route('documents.download', $document)" variant="ghost" icon="arrow-down-tray" />
                        @if ($document->isPreviewable() || $document->isOfficeDocument())
                            <flux:button size="sm" :href="route('documents.preview', $document)" target="_blank" variant="ghost" icon="eye" />
                        @endif
                        @can('update', $document)
                            <flux:button size="sm" wire:click="openRename({{ $document->id }})" variant="ghost" icon="pencil-square" />
                            <flux:button size="sm" wire:click="openMove({{ $document->id }})" variant="ghost" icon="arrows-right-left" />
                        @endcan
                        @can('share', $document)
                            <flux:button size="sm" wire:click="openShare({{ $document->id }})" variant="ghost" icon="link" />
                        @endcan
                        <flux:button size="sm" wire:click="openHistory({{ $document->id }})" variant="ghost" icon="clock" />
                        @can('update', $document)
                            <flux:button size="sm" wire:click="copyDocument({{ $document->id }})" variant="ghost" icon="document-duplicate" />
                        @endcan
                        @can('delete', $document)
                            <flux:button size="sm" wire:click="deleteDocument({{ $document->id }})" variant="ghost" icon="trash" />
                        @endcan
                    </div>
                </div>
            @empty
                <div class="col-span-full px-4 py-12 text-center text-zinc-500">{{ __('scf.dms.no_documents') }}</div>
            @endforelse
        </div>
        <div class="border-t border-zinc-100 px-4 py-3 dark:border-zinc-800">{{ $this->documents->links() }}</div>
    </div>

    <flux:modal wire:model="showFolderModal" class="max-w-md">
        <flux:heading size="lg">{{ __('scf.dms.new_folder') }}</flux:heading>
        <div class="mt-4 space-y-4">
            <flux:input wire:model="newFolderName" :label="__('scf.dms.folder_name')" />
            <div class="flex justify-end gap-2">
                <flux:button wire:click="$set('showFolderModal', false)" variant="ghost">{{ __('Cancel') }}</flux:button>
                <flux:button wire:click="createFolder" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model="showRenameModal" class="max-w-md">
        <flux:heading size="lg">{{ __('scf.dms.rename') }}</flux:heading>
        <div class="mt-4 space-y-4">
            <flux:input wire:model="renameName" :label="__('scf.dms.document_name')" />
            <div class="flex justify-end gap-2">
                <flux:button wire:click="$set('showRenameModal', false)" variant="ghost">{{ __('Cancel') }}</flux:button>
                <flux:button wire:click="renameDocument" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model="showMoveModal" class="max-w-md">
        <flux:heading size="lg">{{ __('scf.dms.move') }}</flux:heading>
        <div class="mt-4 space-y-4">
            <flux:select wire:model="moveFolderId" :label="__('scf.dms.target_folder')">
                <option value="">{{ __('scf.dms.root') }}</option>
                @foreach ($this->allFolders as $folder)
                    <option value="{{ $folder->id }}">{{ $folder->name }}</option>
                @endforeach
            </flux:select>
            <div class="flex justify-end gap-2">
                <flux:button wire:click="$set('showMoveModal', false)" variant="ghost">{{ __('Cancel') }}</flux:button>
                <flux:button wire:click="moveDocument" variant="primary">{{ __('scf.dms.move') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model="showShareModal" class="max-w-lg">
        <flux:heading size="lg">{{ __('scf.dms.share_link') }}</flux:heading>
        <div class="mt-4 space-y-4">
            <flux:input wire:model="sharePassword" type="password" :label="__('scf.dms.share_password')" />
            <flux:input wire:model="shareExpiresAt" type="datetime-local" :label="__('scf.dms.share_expires')" />
            <flux:input wire:model="shareDownloadLimit" type="number" min="1" :label="__('scf.dms.share_limit')" />
            @if ($shareUrl)
                <flux:input :value="$shareUrl" readonly copyable :label="__('scf.dms.share_url')" />
            @endif
            <div class="flex justify-end gap-2">
                <flux:button wire:click="$set('showShareModal', false)" variant="ghost">{{ __('Close') }}</flux:button>
                <flux:button wire:click="createShareLink" variant="primary">{{ __('scf.dms.generate_link') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model="showHistoryModal" class="max-w-2xl">
        <flux:heading size="lg">{{ __('scf.dms.history') }}</flux:heading>
        @if ($this->activeDocument)
            <div class="mt-4 space-y-6">
                @can('update', $this->activeDocument)
                    <div class="rounded-xl border border-zinc-100 p-4 dark:border-zinc-800">
                        <flux:subheading>{{ __('scf.dms.upload_version') }}</flux:subheading>
                        <div class="mt-3 flex flex-wrap items-center gap-3">
                            <input type="file" wire:model="versionUpload" class="text-sm" />
                            <flux:button size="sm" wire:click="uploadNewVersion" variant="primary">{{ __('scf.dms.upload_version') }}</flux:button>
                        </div>
                    </div>
                @endcan
                <div>
                    <flux:subheading>{{ __('scf.dms.versions') }}</flux:subheading>
                    <div class="mt-2 space-y-2">
                        @foreach ($this->activeDocument->versions as $version)
                            <div class="flex items-center justify-between rounded-lg bg-zinc-50 px-3 py-2 text-sm dark:bg-zinc-900">
                                <span>v{{ $version->version }} · {{ number_format($version->size) }} B</span>
                                <span class="text-zinc-500">{{ $version->created_at?->format('Y-m-d H:i') }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div>
                    <flux:subheading>{{ __('scf.dms.activity') }}</flux:subheading>
                    <div class="mt-2 max-h-56 space-y-2 overflow-y-auto">
                        @foreach ($this->activeDocument->activities as $activity)
                            <div class="rounded-lg bg-zinc-50 px-3 py-2 text-sm dark:bg-zinc-900">
                                <span class="font-medium">{{ $activity->action->label() }}</span>
                                <span class="text-zinc-500"> · {{ $activity->created_at?->diffForHumans() }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </flux:modal>
</section>

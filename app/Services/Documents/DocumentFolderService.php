<?php

namespace App\Services\Documents;

use App\Models\DocumentFolder;
use App\Models\User;
use App\Repositories\DocumentFolderRepository;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class DocumentFolderService extends BaseService
{
    public function __construct(
        DocumentFolderRepository $repository,
    ) {
        parent::__construct($repository);
    }

    /**
     * @return Collection<int, DocumentFolder>
     */
    public function tree(?int $parentId = null): Collection
    {
        $cacheKey = 'scf:dms:folders:'.($parentId ?? 'root');

        return Cache::remember($cacheKey, config('documents.cache_ttl'), function () use ($parentId) {
            return DocumentFolder::query()
                ->with(['children' => fn ($q) => $q->orderBy('name')])
                ->where('parent_id', $parentId)
                ->orderBy('name')
                ->get();
        });
    }

    public function createFolder(User $user, array $data): DocumentFolder
    {
        $this->flushFolderCache();

        return DocumentFolder::query()->create([
            ...$data,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    public function renameFolder(DocumentFolder $folder, string $name): DocumentFolder
    {
        $this->flushFolderCache();
        $folder->update(['name' => $name]);

        return $folder->refresh();
    }

    public function moveFolder(DocumentFolder $folder, ?int $parentId): DocumentFolder
    {
        abort_if($parentId === $folder->id, 422, __('scf.dms.cannot_move_into_self'));

        $this->flushFolderCache();
        $folder->update(['parent_id' => $parentId]);

        return $folder->refresh();
    }

    public function deleteFolder(DocumentFolder $folder): bool
    {
        $this->flushFolderCache();

        return (bool) $folder->delete();
    }

    public function restoreFolder(int $id): DocumentFolder
    {
        $this->flushFolderCache();
        $folder = DocumentFolder::withTrashed()->findOrFail($id);
        $folder->restore();

        return $folder;
    }

    protected function flushFolderCache(): void
    {
        Cache::forget('scf:dms:folders:root');
    }
}

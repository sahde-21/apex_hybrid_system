<?php

namespace App\Services\Documents;

use App\Enums\DocumentActivityAction;
use App\Jobs\GenerateDocumentThumbnailJob;
use App\Models\DocumentVersion;
use App\Models\ManagedDocument;
use App\Models\User;
use App\Repositories\ManagedDocumentRepository;
use App\Services\BaseService;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ManagedDocumentService extends BaseService
{
    public function __construct(
        ManagedDocumentRepository $repository,
        protected DocumentActivityService $activity,
    ) {
        parent::__construct($repository);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, ManagedDocument>
     */
    public function search(User $user, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return app(DocumentSearchService::class)->search($user, $filters, $perPage);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function upload(User $user, UploadedFile $file, array $meta = []): ManagedDocument
    {
        $this->validateMime($file);

        $disk = config('documents.disk', 'local');
        $folderSegment = isset($meta['folder_id']) ? 'folder-'.$meta['folder_id'] : 'root';
        $storedPath = $file->store('documents/'.$folderSegment.'/'.now()->format('Y/m'), $disk);
        $realPath = $file->getRealPath();
        $checksum = is_string($realPath) && $realPath !== ''
            ? hash_file('sha256', $realPath)
            : (is_string($storedPath) ? hash_file('sha256', Storage::disk($disk)->path($storedPath)) : '');

        $document = ManagedDocument::query()->create([
            'folder_id' => $meta['folder_id'] ?? null,
            'name' => $meta['name'] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'original_name' => $file->getClientOriginalName(),
            'disk' => $disk,
            'path' => $storedPath,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'category' => $meta['category'] ?? null,
            'tags' => $meta['tags'] ?? [],
            'branch_id' => $meta['branch_id'] ?? null,
            'department' => $meta['department'] ?? null,
            'contact_id' => $meta['contact_id'] ?? null,
            'documentable_type' => $meta['documentable_type'] ?? null,
            'documentable_id' => $meta['documentable_id'] ?? null,
            'version' => 1,
            'checksum' => $checksum,
            'owner_id' => $user->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        DocumentVersion::query()->create([
            'managed_document_id' => $document->id,
            'version' => 1,
            'disk' => $disk,
            'path' => $storedPath,
            'mime_type' => $document->mime_type,
            'size' => $document->size,
            'checksum' => $checksum,
            'created_by' => $user->id,
            'created_at' => now(),
        ]);

        $this->activity->log($document, DocumentActivityAction::Upload, $user);
        $this->flushDocumentCache();
        GenerateDocumentThumbnailJob::dispatch($document->id);

        return $document;
    }

    /**
     * @param  array<int, mixed>  $files
     * @param  array<string, mixed>  $meta
     * @return list<ManagedDocument>
     */
    public function uploadMany(User $user, array $files, array $meta = []): array
    {
        $documents = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $documents[] = $this->upload($user, $file, $meta);
            }
        }

        return $documents;
    }

    public function rename(ManagedDocument $document, string $name, User $user): ManagedDocument
    {
        $old = $document->name;
        $document->update(['name' => $name, 'updated_by' => $user->id]);
        $this->activity->log($document, DocumentActivityAction::Rename, $user, ['from' => $old, 'to' => $name]);
        $this->flushDocumentCache();

        return $document->refresh();
    }

    public function move(ManagedDocument $document, ?int $folderId, User $user): ManagedDocument
    {
        $document->update(['folder_id' => $folderId, 'updated_by' => $user->id]);
        $this->activity->log($document, DocumentActivityAction::Move, $user, ['folder_id' => $folderId]);
        $this->flushDocumentCache();

        return $document->refresh();
    }

    public function copy(ManagedDocument $document, User $user, ?int $folderId = null): ManagedDocument
    {
        $disk = $document->disk;
        $newPath = 'documents/copy/'.now()->format('Y/m').'/'.Str::uuid().'.'.$document->extension();
        Storage::disk($disk)->copy($document->path, $newPath);

        $copy = ManagedDocument::query()->create([
            'folder_id' => $folderId ?? $document->folder_id,
            'name' => $document->name.' ('.__('scf.dms.copy_suffix').')',
            'original_name' => $document->original_name,
            'disk' => $disk,
            'path' => $newPath,
            'mime_type' => $document->mime_type,
            'size' => $document->size,
            'category' => $document->category,
            'tags' => $document->tags,
            'branch_id' => $document->branch_id,
            'department' => $document->department,
            'contact_id' => $document->contact_id,
            'version' => 1,
            'checksum' => $document->checksum,
            'owner_id' => $user->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        DocumentVersion::query()->create([
            'managed_document_id' => $copy->id,
            'version' => 1,
            'disk' => $disk,
            'path' => $newPath,
            'mime_type' => $copy->mime_type,
            'size' => $copy->size,
            'checksum' => $copy->checksum,
            'created_by' => $user->id,
            'created_at' => now(),
        ]);

        $this->activity->log($copy, DocumentActivityAction::Copy, $user, ['source_id' => $document->id]);
        $this->flushDocumentCache();

        return $copy;
    }

    public function softDelete(ManagedDocument $document, User $user): bool
    {
        $this->activity->log($document, DocumentActivityAction::Delete, $user);
        $this->flushDocumentCache();

        return (bool) $document->delete();
    }

    public function restore(int $id, User $user): ManagedDocument
    {
        $document = ManagedDocument::withTrashed()->findOrFail($id);
        $document->restore();
        $this->activity->log($document, DocumentActivityAction::Restore, $user);
        $this->flushDocumentCache();

        return $document;
    }

    public function forceDelete(ManagedDocument $document, User $user): bool
    {
        $this->activity->log($document, DocumentActivityAction::Delete, $user, ['permanent' => true]);

        foreach ($document->versions as $version) {
            Storage::disk($version->disk)->delete($version->path);
        }

        if ($document->thumbnail_path) {
            Storage::disk($document->disk)->delete($document->thumbnail_path);
        }

        Storage::disk($document->disk)->delete($document->path);
        $this->flushDocumentCache();

        return (bool) $document->forceDelete();
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function newVersion(ManagedDocument $document, UploadedFile $file, User $user, array $meta = []): ManagedDocument
    {
        $this->validateMime($file);

        $disk = $document->disk;
        $storedPath = $file->store('documents/versions/'.$document->id, $disk);
        $realPath = $file->getRealPath();
        $checksum = is_string($realPath) && $realPath !== ''
            ? hash_file('sha256', $realPath)
            : (is_string($storedPath) ? hash_file('sha256', Storage::disk($disk)->path($storedPath)) : '');
        $version = $document->version + 1;

        DocumentVersion::query()->create([
            'managed_document_id' => $document->id,
            'version' => $version,
            'disk' => $disk,
            'path' => $storedPath,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'checksum' => $checksum,
            'created_by' => $user->id,
            'created_at' => now(),
        ]);

        $document->update([
            'path' => $storedPath,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'checksum' => $checksum,
            'version' => $version,
            'updated_by' => $user->id,
        ]);

        $this->activity->log($document, DocumentActivityAction::Version, $user, ['version' => $version]);
        $this->flushDocumentCache();
        GenerateDocumentThumbnailJob::dispatch($document->id);

        return $document->refresh();
    }

    public function downloadPath(ManagedDocument $document, User $user): string
    {
        $this->activity->log($document, DocumentActivityAction::Download, $user);

        return Storage::disk($document->disk)->path($document->path);
    }

    public function stream(ManagedDocument $document, User $user): mixed
    {
        $this->activity->log($document, DocumentActivityAction::Preview, $user);

        return Storage::disk($document->disk)->readStream($document->path);
    }

    protected function validateMime(UploadedFile $file): void
    {
        $allowed = config('documents.allowed_mimes', []);
        $mime = $file->getMimeType() ?: $file->getClientMimeType();
        $extension = strtolower($file->getClientOriginalExtension() ?: pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));

        /** @var array<string, list<string>> $extensionMap */
        $extensionMap = [
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword', 'application/octet-stream'],
            'docx' => [
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/zip',
            ],
            'xls' => ['application/vnd.ms-excel', 'application/octet-stream'],
            'xlsx' => [
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/zip',
            ],
            'csv' => ['text/csv', 'text/plain', 'application/csv'],
            'txt' => ['text/plain'],
            'json' => ['application/json', 'text/plain'],
            'zip' => ['application/zip', 'application/x-zip-compressed'],
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'webp' => ['image/webp'],
        ];

        $compatibleMimes = $extensionMap[$extension] ?? null;

        $valid = $mime !== ''
            && is_array($compatibleMimes)
            && in_array($mime, $allowed, true)
            && in_array($mime, $compatibleMimes, true);

        abort_unless($valid, 422, __('scf.dms.invalid_file_type'));
    }

    protected function flushDocumentCache(): void
    {
        Cache::forget('scf:dms:stats');
    }
}

<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\DocumentCategory;
use Database\Factories\ManagedDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int|null $folder_id
 * @property string $name
 * @property string $original_name
 * @property string $disk
 * @property string $path
 * @property string|null $mime_type
 * @property int $size
 * @property DocumentCategory|string|null $category
 * @property array<int, string>|null $tags
 * @property int|null $branch_id
 * @property string|null $department
 * @property int|null $contact_id
 * @property int $version
 * @property string|null $thumbnail_path
 * @property string|null $checksum
 * @property int|null $owner_id
 */
#[Fillable([
    'folder_id',
    'name',
    'original_name',
    'disk',
    'path',
    'mime_type',
    'size',
    'category',
    'tags',
    'branch_id',
    'department',
    'contact_id',
    'documentable_type',
    'documentable_id',
    'version',
    'thumbnail_path',
    'checksum',
    'owner_id',
    'created_by',
    'updated_by',
])]
class ManagedDocument extends Model
{
    /** @use HasFactory<ManagedDocumentFactory> */
    use Auditable, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => DocumentCategory::class,
            'tags' => 'array',
            'size' => 'integer',
            'version' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<DocumentFolder, $this>
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(DocumentFolder::class, 'folder_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasMany<DocumentVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class)->orderByDesc('version');
    }

    /**
     * @return HasMany<DocumentShare, $this>
     */
    public function shares(): HasMany
    {
        return $this->hasMany(DocumentShare::class);
    }

    /**
     * @return HasMany<DocumentActivity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(DocumentActivity::class)->latest('created_at');
    }

    public function isPreviewable(): bool
    {
        return in_array($this->mime_type, config('documents.preview_mimes', []), true);
    }

    public function isOfficeDocument(): bool
    {
        return in_array($this->mime_type, [
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ], true) || in_array($this->extension(), ['doc', 'docx', 'xls', 'xlsx'], true);
    }

    public function humanSize(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, $i > 0 ? 1 : 0).' '.$units[$i];
    }

    public function extension(): string
    {
        return strtolower(pathinfo($this->original_name, PATHINFO_EXTENSION));
    }

    public function thumbnailUrl(): ?string
    {
        if (! $this->thumbnail_path) {
            return null;
        }

        return Storage::disk($this->disk)->url($this->thumbnail_path);
    }
}

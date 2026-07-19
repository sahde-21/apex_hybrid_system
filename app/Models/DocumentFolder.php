<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\DocumentCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property DocumentCategory|string|null $category
 * @property int|null $branch_id
 * @property string|null $department
 * @property int|null $contact_id
 * @property string|null $description
 */
#[Fillable([
    'parent_id',
    'name',
    'category',
    'branch_id',
    'department',
    'contact_id',
    'description',
    'created_by',
    'updated_by',
])]
class DocumentFolder extends Model
{
    use Auditable, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => DocumentCategory::class,
        ];
    }

    /**
     * @return BelongsTo<DocumentFolder, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<DocumentFolder, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return HasMany<ManagedDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(ManagedDocument::class, 'folder_id');
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
}

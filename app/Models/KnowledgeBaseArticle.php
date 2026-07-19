<?php

namespace App\Models;

use App\Concerns\Auditable;
use Database\Factories\KnowledgeBaseArticleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string $category
 * @property string $content
 * @property bool $is_published
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'title',
    'slug',
    'category',
    'content',
    'is_published',
    'created_by',
    'updated_by',
])]
class KnowledgeBaseArticle extends Model
{
    /** @use HasFactory<KnowledgeBaseArticleFactory> */
    use Auditable, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }
}

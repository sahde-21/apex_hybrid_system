<?php

namespace App\Models;

use App\Concerns\Auditable;
use Database\Factories\SupplierEvaluationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $contact_id
 * @property Carbon $evaluation_date
 * @property int $quality_score
 * @property int $delivery_score
 * @property int $price_score
 * @property int $overall_score
 * @property string|null $comments
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'contact_id',
    'evaluation_date',
    'quality_score',
    'delivery_score',
    'price_score',
    'overall_score',
    'comments',
    'created_by',
    'updated_by',
])]
class SupplierEvaluation extends Model
{
    /** @use HasFactory<SupplierEvaluationFactory> */
    use Auditable, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'evaluation_date' => 'date',
            'quality_score' => 'integer',
            'delivery_score' => 'integer',
            'price_score' => 'integer',
            'overall_score' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}

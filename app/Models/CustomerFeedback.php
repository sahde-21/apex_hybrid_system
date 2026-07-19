<?php

namespace App\Models;

use App\Concerns\Auditable;
use Database\Factories\CustomerFeedbackFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $contact_id
 * @property int $rating
 * @property string $subject
 * @property string $feedback
 * @property Carbon $feedback_date
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'contact_id',
    'rating',
    'subject',
    'feedback',
    'feedback_date',
    'created_by',
    'updated_by',
])]
class CustomerFeedback extends Model
{
    /** @use HasFactory<CustomerFeedbackFactory> */
    use Auditable, HasFactory, SoftDeletes;

    /**
     * Laravel treats "feedback" as uncountable, so set the table explicitly.
     */
    protected $table = 'customer_feedbacks';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'feedback_date' => 'date',
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

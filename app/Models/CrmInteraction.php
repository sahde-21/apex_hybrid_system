<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\CrmInteractionType;
use Database\Factories\CrmInteractionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $contact_id
 * @property CrmInteractionType $interaction_type
 * @property string $subject
 * @property string|null $description
 * @property Carbon $interaction_date
 * @property Carbon|null $follow_up_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Contact $contact
 */
#[Fillable([
    'contact_id',
    'interaction_type',
    'subject',
    'description',
    'interaction_date',
    'follow_up_date',
])]
class CrmInteraction extends Model
{
    /** @use HasFactory<CrmInteractionFactory> */
    use Auditable, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'interaction_type' => CrmInteractionType::class,
            'interaction_date' => 'datetime',
            'follow_up_date' => 'date',
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

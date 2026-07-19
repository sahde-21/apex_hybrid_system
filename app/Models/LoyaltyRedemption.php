<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $contact_id
 * @property int $loyalty_program_id
 * @property string $points
 * @property string $reward_label
 * @property string $status
 * @property string|null $notes
 */
#[Fillable([
    'contact_id',
    'loyalty_program_id',
    'points',
    'reward_label',
    'status',
    'notes',
])]
class LoyaltyRedemption extends Model
{
    protected function casts(): array
    {
        return [
            'points' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * @return BelongsTo<LoyaltyProgram, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgram::class, 'loyalty_program_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $rfq_id
 * @property int $contact_id
 * @property string $status
 * @property string|null $quoted_total
 * @property string|null $quoted_tax
 * @property string|null $notes
 * @property Carbon|null $responded_at
 * @property bool $is_selected
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Rfq $rfq
 * @property-read Contact $contact
 */
#[Fillable([
    'rfq_id',
    'contact_id',
    'status',
    'quoted_total',
    'quoted_tax',
    'notes',
    'responded_at',
    'is_selected',
])]
class RfqVendor extends Model
{
    protected function casts(): array
    {
        return [
            'quoted_total' => 'decimal:2',
            'quoted_tax' => 'decimal:2',
            'responded_at' => 'datetime',
            'is_selected' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Rfq, $this>
     */
    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class);
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}

<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\BillStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference_number
 * @property int|null $contact_id
 * @property Carbon $bill_date
 * @property Carbon|null $due_date
 * @property BillStatus $status
 * @property string $total_amount
 * @property string $tax_amount
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Contact|null $contact
 */
#[Fillable([
    'reference_number',
    'contact_id',
    'bill_date',
    'due_date',
    'status',
    'total_amount',
    'tax_amount',
    'notes',
])]
class Bill extends Model
{
    use Auditable, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bill_date' => 'date',
            'due_date' => 'date',
            'status' => BillStatus::class,
            'total_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
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

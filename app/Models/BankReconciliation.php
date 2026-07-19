<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\BankReconciliationStatus;
use Database\Factories\BankReconciliationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference_number
 * @property string $bank_name
 * @property Carbon $statement_date
 * @property string $opening_balance
 * @property string $closing_balance
 * @property BankReconciliationStatus $status
 * @property string|null $notes
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'reference_number',
    'bank_name',
    'statement_date',
    'opening_balance',
    'closing_balance',
    'status',
    'notes',
    'created_by',
    'updated_by',
])]
class BankReconciliation extends Model
{
    /** @use HasFactory<BankReconciliationFactory> */
    use Auditable, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'statement_date' => 'date',
            'opening_balance' => 'decimal:2',
            'closing_balance' => 'decimal:2',
            'status' => BankReconciliationStatus::class,
        ];
    }
}

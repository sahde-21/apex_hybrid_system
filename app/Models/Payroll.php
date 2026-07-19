<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\PayrollStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference_number
 * @property int $employee_id
 * @property Carbon $pay_period_start
 * @property Carbon $pay_period_end
 * @property string $gross_amount
 * @property string $deductions
 * @property string $net_amount
 * @property PayrollStatus $status
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Employee $employee
 */
#[Fillable([
    'reference_number',
    'employee_id',
    'pay_period_start',
    'pay_period_end',
    'gross_amount',
    'deductions',
    'net_amount',
    'status',
    'notes',
])]
class Payroll extends Model
{
    use Auditable, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pay_period_start' => 'date',
            'pay_period_end' => 'date',
            'gross_amount' => 'decimal:2',
            'deductions' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'status' => PayrollStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}

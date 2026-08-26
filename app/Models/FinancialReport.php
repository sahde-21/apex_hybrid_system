<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\FinancialReportStatus;
use App\Enums\FinancialReportType;
use Database\Factories\FinancialReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference_number
 * @property string $name
 * @property FinancialReportType $report_type
 * @property Carbon $period_start
 * @property Carbon $period_end
 * @property FinancialReportStatus $status
 * @property string $total_revenue
 * @property string $total_expenses
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'reference_number',
    'name',
    'report_type',
    'period_start',
    'period_end',
    'status',
    'total_revenue',
    'total_expenses',
    'notes',
])]
class FinancialReport extends Model
{
    /** @use HasFactory<FinancialReportFactory> */
    use Auditable, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'report_type' => FinancialReportType::class,
            'period_start' => 'date',
            'period_end' => 'date',
            'status' => FinancialReportStatus::class,
            'total_revenue' => 'decimal:2',
            'total_expenses' => 'decimal:2',
        ];
    }
}

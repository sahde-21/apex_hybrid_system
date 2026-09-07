<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\AccountType;
use App\Enums\NormalBalance;
use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property int|null $parent_id
 * @property AccountType $type
 * @property NormalBalance $normal_balance
 * @property string $currency_code
 * @property int|null $branch_id
 * @property bool $is_active
 * @property bool $is_system
 * @property bool $allow_manual_entry
 * @property string|null $system_key
 * @property string|null $description
 * @property string $opening_balance
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Account|null $parent
 * @property-read Collection<int, Account> $children
 * @property-read Branch|null $branch
 * @property-read Collection<int, JournalEntryLine> $lines
 */
#[Fillable([
    'code',
    'name',
    'parent_id',
    'type',
    'normal_balance',
    'currency_code',
    'branch_id',
    'is_active',
    'is_system',
    'allow_manual_entry',
    'system_key',
    'description',
    'opening_balance',
    'created_by',
    'updated_by',
])]
class Account extends Model
{
    /** @use HasFactory<AccountFactory> */
    use Auditable, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
            'normal_balance' => NormalBalance::class,
            'is_active' => 'boolean',
            'is_system' => 'boolean',
            'allow_manual_entry' => 'boolean',
            'opening_balance' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Account, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return HasMany<JournalEntryLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function label(): string
    {
        return $this->code.' — '.$this->name;
    }
}

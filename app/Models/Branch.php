<?php

namespace App\Models;

use App\Concerns\Auditable;
use Database\Factories\BranchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|null $address
 * @property string|null $phone
 * @property string|null $email
 * @property bool $is_active
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'name',
    'code',
    'address',
    'phone',
    'email',
    'is_active',
    'created_by',
    'updated_by',
])]
class Branch extends Model
{
    /** @use HasFactory<BranchFactory> */
    use Auditable, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<SaleOrder, $this>
     */
    public function saleOrders(): HasMany
    {
        return $this->hasMany(SaleOrder::class);
    }

    /**
     * @return HasMany<PurchaseOrder, $this>
     */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /**
     * @return HasMany<ProductionOrder, $this>
     */
    public function productionOrders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class);
    }

    /**
     * @return HasMany<PosRegister, $this>
     */
    public function posRegisters(): HasMany
    {
        return $this->hasMany(PosRegister::class);
    }

    /**
     * @return HasMany<Attendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * @return HasMany<Shift, $this>
     */
    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    /**
     * @return HasMany<FloorPlan, $this>
     */
    public function floorPlans(): HasMany
    {
        return $this->hasMany(FloorPlan::class);
    }

    /**
     * @return HasMany<Budget, $this>
     */
    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    /**
     * @return HasMany<FixedAsset, $this>
     */
    public function fixedAssets(): HasMany
    {
        return $this->hasMany(FixedAsset::class);
    }

    /**
     * @return HasMany<Account, $this>
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    /**
     * @return HasMany<JournalEntry, $this>
     */
    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    /**
     * @return HasMany<JournalEntryLine, $this>
     */
    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    /**
     * @return HasMany<DocumentFolder, $this>
     */
    public function documentFolders(): HasMany
    {
        return $this->hasMany(DocumentFolder::class);
    }

    /**
     * @return HasMany<ManagedDocument, $this>
     */
    public function managedDocuments(): HasMany
    {
        return $this->hasMany(ManagedDocument::class);
    }

    /**
     * @return HasMany<AccountingAuditLog, $this>
     */
    public function accountingAuditLogs(): HasMany
    {
        return $this->hasMany(AccountingAuditLog::class);
    }

    /**
     * @return HasMany<IntelligenceSnapshot, $this>
     */
    public function intelligenceSnapshots(): HasMany
    {
        return $this->hasMany(IntelligenceSnapshot::class);
    }

    /**
     * @return HasMany<IntelligenceAlert, $this>
     */
    public function intelligenceAlerts(): HasMany
    {
        return $this->hasMany(IntelligenceAlert::class);
    }

    /**
     * @return HasMany<IntelligenceRecommendation, $this>
     */
    public function intelligenceRecommendations(): HasMany
    {
        return $this->hasMany(IntelligenceRecommendation::class);
    }
}

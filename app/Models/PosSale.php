<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\PosSaleStatus;
use Database\Factories\PosSaleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference_number
 * @property int $pos_shift_id
 * @property int $pos_register_id
 * @property int $user_id
 * @property int|null $contact_id
 * @property int|null $invoice_id
 * @property int|null $coupon_id
 * @property int|null $original_sale_id
 * @property PosSaleStatus $status
 * @property bool $is_return
 * @property string $subtotal_amount
 * @property string $discount_amount
 * @property string $tax_amount
 * @property string $total_amount
 * @property string $loyalty_points_earned
 * @property string $loyalty_points_redeemed
 * @property bool $cash_drawer_opened
 * @property string|null $notes
 * @property array<string, mixed>|null $meta
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'reference_number',
    'pos_shift_id',
    'pos_register_id',
    'user_id',
    'contact_id',
    'invoice_id',
    'coupon_id',
    'original_sale_id',
    'status',
    'is_return',
    'subtotal_amount',
    'discount_amount',
    'tax_amount',
    'total_amount',
    'loyalty_points_earned',
    'loyalty_points_redeemed',
    'cash_drawer_opened',
    'notes',
    'meta',
    'created_by',
    'updated_by',
])]
class PosSale extends Model
{
    /** @use HasFactory<PosSaleFactory> */
    use Auditable, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PosSaleStatus::class,
            'is_return' => 'boolean',
            'subtotal_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'loyalty_points_earned' => 'decimal:2',
            'loyalty_points_redeemed' => 'decimal:2',
            'cash_drawer_opened' => 'boolean',
            'meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<PosShift, $this>
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(PosShift::class, 'pos_shift_id');
    }

    /**
     * @return BelongsTo<PosRegister, $this>
     */
    public function register(): BelongsTo
    {
        return $this->belongsTo(PosRegister::class, 'pos_register_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<Coupon, $this>
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * @return BelongsTo<PosSale, $this>
     */
    public function originalSale(): BelongsTo
    {
        return $this->belongsTo(PosSale::class, 'original_sale_id');
    }

    /**
     * @return HasMany<PosSaleItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PosSaleItem::class);
    }

    /**
     * @return HasMany<PosSalePayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(PosSalePayment::class);
    }

    /**
     * @return HasMany<PosSale, $this>
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(PosSale::class, 'original_sale_id');
    }
}

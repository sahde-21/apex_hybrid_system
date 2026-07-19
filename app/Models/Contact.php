<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\ContactType;
use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property ContactType $type
 * @property string|null $company_name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $tax_number
 * @property string $opening_balance
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name',
    'type',
    'company_name',
    'email',
    'phone',
    'address',
    'tax_number',
    'opening_balance',
])]
class Contact extends Model
{
    /** @use HasFactory<ContactFactory> */
    use Auditable, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ContactType::class,
            'opening_balance' => 'decimal:2',
        ];
    }

    public function isReceivable(): bool
    {
        return (float) $this->opening_balance > 0;
    }

    public function isPayable(): bool
    {
        return (float) $this->opening_balance < 0;
    }

    public function balanceLabel(): string
    {
        $balance = (float) $this->opening_balance;

        if ($balance > 0) {
            return __('Receivable');
        }

        if ($balance < 0) {
            return __('Payable');
        }

        return __('Settled');
    }

    public function balanceColor(): string
    {
        if ($this->isReceivable()) {
            return 'green';
        }

        if ($this->isPayable()) {
            return 'red';
        }

        return 'zinc';
    }

    /**
     * @return HasOne<PortalCustomer, $this>
     */
    public function portalCustomer(): HasOne
    {
        return $this->hasOne(PortalCustomer::class);
    }

    /**
     * @return HasOne<PortalSupplier, $this>
     */
    public function portalSupplier(): HasOne
    {
        return $this->hasOne(PortalSupplier::class);
    }

    /**
     * @return HasMany<PurchaseOrder, $this>
     */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /**
     * @return HasMany<Bill, $this>
     */
    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    /**
     * @return HasMany<SaleOrder, $this>
     */
    public function saleOrders(): HasMany
    {
        return $this->hasMany(SaleOrder::class);
    }

    /**
     * @return HasMany<Quotation, $this>
     */
    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasMany<Ticket, $this>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * @return HasMany<GiftCard, $this>
     */
    public function giftCards(): HasMany
    {
        return $this->hasMany(GiftCard::class);
    }

    /**
     * @return HasMany<LoyaltyBalance, $this>
     */
    public function loyaltyBalances(): HasMany
    {
        return $this->hasMany(LoyaltyBalance::class);
    }

    /**
     * @return HasMany<Contract, $this>
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }
}

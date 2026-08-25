<?php

namespace App\Enums;

enum StockMovementType: string
{
    case OpeningBalance = 'opening_balance';
    case PurchaseReceipt = 'purchase_receipt';
    case PurchaseReturn = 'purchase_return';
    case SalesReserve = 'sales_reserve';
    case SalesRelease = 'sales_release';
    case SalesFulfillment = 'sales_fulfillment';
    case SalesReturn = 'sales_return';
    case PosSale = 'pos_sale';
    case PosRefund = 'pos_refund';
    case Adjustment = 'adjustment';
    case Damage = 'damage';
    case Expiry = 'expiry';
    case Loss = 'loss';
    case TransferShip = 'transfer_ship';
    case TransferReceive = 'transfer_receive';
    case StocktakeVariance = 'stocktake_variance';
    case ManufactureIssue = 'manufacture_issue';
    case ManufactureReceipt = 'manufacture_receipt';
    case Reversal = 'reversal';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type) => [$type->value => $type->label()])
            ->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::OpeningBalance => __('Opening Balance'),
            self::PurchaseReceipt => __('Purchase Receipt'),
            self::PurchaseReturn => __('Purchase Return'),
            self::SalesReserve => __('Sales Reserve'),
            self::SalesRelease => __('Sales Release'),
            self::SalesFulfillment => __('Sales Fulfillment'),
            self::SalesReturn => __('Sales Return'),
            self::PosSale => __('POS Sale'),
            self::PosRefund => __('POS Refund'),
            self::Adjustment => __('Adjustment'),
            self::Damage => __('Damage'),
            self::Expiry => __('Expiry'),
            self::Loss => __('Loss'),
            self::TransferShip => __('Transfer Ship'),
            self::TransferReceive => __('Transfer Receive'),
            self::StocktakeVariance => __('Stocktake Variance'),
            self::ManufactureIssue => __('Manufacture Issue'),
            self::ManufactureReceipt => __('Manufacture Receipt'),
            self::Reversal => __('Reversal'),
        };
    }
}

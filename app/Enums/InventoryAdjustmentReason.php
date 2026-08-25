<?php

namespace App\Enums;

enum InventoryAdjustmentReason: string
{
    case Correction = 'correction';
    case Found = 'found';
    case Other = 'other';
    case Damage = 'damage';
    case Expiry = 'expiry';
    case Loss = 'loss';
    case StocktakeVariance = 'stocktake_variance';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $reason) => [$reason->value => $reason->label()])
            ->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::Correction => __('Correction'),
            self::Found => __('Found'),
            self::Other => __('Other'),
            self::Damage => __('Damage'),
            self::Expiry => __('Expiry'),
            self::Loss => __('Loss'),
            self::StocktakeVariance => __('Stocktake Variance'),
        };
    }

    public function movementType(): StockMovementType
    {
        return match ($this) {
            self::Correction, self::Found, self::Other => StockMovementType::Adjustment,
            self::Damage => StockMovementType::Damage,
            self::Expiry => StockMovementType::Expiry,
            self::Loss => StockMovementType::Loss,
            self::StocktakeVariance => StockMovementType::StocktakeVariance,
        };
    }
}

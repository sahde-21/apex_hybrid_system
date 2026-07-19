<?php

namespace App\Enums;

enum PosPaymentMethod: string
{
    case Cash = 'cash';
    case Card = 'card';
    case BankTransfer = 'bank_transfer';
    case GiftCard = 'gift_card';
    case Loyalty = 'loyalty';
    case Other = 'other';

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $method) => [$method->value => $method->label()])
            ->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::Cash => __('Cash'),
            self::Card => __('Card'),
            self::BankTransfer => __('Bank transfer'),
            self::GiftCard => __('Gift card'),
            self::Loyalty => __('Loyalty points'),
            self::Other => __('Other'),
        };
    }

    public function opensCashDrawer(): bool
    {
        return $this === self::Cash;
    }
}

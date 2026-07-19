<?php

namespace App\Enums;

enum PosSaleStatus: string
{
    case Completed = 'completed';
    case Refunded = 'refunded';
    case PartiallyRefunded = 'partially_refunded';
    case Voided = 'voided';

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status) => [$status->value => $status->label()])
            ->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::Completed => __('Completed'),
            self::Refunded => __('Refunded'),
            self::PartiallyRefunded => __('Partially refunded'),
            self::Voided => __('Voided'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Completed => 'green',
            self::Refunded => 'red',
            self::PartiallyRefunded => 'amber',
            self::Voided => 'zinc',
        };
    }
}

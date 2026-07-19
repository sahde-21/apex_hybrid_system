<?php

namespace App\Enums;

enum ContactType: string
{
    case Customer = 'customer';
    case Supplier = 'supplier';
    case Both = 'both';

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
            self::Customer => __('Customer'),
            self::Supplier => __('Supplier'),
            self::Both => __('Customer & Supplier'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Customer => 'blue',
            self::Supplier => 'purple',
            self::Both => 'green',
        };
    }
}

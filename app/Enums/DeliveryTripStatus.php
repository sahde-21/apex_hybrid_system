<?php

namespace App\Enums;

enum DeliveryTripStatus: string
{
    case Planned = 'planned';
    case InTransit = 'in_transit';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status) => [$status->value => $status->label()])
            ->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::Planned => __('Planned'),
            self::InTransit => __('In Transit'),
            self::Delivered => __('Delivered'),
            self::Cancelled => __('Cancelled'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Planned => 'zinc',
            self::InTransit => 'blue',
            self::Delivered => 'amber',
            self::Cancelled => 'green',
        };
    }
}

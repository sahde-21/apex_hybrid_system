<?php

namespace App\Enums;

enum SupplierShipmentStatus: string
{
    case Scheduled = 'scheduled';
    case Shipped = 'shipped';
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
            self::Scheduled => __('Scheduled'),
            self::Shipped => __('Shipped'),
            self::InTransit => __('In transit'),
            self::Delivered => __('Delivered'),
            self::Cancelled => __('Cancelled'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Scheduled => 'zinc',
            self::Shipped => 'blue',
            self::InTransit => 'amber',
            self::Delivered => 'green',
            self::Cancelled => 'red',
        };
    }
}

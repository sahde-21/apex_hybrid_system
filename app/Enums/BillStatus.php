<?php

namespace App\Enums;

enum BillStatus: string
{
    case Draft = 'draft';
    case Received = 'received';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';

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
            self::Draft => __('Draft'),
            self::Received => __('Received'),
            self::Paid => __('Paid'),
            self::Overdue => __('Overdue'),
            self::Cancelled => __('Cancelled'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'zinc',
            self::Received => 'blue',
            self::Paid => 'green',
            self::Overdue => 'amber',
            self::Cancelled => 'red',
        };
    }
}

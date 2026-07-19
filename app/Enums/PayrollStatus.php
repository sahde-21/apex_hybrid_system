<?php

namespace App\Enums;

enum PayrollStatus: string
{
    case Draft = 'draft';
    case Processed = 'processed';
    case Paid = 'paid';

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
            self::Processed => __('Processed'),
            self::Paid => __('Paid'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'zinc',
            self::Processed => 'blue',
            self::Paid => 'green',
        };
    }
}

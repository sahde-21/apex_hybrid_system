<?php

namespace App\Enums;

enum PosShiftStatus: string
{
    case Open = 'open';
    case Closed = 'closed';

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
            self::Open => __('Open'),
            self::Closed => __('Closed'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'green',
            self::Closed => 'zinc',
        };
    }
}

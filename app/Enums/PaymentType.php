<?php

namespace App\Enums;

enum PaymentType: string
{
    case Incoming = 'incoming';
    case Outgoing = 'outgoing';

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type) => [$type->value => $type->label()])
            ->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::Incoming => __('Incoming'),
            self::Outgoing => __('Outgoing'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Incoming => 'green',
            self::Outgoing => 'red',
        };
    }
}

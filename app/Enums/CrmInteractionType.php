<?php

namespace App\Enums;

enum CrmInteractionType: string
{
    case Call = 'call';
    case Email = 'email';
    case Meeting = 'meeting';
    case Note = 'note';

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
            self::Call => __('Call'),
            self::Email => __('Email'),
            self::Meeting => __('Meeting'),
            self::Note => __('Note'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Call => 'blue',
            self::Email => 'purple',
            self::Meeting => 'green',
            self::Note => 'zinc',
        };
    }
}

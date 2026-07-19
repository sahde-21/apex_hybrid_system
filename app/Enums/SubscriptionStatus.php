<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

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
            self::Active => __('Active'),
            self::Paused => __('Paused'),
            self::Cancelled => __('Cancelled'),
            self::Expired => __('Expired'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'zinc',
            self::Paused => 'blue',
            self::Cancelled => 'amber',
            self::Expired => 'green',
        };
    }
}

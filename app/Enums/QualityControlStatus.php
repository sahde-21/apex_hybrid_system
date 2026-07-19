<?php

namespace App\Enums;

enum QualityControlStatus: string
{
    case Pending = 'pending';
    case Passed = 'passed';
    case Failed = 'failed';
    case Rework = 'rework';

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
            self::Pending => __('Pending'),
            self::Passed => __('Passed'),
            self::Failed => __('Failed'),
            self::Rework => __('Rework'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'zinc',
            self::Passed => 'blue',
            self::Failed => 'amber',
            self::Rework => 'green',
        };
    }
}

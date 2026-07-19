<?php

namespace App\Enums;

enum LeadStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Qualified = 'qualified';
    case Converted = 'converted';
    case Lost = 'lost';

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
            self::New => __('New'),
            self::Contacted => __('Contacted'),
            self::Qualified => __('Qualified'),
            self::Converted => __('Converted'),
            self::Lost => __('Lost'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'zinc',
            self::Contacted => 'blue',
            self::Qualified => 'amber',
            self::Converted => 'green',
            self::Lost => 'red',
        };
    }
}

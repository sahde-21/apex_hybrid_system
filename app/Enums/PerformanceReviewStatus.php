<?php

namespace App\Enums;

enum PerformanceReviewStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Completed = 'completed';

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
            self::Draft => __('Draft'),
            self::Submitted => __('Submitted'),
            self::Completed => __('Completed'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'zinc',
            self::Submitted => 'blue',
            self::Completed => 'amber',
        };
    }
}

<?php

namespace App\Enums;

enum FinancialReportStatus: string
{
    case Draft = 'draft';
    case Generated = 'generated';

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
            self::Generated => __('Generated'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'zinc',
            self::Generated => 'green',
        };
    }
}

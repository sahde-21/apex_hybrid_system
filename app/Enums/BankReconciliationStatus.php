<?php

namespace App\Enums;

enum BankReconciliationStatus: string
{
    case Draft = 'draft';
    case InProgress = 'in_progress';
    case Reconciled = 'reconciled';
    case Cancelled = 'cancelled';

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
            self::InProgress => __('In Progress'),
            self::Reconciled => __('Reconciled'),
            self::Cancelled => __('Cancelled'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'zinc',
            self::InProgress => 'blue',
            self::Reconciled => 'amber',
            self::Cancelled => 'green',
        };
    }
}

<?php

namespace App\Enums;

enum InventoryAdjustmentStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Posted = 'posted';
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
            self::Approved => __('Approved'),
            self::Posted => __('Posted'),
            self::Cancelled => __('Cancelled'),
        };
    }

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    public function canApprove(): bool
    {
        return $this === self::Draft;
    }

    public function canPost(): bool
    {
        return $this === self::Approved;
    }

    public function canCancel(): bool
    {
        return in_array($this, [self::Draft, self::Approved], true);
    }
}

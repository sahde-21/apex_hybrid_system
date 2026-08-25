<?php

namespace App\Enums;

enum StockTransferStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case InTransit = 'in_transit';
    case Completed = 'completed';
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
            self::Pending => __('Pending'),
            self::InTransit => __('In Transit'),
            self::Completed => __('Completed'),
            self::Cancelled => __('Cancelled'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'zinc',
            self::Pending => 'blue',
            self::InTransit => 'amber',
            self::Completed => 'green',
            self::Cancelled => 'red',
        };
    }

    /**
     * Semantic UI label (enum values remain draft/pending/in_transit/completed/cancelled).
     */
    public function workflowLabel(): string
    {
        return match ($this) {
            self::Draft => __('Draft'),
            self::Pending => __('Approved'),
            self::InTransit => __('Shipped'),
            self::Completed => __('Received'),
            self::Cancelled => __('Cancelled'),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function workflowOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status) => [$status->value => $status->workflowLabel()])
            ->all();
    }

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    public function canApprove(): bool
    {
        return $this === self::Draft;
    }

    public function canShip(): bool
    {
        return $this === self::Pending;
    }

    public function canReceive(): bool
    {
        return $this === self::InTransit;
    }

    public function canCancel(): bool
    {
        return in_array($this, [self::Draft, self::Pending], true);
    }
}

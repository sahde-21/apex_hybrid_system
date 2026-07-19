<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Reversed = 'reversed';
    case Cancelled = 'cancelled';

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
            self::Draft => __('scf.sales_workflow.status_draft'),
            self::Posted => __('scf.sales_workflow.status_posted'),
            self::Reversed => __('scf.sales_workflow.status_reversed'),
            self::Cancelled => __('scf.sales_workflow.status_cancelled'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'zinc',
            self::Posted => 'green',
            self::Reversed, self::Cancelled => 'red',
        };
    }

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Posted, self::Cancelled],
            self::Posted => [self::Reversed],
            self::Reversed, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}

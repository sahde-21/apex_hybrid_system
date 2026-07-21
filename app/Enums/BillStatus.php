<?php

namespace App\Enums;

enum BillStatus: string
{
    case Draft = 'draft';
    case Received = 'received'; // issued
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Void = 'void';
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
            self::Draft => __('scf.purchase_workflow.status_draft'),
            self::Received => __('scf.purchase_workflow.status_issued'),
            self::PartiallyPaid => __('scf.purchase_workflow.status_partially_paid'),
            self::Paid => __('scf.purchase_workflow.status_paid'),
            self::Overdue => __('scf.purchase_workflow.status_overdue'),
            self::Void => __('scf.purchase_workflow.status_void'),
            self::Cancelled => __('scf.purchase_workflow.status_cancelled'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'zinc',
            self::Received => 'blue',
            self::PartiallyPaid => 'amber',
            self::Paid => 'green',
            self::Overdue => 'amber',
            self::Void, self::Cancelled => 'red',
        };
    }

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Received, self::PartiallyPaid, self::Overdue], true);
    }

    public function isPosted(): bool
    {
        return in_array($this, [self::Received, self::PartiallyPaid, self::Paid, self::Overdue], true);
    }

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Received, self::Cancelled],
            self::Received => [self::PartiallyPaid, self::Paid, self::Overdue, self::Void, self::Cancelled],
            self::PartiallyPaid => [self::Paid, self::Overdue, self::Void],
            self::Overdue => [self::PartiallyPaid, self::Paid, self::Void],
            self::Paid => [self::Void],
            self::Void, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}

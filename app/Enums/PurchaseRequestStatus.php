<?php

namespace App\Enums;

enum PurchaseRequestStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Converted = 'converted';
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
            self::Submitted => __('scf.purchase_workflow.status_submitted'),
            self::Approved => __('scf.purchase_workflow.status_approved'),
            self::Rejected => __('scf.purchase_workflow.status_rejected'),
            self::Converted => __('scf.purchase_workflow.status_converted'),
            self::Cancelled => __('scf.purchase_workflow.status_cancelled'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'zinc',
            self::Submitted => 'amber',
            self::Approved, self::Converted => 'green',
            self::Rejected, self::Cancelled => 'red',
        };
    }

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Submitted, self::Cancelled],
            self::Submitted => [self::Approved, self::Rejected, self::Cancelled],
            self::Approved => [self::Converted, self::Cancelled],
            self::Rejected, self::Converted, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}

<?php

namespace App\Enums;

enum PurchaseOrderStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Confirmed = 'confirmed';
    case Received = 'received';
    case PartiallyBilled = 'partially_billed';
    case FullyBilled = 'fully_billed';
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
            self::Draft => __('scf.purchase_workflow.status_draft'),
            self::PendingApproval => __('scf.purchase_workflow.status_pending_approval'),
            self::Approved => __('scf.purchase_workflow.status_approved'),
            self::Confirmed => __('scf.purchase_workflow.status_confirmed'),
            self::Received => __('scf.purchase_workflow.status_received'),
            self::PartiallyBilled => __('scf.purchase_workflow.status_partially_billed'),
            self::FullyBilled => __('scf.purchase_workflow.status_fully_billed'),
            self::Cancelled => __('scf.purchase_workflow.status_cancelled'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'zinc',
            self::PendingApproval => 'amber',
            self::Approved, self::Confirmed => 'blue',
            self::Received, self::FullyBilled => 'green',
            self::PartiallyBilled => 'amber',
            self::Cancelled => 'red',
        };
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::PendingApproval], true);
    }

    public function canBill(): bool
    {
        return in_array($this, [
            self::Confirmed,
            self::Received,
            self::Approved,
            self::PartiallyBilled,
        ], true);
    }

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::PendingApproval, self::Cancelled],
            self::PendingApproval => [self::Approved, self::Draft, self::Cancelled],
            self::Approved => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::Received, self::PartiallyBilled, self::FullyBilled, self::Cancelled],
            self::Received => [self::PartiallyBilled, self::FullyBilled, self::Cancelled],
            self::PartiallyBilled => [self::FullyBilled, self::Cancelled],
            self::FullyBilled, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}

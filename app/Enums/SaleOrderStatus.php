<?php

namespace App\Enums;

enum SaleOrderStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Confirmed = 'confirmed';
    case PartiallyFulfilled = 'partially_fulfilled';
    case Fulfilled = 'fulfilled';
    case Delivered = 'delivered';
    case PartiallyInvoiced = 'partially_invoiced';
    case Invoiced = 'invoiced';
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
            self::Draft => __('scf.sales_workflow.status_draft'),
            self::PendingApproval => __('scf.sales_workflow.status_pending_approval'),
            self::Approved => __('scf.sales_workflow.status_approved'),
            self::Confirmed => __('scf.sales_workflow.status_confirmed'),
            self::PartiallyFulfilled => __('scf.sales_workflow.status_partially_fulfilled'),
            self::Fulfilled, self::Delivered => __('scf.sales_workflow.status_fulfilled'),
            self::PartiallyInvoiced => __('scf.sales_workflow.status_partially_invoiced'),
            self::Invoiced => __('scf.sales_workflow.status_invoiced'),
            self::Cancelled => __('scf.sales_workflow.status_cancelled'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'zinc',
            self::PendingApproval => 'amber',
            self::Approved, self::Confirmed => 'blue',
            self::PartiallyFulfilled, self::PartiallyInvoiced => 'amber',
            self::Fulfilled, self::Delivered, self::Invoiced => 'green',
            self::Cancelled => 'red',
        };
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::PendingApproval], true);
    }

    public function canInvoice(): bool
    {
        return in_array($this, [
            self::Confirmed,
            self::PartiallyFulfilled,
            self::Fulfilled,
            self::Delivered,
            self::PartiallyInvoiced,
            self::Approved,
        ], true);
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::PendingApproval, self::Cancelled],
            self::PendingApproval => [self::Approved, self::Draft, self::Cancelled],
            self::Approved => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::PartiallyFulfilled, self::Fulfilled, self::Delivered, self::PartiallyInvoiced, self::Invoiced, self::Cancelled],
            self::PartiallyFulfilled => [self::Fulfilled, self::PartiallyInvoiced, self::Invoiced, self::Cancelled],
            self::Fulfilled, self::Delivered => [self::PartiallyInvoiced, self::Invoiced, self::Cancelled],
            self::PartiallyInvoiced => [self::Invoiced, self::Cancelled],
            self::Invoiced, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}

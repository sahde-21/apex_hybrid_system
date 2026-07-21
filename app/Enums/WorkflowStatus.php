<?php

namespace App\Enums;

/**
 * Canonical status vocabulary for the enterprise workflow engine.
 * Module definitions may use a subset (and aliases like "pending").
 */
enum WorkflowStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Confirmed = 'confirmed';
    case Posted = 'posted';
    case Cancelled = 'cancelled';
    case Closed = 'closed';
    case Archived = 'archived';

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
            self::Draft => __('scf.workflow.status_draft'),
            self::Submitted => __('scf.workflow.status_submitted'),
            self::PendingApproval => __('scf.workflow.status_pending_approval'),
            self::Approved => __('scf.workflow.status_approved'),
            self::Rejected => __('scf.workflow.status_rejected'),
            self::Confirmed => __('scf.workflow.status_confirmed'),
            self::Posted => __('scf.workflow.status_posted'),
            self::Cancelled => __('scf.workflow.status_cancelled'),
            self::Closed => __('scf.workflow.status_closed'),
            self::Archived => __('scf.workflow.status_archived'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'zinc',
            self::Submitted, self::PendingApproval => 'amber',
            self::Approved, self::Confirmed, self::Posted, self::Closed => 'green',
            self::Rejected, self::Cancelled => 'red',
            self::Archived => 'zinc',
        };
    }
}

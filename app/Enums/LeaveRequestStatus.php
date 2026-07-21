<?php

namespace App\Enums;

enum LeaveRequestStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Closed = 'closed';
    case Archived = 'archived';

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
            self::Draft => __('scf.workflow.status_draft'),
            self::Pending => __('scf.workflow.status_pending_approval'),
            self::Approved => __('scf.workflow.status_approved'),
            self::Rejected => __('scf.workflow.status_rejected'),
            self::Cancelled => __('scf.workflow.status_cancelled'),
            self::Closed => __('scf.workflow.status_closed'),
            self::Archived => __('scf.workflow.status_archived'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'zinc',
            self::Pending => 'amber',
            self::Approved, self::Closed => 'green',
            self::Rejected, self::Cancelled => 'red',
            self::Archived => 'zinc',
        };
    }

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }
}

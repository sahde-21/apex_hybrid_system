<?php

namespace App\Enums;

enum WorkflowApprovalStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('scf.workflow.status_pending'),
            self::Approved => __('scf.workflow.status_approved'),
            self::Rejected => __('scf.workflow.status_rejected'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Approved => 'green',
            self::Rejected => 'red',
        };
    }
}

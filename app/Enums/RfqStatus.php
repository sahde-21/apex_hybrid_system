<?php

namespace App\Enums;

enum RfqStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case VendorResponse = 'vendor_response';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Expired = 'expired';
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
            self::Sent => __('scf.purchase_workflow.status_sent'),
            self::VendorResponse => __('scf.purchase_workflow.status_vendor_response'),
            self::Accepted => __('scf.purchase_workflow.status_accepted'),
            self::Rejected => __('scf.purchase_workflow.status_rejected'),
            self::Expired => __('scf.purchase_workflow.status_expired'),
            self::Converted => __('scf.purchase_workflow.status_converted'),
            self::Cancelled => __('scf.purchase_workflow.status_cancelled'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'zinc',
            self::Sent => 'blue',
            self::VendorResponse => 'amber',
            self::Accepted, self::Converted => 'green',
            self::Rejected, self::Cancelled => 'red',
            self::Expired => 'amber',
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
            self::Draft => [self::Sent, self::Cancelled],
            self::Sent => [self::VendorResponse, self::Accepted, self::Rejected, self::Expired, self::Cancelled],
            self::VendorResponse => [self::Accepted, self::Rejected, self::Expired, self::Cancelled],
            self::Accepted => [self::Converted, self::Cancelled],
            self::Rejected, self::Expired, self::Converted, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}

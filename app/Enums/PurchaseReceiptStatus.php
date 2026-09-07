<?php

namespace App\Enums;

enum PurchaseReceiptStatus: string
{
    case Posted = 'posted';

    public function label(): string
    {
        return match ($this) {
            self::Posted => __('scf.purchase_workflow.status_posted'),
        };
    }
}

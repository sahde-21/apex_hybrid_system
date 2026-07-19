<?php

namespace App\Enums;

enum NormalBalance: string
{
    case Debit = 'debit';
    case Credit = 'credit';

    public function label(): string
    {
        return match ($this) {
            self::Debit => __('scf.accounting_engine.debit'),
            self::Credit => __('scf.accounting_engine.credit'),
        };
    }
}

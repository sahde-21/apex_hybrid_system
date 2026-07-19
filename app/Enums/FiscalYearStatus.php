<?php

namespace App\Enums;

enum FiscalYearStatus: string
{
    case Open = 'open';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => __('scf.accounting_engine.year_open'),
            self::Closed => __('scf.accounting_engine.year_closed'),
        };
    }
}

<?php

namespace App\Enums;

enum FiscalPeriodStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
    case Locked = 'locked';

    public function label(): string
    {
        return match ($this) {
            self::Open => __('scf.accounting_engine.period_open'),
            self::Closed => __('scf.accounting_engine.period_closed'),
            self::Locked => __('scf.accounting_engine.period_locked'),
        };
    }

    public function allowsPosting(): bool
    {
        return $this === self::Open;
    }
}

<?php

namespace App\Enums;

enum ActivityVisibility: string
{
    case Public = 'public';
    case Internal = 'internal';

    public function label(): string
    {
        return match ($this) {
            self::Public => __('scf.activity.visibility_public'),
            self::Internal => __('scf.activity.visibility_internal'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Public => 'zinc',
            self::Internal => 'amber',
        };
    }
}

<?php

namespace App\Enums;

enum DocumentActivityAction: string
{
    case Upload = 'upload';
    case Download = 'download';
    case Delete = 'delete';
    case Restore = 'restore';
    case Rename = 'rename';
    case Move = 'move';
    case Copy = 'copy';
    case Share = 'share';
    case Print = 'print';
    case Preview = 'preview';
    case Version = 'version';

    public function label(): string
    {
        return match ($this) {
            self::Upload => __('scf.dms.action_upload'),
            self::Download => __('scf.dms.action_download'),
            self::Delete => __('scf.dms.action_delete'),
            self::Restore => __('scf.dms.action_restore'),
            self::Rename => __('scf.dms.action_rename'),
            self::Move => __('scf.dms.action_move'),
            self::Copy => __('scf.dms.action_copy'),
            self::Share => __('scf.dms.action_share'),
            self::Print => __('scf.dms.action_print'),
            self::Preview => __('scf.dms.action_preview'),
            self::Version => __('scf.dms.action_version'),
        };
    }
}

<?php

namespace App\Enums;

enum NotificationCategory: string
{
    case Success = 'success';
    case Warning = 'warning';
    case Error = 'error';
    case Information = 'information';
    case Critical = 'critical';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::Success => __('scf.notification_center.category_success'),
            self::Warning => __('scf.notification_center.category_warning'),
            self::Error => __('scf.notification_center.category_error'),
            self::Information => __('scf.notification_center.category_information'),
            self::Critical => __('scf.notification_center.category_critical'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Success => 'green',
            self::Warning => 'amber',
            self::Error => 'red',
            self::Information => 'sky',
            self::Critical => 'rose',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Success => 'check-circle',
            self::Warning => 'exclamation-triangle',
            self::Error => 'x-circle',
            self::Information => 'information-circle',
            self::Critical => 'shield-exclamation',
        };
    }
}

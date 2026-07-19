<?php

namespace App\Enums;

enum NotificationPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
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
            self::Low => __('scf.notification_center.priority_low'),
            self::Medium => __('scf.notification_center.priority_medium'),
            self::High => __('scf.notification_center.priority_high'),
            self::Critical => __('scf.notification_center.priority_critical'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Low => 'zinc',
            self::Medium => 'sky',
            self::High => 'amber',
            self::Critical => 'red',
        };
    }
}

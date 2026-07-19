<?php

namespace App\Enums;

enum ProjectTaskStatus: string
{
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case Review = 'review';
    case Done = 'done';
    case Cancelled = 'cancelled';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status) => [$status->value => $status->label()])
            ->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::Todo => __('Todo'),
            self::InProgress => __('In Progress'),
            self::Review => __('Review'),
            self::Done => __('Done'),
            self::Cancelled => __('Cancelled'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Todo => 'zinc',
            self::InProgress => 'blue',
            self::Review => 'amber',
            self::Done => 'green',
            self::Cancelled => 'red',
        };
    }
}

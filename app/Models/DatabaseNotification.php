<?php

namespace App\Models;

use App\Enums\NotificationCategory;
use App\Enums\NotificationPriority;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification as BaseDatabaseNotification;

class DatabaseNotification extends BaseDatabaseNotification
{
    protected $fillable = [
        'id',
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'category',
        'priority',
        'module',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
            'category' => NotificationCategory::class,
            'priority' => NotificationPriority::class,
        ];
    }

    public function title(): string
    {
        return (string) ($this->data['title'] ?? __('Notification'));
    }

    public function body(): ?string
    {
        $body = $this->data['body'] ?? null;

        return is_string($body) ? $body : null;
    }

    public function actionUrl(): ?string
    {
        $url = $this->data['action_url'] ?? null;

        return is_string($url) ? $url : null;
    }

    public function eventType(): string
    {
        return (string) ($this->data['event'] ?? 'general');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeRead(Builder $query): Builder
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $like = '%'.$term.'%';

        return $query->where(function (Builder $q) use ($like): void {
            $q->where('data', 'like', $like)
                ->orWhere('module', 'like', $like)
                ->orWhere('category', 'like', $like)
                ->orWhere('priority', 'like', $like);
        });
    }
}

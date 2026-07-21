<?php

namespace App\Jobs;

use App\Enums\NotificationCategory;
use App\Enums\NotificationPriority;
use App\Models\User;
use App\Notifications\ErpNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class DeliverNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries;

    public int $timeout;

    /**
     * @param  list<int>  $recipientIds
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public array $recipientIds,
        public string $event,
        public string $title,
        public ?string $body,
        public string $category,
        public string $priority,
        public string $module,
        public ?string $actionUrl,
        public array $meta = [],
    ) {
        $this->tries = (int) config('performance.queue.retries', 3);
        $this->timeout = (int) config('performance.queue.timeout', 120);
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $users = User::query()->whereIn('id', $this->recipientIds)->get();

        if ($users->isEmpty()) {
            return;
        }

        $notification = new ErpNotification(
            event: $this->event,
            title: $this->title,
            body: $this->body,
            category: NotificationCategory::from($this->category),
            priority: NotificationPriority::from($this->priority),
            module: $this->module,
            actionUrl: $this->actionUrl,
            meta: $this->meta,
        );

        Notification::send($users, $notification);
    }
}

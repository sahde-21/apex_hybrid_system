<?php

namespace App\Services\Notifications;

use App\Enums\NotificationCategory;
use App\Enums\NotificationPriority;
use App\Models\AuditLog;
use App\Models\DatabaseNotification;
use App\Models\User;
use App\Notifications\ErpNotification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class NotificationCenterService
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function notify(
        User|Authenticatable|iterable $recipients,
        string $event,
        string $title,
        ?string $body = null,
        NotificationCategory $category = NotificationCategory::Information,
        NotificationPriority $priority = NotificationPriority::Medium,
        string $module = 'system',
        ?string $actionUrl = null,
        array $meta = [],
    ): void {
        $users = $this->normalizeRecipients($recipients);

        if ($users->isEmpty()) {
            return;
        }

        $notification = new ErpNotification(
            event: $event,
            title: $title,
            body: $body,
            category: $category,
            priority: $priority,
            module: $module,
            actionUrl: $actionUrl,
            meta: $meta,
        );

        Notification::send($users, $notification);

        $this->audit('notification.dispatched', [
            'event' => $event,
            'module' => $module,
            'category' => $category->value,
            'priority' => $priority->value,
            'recipients' => $users->pluck('id')->all(),
            'title' => $title,
        ]);
    }

    /**
     * Notify all active users that have the given permission (or privileged roles).
     *
     * @param  array<string, mixed>  $meta
     */
    public function notifyByPermission(
        string $permission,
        string $event,
        string $title,
        ?string $body = null,
        NotificationCategory $category = NotificationCategory::Information,
        NotificationPriority $priority = NotificationPriority::Medium,
        string $module = 'system',
        ?string $actionUrl = null,
        array $meta = [],
    ): void {
        $users = User::query()
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->get()
            ->filter(fn (User $user) => $user->can($permission));

        $this->notify($users, $event, $title, $body, $category, $priority, $module, $actionUrl, $meta);
    }

    /**
     * @param  list<string>  $roles
     * @param  array<string, mixed>  $meta
     */
    public function notifyRoles(
        array $roles,
        string $event,
        string $title,
        ?string $body = null,
        NotificationCategory $category = NotificationCategory::Information,
        NotificationPriority $priority = NotificationPriority::Medium,
        string $module = 'system',
        ?string $actionUrl = null,
        array $meta = [],
    ): void {
        $users = User::role($roles)->where('is_active', true)->get();

        $this->notify($users, $event, $title, $body, $category, $priority, $module, $actionUrl, $meta);
    }

    public function unreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    public function markAsRead(User $user, string $notificationId): void
    {
        $notification = $this->ownedQuery($user)->findOrFail($notificationId);
        $notification->markAsRead();

        $this->audit('notification.read', [
            'notification_id' => $notificationId,
            'user_id' => $user->id,
        ], $user->id);
    }

    public function markAllAsRead(User $user): int
    {
        $count = $user->unreadNotifications()->update(['read_at' => now()]);

        $this->audit('notification.read_all', [
            'user_id' => $user->id,
            'count' => $count,
        ], $user->id);

        return $count;
    }

    public function delete(User $user, string $notificationId): void
    {
        $notification = $this->ownedQuery($user)->findOrFail($notificationId);
        $notification->delete();

        $this->audit('notification.deleted', [
            'notification_id' => $notificationId,
            'user_id' => $user->id,
        ], $user->id);
    }

    /**
     * @return Builder<DatabaseNotification>
     */
    public function queryFor(User $user): Builder
    {
        return DatabaseNotification::query()
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->getKey())
            ->latest();
    }

    /**
     * @return Builder<DatabaseNotification>
     */
    protected function ownedQuery(User $user): Builder
    {
        return $this->queryFor($user);
    }

    /**
     * @param  User|Authenticatable|iterable<int, User|Authenticatable>  $recipients
     * @return Collection<int, User>
     */
    protected function normalizeRecipients(User|Authenticatable|iterable $recipients): Collection
    {
        if ($recipients instanceof User) {
            return collect([$recipients]);
        }

        if ($recipients instanceof Authenticatable) {
            return $recipients instanceof User ? collect([$recipients]) : collect();
        }

        return collect($recipients)->filter(fn ($r) => $r instanceof User)->values();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function audit(string $action, array $payload, ?int $userId = null): void
    {
        if (! class_exists(AuditLog::class)) {
            return;
        }

        AuditLog::query()->create([
            'user_id' => $userId ?? auth('web')->id(),
            'auditable_type' => DatabaseNotification::class,
            'auditable_id' => 0,
            'action' => $action,
            'old_values' => null,
            'new_values' => $payload,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}

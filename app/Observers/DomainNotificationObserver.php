<?php

namespace App\Observers;

use App\Enums\NotificationCategory;
use App\Enums\NotificationPriority;
use App\Services\Notifications\NotificationCenterService;
use Illuminate\Database\Eloquent\Model;

/**
 * Emits staff ERP notifications for important domain model lifecycle events.
 */
class DomainNotificationObserver
{
    public function __construct(
        protected NotificationCenterService $notifications,
    ) {}

    public function created(Model $model): void
    {
        if (! config('notifications.domain_enabled', true)) {
            return;
        }

        $this->dispatch($model, 'created');
    }

    public function updated(Model $model): void
    {
        if (! config('notifications.domain_enabled', true)) {
            return;
        }

        if (! $model->wasChanged()) {
            return;
        }

        // Prefer status transitions; otherwise notify on meaningful updates once.
        if ($model->wasChanged('status') || $model->wasChanged('is_active')) {
            $this->dispatch($model, 'updated');
        }
    }

    public function deleted(Model $model): void
    {
        if (! config('notifications.domain_enabled', true)) {
            return;
        }

        $this->dispatch($model, 'deleted', NotificationCategory::Warning, NotificationPriority::High);
    }

    protected function dispatch(
        Model $model,
        string $action,
        ?NotificationCategory $category = null,
        ?NotificationPriority $priority = null,
    ): void {
        $class = $model::class;
        $map = config('notifications.domain.'.$class);

        if (! is_array($map)) {
            return;
        }

        $module = (string) ($map['module'] ?? 'system');
        $permission = (string) ($map['permission'] ?? $module.'.read');
        $label = (string) ($map['label'] ?? class_basename($model));
        $reference = $this->reference($model);
        $route = $this->resolveRoute($map['route'] ?? null, $model);

        $category ??= match ($action) {
            'created' => NotificationCategory::Success,
            'deleted' => NotificationCategory::Warning,
            default => NotificationCategory::Information,
        };

        $priority ??= match ($action) {
            'deleted' => NotificationPriority::High,
            default => NotificationPriority::Medium,
        };

        if ($model->wasChanged('status')) {
            $status = $model->getAttribute('status');
            $statusLabel = $status instanceof \BackedEnum ? $status->value : (string) $status;
            $title = __(':label :ref status → :status', [
                'label' => $label,
                'ref' => $reference,
                'status' => $statusLabel,
            ]);
            $priority = NotificationPriority::High;
            $category = NotificationCategory::Information;
        } else {
            $title = match ($action) {
                'created' => __(':label created: :ref', ['label' => $label, 'ref' => $reference]),
                'deleted' => __(':label deleted: :ref', ['label' => $label, 'ref' => $reference]),
                default => __(':label updated: :ref', ['label' => $label, 'ref' => $reference]),
            };
        }

        $this->notifications->notifyByPermission(
            permission: $permission,
            event: $module.'.'.$action,
            title: $title,
            body: $label.' · '.$reference,
            category: $category,
            priority: $priority,
            module: $module,
            actionUrl: $route,
            meta: [
                'model' => $class,
                'id' => $model->getKey(),
                'action' => $action,
            ],
        );
    }

    protected function reference(Model $model): string
    {
        foreach (['reference_number', 'code', 'name', 'title', 'subject', 'email'] as $attr) {
            $value = $model->getAttribute($attr);
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return '#'.$model->getKey();
    }

    /**
     * @param  string|null  $routeName
     */
    protected function resolveRoute(?string $routeName, Model $model): ?string
    {
        if ($routeName === null || $routeName === '') {
            return null;
        }

        try {
            if (str_ends_with($routeName, '.show') || str_ends_with($routeName, '.edit')) {
                return route($routeName, $model);
            }

            return route($routeName);
        } catch (\Throwable) {
            return null;
        }
    }
}

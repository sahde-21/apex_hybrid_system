<?php

namespace App\Services\Workflow;

use App\Contracts\Workflow\Workflowable;
use App\Enums\NotificationCategory;
use App\Enums\NotificationPriority;
use App\Models\User;
use App\Services\Notifications\NotificationCenterService;
use App\Support\Workflow\WorkflowDefinition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

class WorkflowNotifier
{
    public function __construct(
        protected NotificationCenterService $notifications,
    ) {}

    public function notifyTransition(
        Workflowable&Model $document,
        WorkflowDefinition $definition,
        string $event,
        User $actor,
        string $from,
        string $to,
        ?string $comment,
        ?string $permission = null,
    ): void {
        $title = __('scf.workflow.notify_'.$event.'_title', [
            'document' => class_basename($document),
            'ref' => $document->getAttribute('reference_number') ?? $document->getKey(),
        ]);

        $body = __('scf.workflow.notify_'.$event.'_body', [
            'from' => $from,
            'to' => $to,
            'user' => $actor->name,
            'comment' => $comment ?: '—',
        ]);

        $actionUrl = null;
        if ($definition->showRoute() && Route::has($definition->showRoute())) {
            $actionUrl = route($definition->showRoute(), $document);
        }

        $meta = [
            'definition' => $definition->key,
            'action' => $event,
            'from_status' => $from,
            'to_status' => $to,
            'document_type' => $document->getMorphClass(),
            'document_id' => $document->getKey(),
            'actor_id' => $actor->id,
        ];

        if ($permission) {
            $this->notifications->notifyByPermission(
                permission: $permission,
                event: 'workflow.'.$event,
                title: $title,
                body: $body,
                category: NotificationCategory::Workflow,
                priority: NotificationPriority::Medium,
                module: $definition->module(),
                actionUrl: $actionUrl,
                meta: $meta,
            );

            return;
        }

        $this->notifications->notify(
            recipients: $actor,
            event: 'workflow.'.$event,
            title: $title,
            body: $body,
            category: NotificationCategory::Workflow,
            priority: NotificationPriority::Medium,
            module: $definition->module(),
            actionUrl: $actionUrl,
            meta: $meta,
        );
    }
}

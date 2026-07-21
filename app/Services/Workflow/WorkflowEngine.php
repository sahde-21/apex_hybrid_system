<?php

namespace App\Services\Workflow;

use App\Contracts\Workflow\Workflowable;
use App\Models\User;
use App\Models\WorkflowHistory;
use App\Models\WorkflowInstance;
use App\Support\Workflow\WorkflowDefinition;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkflowEngine
{
    public function __construct(
        protected ApprovalEngine $approvals,
        protected WorkflowNotifier $notifier,
    ) {}

    public function ensureInstance(Workflowable $document): WorkflowInstance
    {
        $existing = $document->workflowInstance;

        if ($existing) {
            return $existing;
        }

        $definition = WorkflowDefinition::fromConfig($document->workflowDefinitionKey());

        return WorkflowInstance::query()->create([
            'definition_key' => $definition->key,
            'document_type' => $document->getMorphClass(),
            'document_id' => $document->getKey(),
            'current_status' => $document->workflowStatus() ?: $definition->initialStatus(),
            'current_approval_level' => 0,
            'approval_mode' => null,
            'meta' => [],
        ]);
    }

    /**
     * @return list<array{action: string, label: string, requires_comment: bool, approval: bool}>
     */
    public function availableActions(Workflowable $document, User $user): array
    {
        $definition = WorkflowDefinition::fromConfig($document->workflowDefinitionKey());
        $status = $document->workflowStatus();
        $actions = [];

        foreach ($definition->availableActionsFor($status) as $action) {
            $rule = $definition->transition($action);
            if ($rule === null) {
                continue;
            }

            if (! $this->userMayPerform($user, $rule['permissions'] ?? [])) {
                continue;
            }

            // For multi-level approve, hide if user already acted at their pending level without permission for current level
            if ($action === 'approve' && ! empty($rule['approval'])) {
                $instance = $document->workflowInstance;
                if ($instance && ! $this->approvals->userCanActOnCurrentLevel($instance, $user, $rule['approval'])) {
                    continue;
                }
            }

            $actions[] = [
                'action' => $action,
                'label' => __('scf.workflow.action_'.$action),
                'requires_comment' => (bool) ($rule['requires_comment'] ?? false),
                'approval' => ! empty($rule['approval']),
            ];
        }

        return $actions;
    }

    public function can(Workflowable $document, User $user, string $action): bool
    {
        foreach ($this->availableActions($document, $user) as $available) {
            if ($available['action'] === $action) {
                return true;
            }
        }

        return false;
    }

    public function apply(Workflowable $document, User $user, string $action, ?string $comment = null): WorkflowInstance
    {
        return DB::transaction(function () use ($document, $user, $action, $comment) {
            $definition = WorkflowDefinition::fromConfig($document->workflowDefinitionKey());
            $rule = $definition->transition($action);

            if ($rule === null) {
                throw ValidationException::withMessages([
                    'action' => [__('scf.workflow.unknown_action', ['action' => $action])],
                ]);
            }

            if (! $this->userMayPerform($user, $rule['permissions'] ?? [])) {
                abort(403);
            }

            if (($rule['requires_comment'] ?? false) && blank($comment)) {
                throw ValidationException::withMessages([
                    'comment' => [__('scf.workflow.comment_required')],
                ]);
            }

            $instance = WorkflowInstance::query()
                ->where('document_type', $document->getMorphClass())
                ->where('document_id', $document->getKey())
                ->lockForUpdate()
                ->first();

            if (! $instance) {
                $instance = $this->ensureInstance($document);
                $instance = WorkflowInstance::query()->whereKey($instance->id)->lockForUpdate()->firstOrFail();
            }

            $from = $document->workflowStatus();

            if (! in_array($from, $rule['from'] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => [__('scf.workflow.invalid_transition', [
                        'action' => $action,
                        'from' => $from,
                    ])],
                ]);
            }

            $to = (string) $rule['to'];
            $approvalConfig = $rule['approval'] ?? null;
            $approvalLevel = null;
            $approvalLevelName = null;
            $statusChanged = true;

            if (is_array($approvalConfig) && $action === 'approve') {
                $result = $this->approvals->recordApproval($instance, $user, $action, $approvalConfig, $comment);
                $approvalLevel = $result['level'];
                $approvalLevelName = $result['level_name'];
                $statusChanged = $result['complete'];

                if (! $statusChanged) {
                    $this->recordHistory($instance, $user, $action, $from, $from, $comment, $approvalLevel, $approvalLevelName, [
                        'partial' => true,
                        'mode' => $approvalConfig['mode'] ?? 'sequential',
                    ]);

                    return $instance->fresh(['histories.user', 'approvals.user']);
                }
            }

            if (($rule['clears_approvals'] ?? false) === true) {
                $this->approvals->clear($instance);
            }

            $document->setWorkflowStatus($to);
            $instance->update([
                'current_status' => $to,
                'current_approval_level' => 0,
                'approval_mode' => null,
            ]);

            $this->recordHistory($instance, $user, $action, $from, $to, $comment, $approvalLevel, $approvalLevelName);

            if (! empty($rule['notify'])) {
                $this->notifier->notifyTransition(
                    $document,
                    $definition,
                    (string) $rule['notify'],
                    $user,
                    $from,
                    $to,
                    $comment,
                    $rule['notify_permission'] ?? null,
                );
            }

            return $instance->fresh(['histories.user', 'approvals.user']);
        });
    }

    /**
     * @param  list<string>  $permissions
     */
    protected function userMayPerform(User $user, array $permissions): bool
    {
        if ($permissions === []) {
            return true;
        }

        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>|null  $meta
     */
    protected function recordHistory(
        WorkflowInstance $instance,
        User $user,
        string $action,
        ?string $from,
        ?string $to,
        ?string $comment,
        ?int $approvalLevel = null,
        ?string $approvalLevelName = null,
        ?array $meta = null,
    ): WorkflowHistory {
        return WorkflowHistory::query()->create([
            'workflow_instance_id' => $instance->id,
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'comment' => $comment,
            'approval_level' => $approvalLevel,
            'approval_level_name' => $approvalLevelName,
            'user_id' => $user->id,
            'meta' => $meta,
            'created_at' => now(),
        ]);
    }
}

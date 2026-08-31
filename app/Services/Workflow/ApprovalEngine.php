<?php

namespace App\Services\Workflow;

use App\Enums\WorkflowApprovalStatus;
use App\Models\User;
use App\Models\WorkflowApproval;
use App\Models\WorkflowInstance;
use Illuminate\Validation\ValidationException;

class ApprovalEngine
{
    /**
     * @param  array{mode?: string, levels?: list<array{name: string, label?: string, permissions?: list<string>}>}  $config
     * @return array{complete: bool, level: int, level_name: string}
     */
    public function recordApproval(WorkflowInstance $instance, User $user, string $action, array $config, ?string $comment = null): array
    {
        $mode = $config['mode'] ?? 'single';
        $levels = $config['levels'] ?? [];

        if ($levels === []) {
            return ['complete' => true, 'level' => 1, 'level_name' => 'default'];
        }

        $this->ensureApprovalRows($instance, $action, $mode, $levels);

        if ($mode === 'parallel') {
            return $this->recordParallel($instance, $user, $action, $levels, $comment);
        }

        return $this->recordSequential($instance, $user, $action, $levels, $comment);
    }

    /**
     * @param  array{mode?: string, levels?: list<array{name: string, label?: string, permissions?: list<string>}>}  $config
     */
    public function userCanActOnCurrentLevel(WorkflowInstance $instance, User $user, array $config): bool
    {
        $mode = $config['mode'] ?? 'single';
        $levels = $config['levels'] ?? [];

        if ($levels === []) {
            return true;
        }

        if ($mode === 'parallel') {
            foreach ($levels as $index => $level) {
                $row = $this->findApproval($instance, 'approve', $index + 1);
                if ($row && $row->status === WorkflowApprovalStatus::Pending && $this->userHasLevelPermission($user, $level)) {
                    return true;
                }
            }

            // No rows yet — user may start if they match any level
            foreach ($levels as $level) {
                if ($this->userHasLevelPermission($user, $level)) {
                    return true;
                }
            }

            return false;
        }

        $current = max(1, (int) $instance->current_approval_level ?: 1);
        if ($instance->current_approval_level === 0) {
            $current = 1;
        }

        $level = $levels[$current - 1] ?? null;
        if ($level === null) {
            return false;
        }

        $row = $this->findApproval($instance, 'approve', $current);
        if ($row && $row->status !== WorkflowApprovalStatus::Pending) {
            return false;
        }

        return $this->userHasLevelPermission($user, $level);
    }

    public function clear(WorkflowInstance $instance): void
    {
        $instance->approvals()->delete();
        $instance->update([
            'current_approval_level' => 0,
            'approval_mode' => null,
        ]);
    }

    /**
     * @param  list<array{name: string, label?: string, permissions?: list<string>}>  $levels
     */
    protected function ensureApprovalRows(WorkflowInstance $instance, string $action, string $mode, array $levels): void
    {
        if ($instance->approvals()->where('action', $action)->exists()) {
            return;
        }

        foreach ($levels as $index => $level) {
            WorkflowApproval::query()->create([
                'workflow_instance_id' => $instance->id,
                'action' => $action,
                'level' => $index + 1,
                'level_name' => $level['name'],
                'status' => WorkflowApprovalStatus::Pending,
            ]);
        }

        $instance->update([
            'approval_mode' => $mode,
            'current_approval_level' => $mode === 'sequential' ? 1 : 0,
        ]);
    }

    /**
     * @param  list<array{name: string, label?: string, permissions?: list<string>}>  $levels
     * @return array{complete: bool, level: int, level_name: string}
     */
    protected function recordSequential(WorkflowInstance $instance, User $user, string $action, array $levels, ?string $comment): array
    {
        $current = max(1, (int) $instance->current_approval_level ?: 1);
        $levelConfig = $levels[$current - 1] ?? null;

        if ($levelConfig === null) {
            throw ValidationException::withMessages([
                'approval' => [__('scf.workflow.approval_complete')],
            ]);
        }

        if (! $this->userHasLevelPermission($user, $levelConfig)) {
            abort(403);
        }

        $row = $this->findApproval($instance, $action, $current);
        if (! $row || $row->status !== WorkflowApprovalStatus::Pending) {
            throw ValidationException::withMessages([
                'approval' => [__('scf.workflow.approval_already_acted')],
            ]);
        }

        $row->update([
            'status' => WorkflowApprovalStatus::Approved,
            'user_id' => $user->id,
            'comment' => $comment,
            'acted_at' => now(),
        ]);

        $complete = $current >= count($levels);

        if (! $complete) {
            $instance->update(['current_approval_level' => $current + 1]);
        }

        return [
            'complete' => $complete,
            'level' => $current,
            'level_name' => $levelConfig['name'],
        ];
    }

    /**
     * @param  list<array{name: string, label?: string, permissions?: list<string>}>  $levels
     * @return array{complete: bool, level: int, level_name: string}
     */
    protected function recordParallel(WorkflowInstance $instance, User $user, string $action, array $levels, ?string $comment): array
    {
        $actedLevel = null;
        $actedName = null;

        foreach ($levels as $index => $levelConfig) {
            if (! $this->userHasLevelPermission($user, $levelConfig)) {
                continue;
            }

            $level = $index + 1;
            $row = $this->findApproval($instance, $action, $level);

            if (! $row || $row->status !== WorkflowApprovalStatus::Pending) {
                continue;
            }

            $row->update([
                'status' => WorkflowApprovalStatus::Approved,
                'user_id' => $user->id,
                'comment' => $comment,
                'acted_at' => now(),
            ]);

            $actedLevel = $level;
            $actedName = $levelConfig['name'];
            break;
        }

        if ($actedLevel === null) {
            throw ValidationException::withMessages([
                'approval' => [__('scf.workflow.approval_no_pending_level')],
            ]);
        }

        $pending = $instance->approvals()
            ->where('action', $action)
            ->where('status', WorkflowApprovalStatus::Pending->value)
            ->exists();

        return [
            'complete' => ! $pending,
            'level' => $actedLevel,
            'level_name' => $actedName,
        ];
    }

    protected function findApproval(WorkflowInstance $instance, string $action, int $level): ?WorkflowApproval
    {
        return WorkflowApproval::query()
            ->where('workflow_instance_id', $instance->id)
            ->where('action', $action)
            ->where('level', $level)
            ->first();
    }

    /**
     * @param  array{name: string, permissions?: list<string>}  $level
     */
    protected function userHasLevelPermission(User $user, array $level): bool
    {
        $permissions = $level['permissions'] ?? ['workflow.approve'];

        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }
}

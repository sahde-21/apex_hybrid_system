<?php

namespace App\Livewire\Workflow;

use App\Contracts\Workflow\Workflowable;
use App\Enums\WorkflowStatus;
use App\Models\WorkflowInstance;
use App\Services\Workflow\WorkflowEngine;
use Flux\Flux;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\HttpException;

class WorkflowPanel extends Component
{
    public string $documentType;

    public int|string $documentId;

    public string $comment = '';

    public string $pendingAction = '';

    public bool $showCommentModal = false;

    public function mount(Workflowable&Model $document): void
    {
        $this->documentType = $document->getMorphClass();
        $this->documentId = $document->getKey();

        app(WorkflowEngine::class)->ensureInstance($document);
    }

    #[Computed]
    public function document(): Workflowable&Model
    {
        $class = Relation::getMorphedModel($this->documentType) ?? $this->documentType;

        /** @var Workflowable&Model $model */
        $model = $class::query()->findOrFail($this->documentId);

        return $model;
    }

    #[Computed]
    public function instance(): ?WorkflowInstance
    {
        return $this->document->workflowInstance()
            ->with(['histories.user', 'approvals.user'])
            ->first();
    }

    /**
     * @return list<array{action: string, label: string, requires_comment: bool, approval: bool}>
     */
    #[Computed]
    public function actions(): array
    {
        return app(WorkflowEngine::class)->availableActions($this->document, auth()->user());
    }

    public function statusLabel(): string
    {
        $status = $this->document->workflowStatus();

        $enum = WorkflowStatus::tryFrom($status);
        if ($enum) {
            return $enum->label();
        }

        $key = 'scf.workflow.status_'.$status;

        return __($key) !== $key ? __($key) : str_replace('_', ' ', ucfirst($status));
    }

    public function statusColor(): string
    {
        $status = $this->document->workflowStatus();

        return WorkflowStatus::tryFrom($status)?->color()
            ?? match ($status) {
                'pending' => 'amber',
                'approved', 'closed', 'confirmed', 'posted' => 'green',
                'rejected', 'cancelled' => 'red',
                default => 'zinc',
            };
    }

    public function requestAction(string $action): void
    {
        $meta = collect($this->actions)->firstWhere('action', $action);
        if (! $meta) {
            abort(403);
        }

        if ($meta['requires_comment']) {
            $this->pendingAction = $action;
            $this->comment = '';
            $this->showCommentModal = true;

            return;
        }

        $this->applyAction($action);
    }

    public function confirmCommentedAction(): void
    {
        $this->validate([
            'comment' => ['required', 'string', 'max:2000'],
            'pendingAction' => ['required', 'string'],
        ]);

        $action = $this->pendingAction;
        $this->showCommentModal = false;
        $this->applyAction($action, $this->comment);
        $this->pendingAction = '';
        $this->comment = '';
    }

    protected function applyAction(string $action, ?string $comment = null): void
    {
        try {
            $document = $this->document;
            $before = $document->workflowStatus();

            app(WorkflowEngine::class)->apply($document, auth()->user(), $action, $comment);

            $document->refresh();
            $after = $document->workflowStatus();

            Flux::toast(
                variant: 'success',
                text: $before === $after
                    ? __('scf.workflow.partial_approval')
                    : __('scf.workflow.transition_applied'),
            );

            unset($this->document, $this->instance, $this->actions);
            $this->dispatch('workflow-updated');
        } catch (ValidationException $e) {
            Flux::toast(variant: 'danger', text: collect($e->errors())->flatten()->first());
        } catch (HttpException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage() ?: __('Unauthorized.'));
        }
    }

    public function render()
    {
        return view('livewire.workflow.panel');
    }
}

<?php

namespace App\Services\Activity;

use App\Contracts\Workflow\Workflowable;
use App\Enums\ActivityType;
use App\Enums\ActivityVisibility;
use App\Models\Activity;
use App\Models\AuditLog;
use App\Models\ManagedDocument;
use App\Models\SalesDocumentEvent;
use App\Models\User;
use App\Models\WorkflowHistory;
use App\Models\WorkflowInstance;
use App\Support\Activity\TimelineEntry;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class DocumentTimelineService
{
    /**
     * @return LengthAwarePaginator<int, TimelineEntry>
     */
    public function forSubject(Model $subject, User $viewer, int $page = 1, int $perPage = 15): LengthAwarePaginator
    {
        abort_unless(Gate::forUser($viewer)->allows('view', $subject), 403);

        $fetch = max($perPage * (int) config('activity.source_fetch_multiplier', 3), $perPage);
        $canSeeInternal = $viewer->can('activities.internal_note')
            || $viewer->can('activities.manage')
            || $viewer->can('activities.view_all');

        $entries = collect()
            ->merge($this->fromActivities($subject, $viewer, $canSeeInternal, $fetch))
            ->merge($this->fromSalesEvents($subject, $fetch))
            ->merge($this->fromWorkflowHistory($subject, $fetch))
            ->merge($this->fromAuditFieldChanges($subject, $fetch))
            ->merge($this->fromAttachments($subject, $fetch))
            ->sortByDesc(fn (TimelineEntry $entry) => $entry->occurredAt->getTimestamp())
            ->values();

        // Deduplicate attachment rows that already have an Activity record
        $activityDocIds = $entries
            ->filter(fn (TimelineEntry $e) => $e->source === 'activity' && $e->type === ActivityType::Attachment)
            ->map(fn (TimelineEntry $e) => $e->meta['managed_document_id'] ?? null)
            ->filter()
            ->all();

        $entries = $entries->reject(function (TimelineEntry $entry) use ($activityDocIds) {
            return $entry->source === 'document'
                && in_array($entry->meta['managed_document_id'] ?? null, $activityDocIds, true);
        })->values();

        $total = $entries->count();
        $slice = $entries->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    /**
     * Global activity center feed (activities table + optional sales events), permission-aware.
     *
     * @param  array{search?: string, type?: string, module?: string, user_id?: int|null, date_from?: string|null, date_to?: string|null}  $filters
     * @return LengthAwarePaginator<int, Activity>
     */
    public function globalFeed(User $viewer, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        abort_unless($viewer->can('activities.read') || $viewer->can('activities.view_all'), 403);

        $canSeeInternal = $viewer->can('activities.internal_note')
            || $viewer->can('activities.manage')
            || $viewer->can('activities.view_all');

        $query = Activity::query()
            ->with(['user', 'subject', 'document'])
            ->when(! $canSeeInternal, fn ($q) => $q->where('visibility', ActivityVisibility::Public->value))
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when($filters['user_id'] ?? null, fn ($q, $userId) => $q->where('user_id', $userId))
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('body', 'like', "%{$search}%")
                        ->orWhere('event_key', 'like', "%{$search}%");
                });
            })
            ->when($filters['date_from'] ?? null, fn ($q, $from) => $q->whereDate('created_at', '>=', $from))
            ->when($filters['date_to'] ?? null, fn ($q, $to) => $q->whereDate('created_at', '<=', $to))
            ->when($filters['module'] ?? null, function ($q, $module) {
                /** @var array<class-string<Model>, string> $map */
                $map = config('activity.subject_routes', []);
                $types = [];
                foreach ($map as $class => $route) {
                    if (! is_subclass_of($class, Model::class)) {
                        continue;
                    }
                    if (str_contains($route, $module) || str_contains(Str::kebab(class_basename($class)), $module)) {
                        $types[] = (new $class)->getMorphClass();
                    }
                }
                if ($types !== []) {
                    $q->whereIn('subject_type', $types);
                }
            })
            ->latest();

        /** @var LengthAwarePaginator<int, Activity> $page */
        $page = $query->paginate($perPage);

        // Record-level filter: drop subjects the user cannot view (unless view_all)
        if (! $viewer->can('activities.view_all')) {
            $filtered = $page->getCollection()->filter(function (Activity $activity) use ($viewer) {
                $subject = $activity->subject;
                if (! $subject) {
                    return false;
                }

                return Gate::forUser($viewer)->allows('view', $subject);
            })->values();

            $page->setCollection($filtered);
        }

        return $page;
    }

    /**
     * @return Collection<int, TimelineEntry>
     */
    protected function fromActivities(Model $subject, User $viewer, bool $canSeeInternal, int $limit): Collection
    {
        return Activity::query()
            ->with(['user', 'document', 'mentions.user'])
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey())
            ->when(! $canSeeInternal, fn ($q) => $q->where('visibility', ActivityVisibility::Public->value))
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(function (Activity $activity) use ($viewer) {
                return new TimelineEntry(
                    id: 'activity:'.$activity->id,
                    source: 'activity',
                    type: $activity->type,
                    eventKey: $activity->event_key,
                    actor: $activity->user,
                    title: $activity->title ?: $activity->type->label(),
                    body: $activity->body,
                    visibility: $activity->visibility,
                    oldValues: $activity->old_values,
                    newValues: $activity->new_values,
                    meta: array_merge($activity->metadata ?? [], [
                        'managed_document_id' => $activity->managed_document_id,
                        'filename' => $activity->document?->original_name,
                        'size' => $activity->document?->size,
                    ]),
                    occurredAt: $this->resolveOccurredAt($activity->created_at),
                    editable: $activity->isEditableBy($viewer),
                    deletable: $activity->isDeletableBy($viewer),
                    activityId: $activity->id,
                    edited: $activity->edited_at !== null,
                    hasAttachment: $activity->managed_document_id !== null,
                    parentId: $activity->parent_id,
                );
            });
    }

    /**
     * @return Collection<int, TimelineEntry>
     */
    protected function fromSalesEvents(Model $subject, int $limit): Collection
    {
        if (! method_exists($subject, 'events')) {
            return collect();
        }

        return SalesDocumentEvent::query()
            ->with('user')
            ->where('document_type', $subject->getMorphClass())
            ->where('document_id', $subject->getKey())
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(function (SalesDocumentEvent $event) {
                $type = $this->mapSalesEventType($event->event);

                $title = __('scf.activity.event_'.$event->event);
                if ($title === 'scf.activity.event_'.$event->event) {
                    $title = Str::headline(str_replace('_', ' ', $event->event));
                }

                $bodyParts = [];
                if ($event->from_status || $event->to_status) {
                    $bodyParts[] = trim(($event->from_status ?? '—').' → '.($event->to_status ?? '—'));
                }
                if ($event->reason) {
                    $bodyParts[] = $event->reason;
                }

                return new TimelineEntry(
                    id: 'sales_event:'.$event->id,
                    source: 'sales_event',
                    type: $type,
                    eventKey: $event->event,
                    actor: $event->user,
                    title: $title,
                    body: $bodyParts !== [] ? implode(' · ', $bodyParts) : null,
                    visibility: ActivityVisibility::Public,
                    oldValues: $event->from_status ? ['status' => $event->from_status] : null,
                    newValues: $event->to_status ? ['status' => $event->to_status] : null,
                    meta: [
                        'amount' => $event->amount,
                        'related_type' => $event->related_type,
                        'related_id' => $event->related_id,
                        'metadata' => $event->metadata,
                    ],
                    occurredAt: $this->resolveOccurredAt($event->created_at),
                    editable: false,
                    deletable: false,
                );
            });
    }

    /**
     * @return Collection<int, TimelineEntry>
     */
    protected function fromWorkflowHistory(Model $subject, int $limit): Collection
    {
        if (! $subject instanceof Workflowable) {
            return collect();
        }

        $instance = $subject->workflowInstance()->first();
        if (! $instance instanceof WorkflowInstance) {
            return collect();
        }

        return WorkflowHistory::query()
            ->with('user')
            ->where('workflow_instance_id', $instance->id)
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(function (WorkflowHistory $history) {
                $type = match ($history->action) {
                    'approve' => ActivityType::Approval,
                    'reject' => ActivityType::Rejection,
                    'cancel' => ActivityType::Cancellation,
                    default => ActivityType::WorkflowTransition,
                };

                return new TimelineEntry(
                    id: 'workflow:'.$history->id,
                    source: 'workflow',
                    type: $type,
                    eventKey: $history->action,
                    actor: $history->user,
                    title: __('scf.workflow.action_'.$history->action),
                    body: $history->comment,
                    visibility: ActivityVisibility::Public,
                    oldValues: $history->from_status ? ['status' => $history->from_status] : null,
                    newValues: $history->to_status ? ['status' => $history->to_status] : null,
                    meta: [
                        'approval_level' => $history->approval_level,
                        'approval_level_name' => $history->approval_level_name,
                        'meta' => $history->meta,
                    ],
                    occurredAt: $this->resolveOccurredAt($history->created_at),
                    editable: false,
                    deletable: false,
                );
            });
    }

    /**
     * @return Collection<int, TimelineEntry>
     */
    protected function fromAuditFieldChanges(Model $subject, int $limit): Collection
    {
        /** @var array<string, string> $tracked */
        $tracked = config('activity.tracked_fields.'.$subject::class, []);
        if ($tracked === []) {
            return collect();
        }

        /** @var list<string> $trackedFieldKeys */
        $trackedFieldKeys = array_map('strval', array_keys($tracked));

        $ignored = config('activity.ignored_audit_fields', []);

        return AuditLog::query()
            ->with('user')
            ->where('auditable_type', $subject->getMorphClass())
            ->where('auditable_id', $subject->getKey())
            ->where('action', 'updated')
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(function (AuditLog $log) use ($tracked, $ignored, $trackedFieldKeys) {
                $old = collect($log->old_values ?? [])
                    ->reject(fn ($_, $key) => in_array($key, $ignored, true))
                    ->only($trackedFieldKeys);
                $new = collect($log->new_values ?? [])
                    ->reject(fn ($_, $key) => in_array($key, $ignored, true))
                    ->only($trackedFieldKeys);

                if ($old->isEmpty() && $new->isEmpty()) {
                    return null;
                }

                $changes = [];
                foreach ($tracked as $field => $labelKey) {
                    if (! $new->has($field) && ! $old->has($field)) {
                        continue;
                    }
                    $changes[$field] = [
                        'label' => __('scf.activity.field_'.$labelKey),
                        'old' => $old->get($field),
                        'new' => $new->get($field, $old->get($field)),
                    ];
                }

                if ($changes === []) {
                    return null;
                }

                return new TimelineEntry(
                    id: 'audit:'.$log->id,
                    source: 'audit',
                    type: ActivityType::FieldChange,
                    eventKey: 'field_change',
                    actor: $log->user,
                    title: __('scf.activity.field_changes'),
                    body: null,
                    visibility: ActivityVisibility::Public,
                    oldValues: $old->all(),
                    newValues: $new->all(),
                    meta: ['changes' => $changes],
                    occurredAt: $this->resolveOccurredAt($log->created_at),
                    editable: false,
                    deletable: false,
                );
            })
            ->filter()
            ->values();
    }

    /**
     * @return Collection<int, TimelineEntry>
     */
    protected function fromAttachments(Model $subject, int $limit): Collection
    {
        return ManagedDocument::query()
            ->with('owner')
            ->where('documentable_type', $subject->getMorphClass())
            ->where('documentable_id', $subject->getKey())
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(function (ManagedDocument $document) {
                return new TimelineEntry(
                    id: 'document:'.$document->id,
                    source: 'document',
                    type: ActivityType::Attachment,
                    eventKey: 'attachment.linked',
                    actor: $document->owner,
                    title: __('scf.activity.attachment_uploaded', ['name' => $document->original_name]),
                    body: null,
                    visibility: ActivityVisibility::Public,
                    oldValues: null,
                    newValues: null,
                    meta: [
                        'managed_document_id' => $document->id,
                        'filename' => $document->original_name,
                        'size' => $document->size,
                        'mime_type' => $document->mime_type,
                    ],
                    occurredAt: $this->resolveOccurredAt($document->created_at),
                    editable: false,
                    deletable: false,
                    hasAttachment: true,
                );
            });
    }

    protected function mapSalesEventType(string $event): ActivityType
    {
        return match (true) {
            str_contains($event, 'approv') => ActivityType::Approval,
            str_contains($event, 'reject') => ActivityType::Rejection,
            str_contains($event, 'cancel') || str_contains($event, 'void') => ActivityType::Cancellation,
            str_contains($event, 'convert') || str_contains($event, 'invoice') || str_contains($event, 'bill') => ActivityType::Conversion,
            str_contains($event, 'payment') || str_contains($event, 'paid') => ActivityType::Payment,
            str_contains($event, 'post') => ActivityType::AccountingPosting,
            default => ActivityType::SystemEvent,
        };
    }

    private function resolveOccurredAt(mixed $timestamp): CarbonInterface
    {
        if ($timestamp instanceof CarbonInterface) {
            return $timestamp;
        }

        if ($timestamp === null) {
            return now();
        }

        return Carbon::parse($timestamp);
    }
}

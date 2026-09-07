<?php

namespace App\Livewire\Activity;

use App\Enums\ActivityVisibility;
use App\Models\Activity;
use App\Models\User;
use App\Services\Activity\ActivityService;
use App\Services\Activity\DocumentTimelineService;
use App\Services\Activity\MentionParser;
use App\Support\Activity\TimelineEntry;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @property-read Model $subject
 */
class ActivityTimeline extends Component
{
    use WithFileUploads;

    public string $subjectType;

    public int|string $subjectId;

    public string $body = '';

    public bool $internal = false;

    public ?int $replyToId = null;

    public ?int $editingId = null;

    public ?TemporaryUploadedFile $attachment = null;

    public int $perPage = 15;

    public string $mentionQuery = '';

    public function mount(Model $subject): void
    {
        $this->authorize('view', $subject);
        $this->subjectType = $subject->getMorphClass();
        $this->subjectId = $subject->getKey();
        $this->perPage = (int) config('activity.per_page', 15);
    }

    #[Computed]
    public function subject(): Model
    {
        $class = Relation::getMorphedModel($this->subjectType) ?? $this->subjectType;

        return $class::query()->findOrFail($this->subjectId);
    }

    /**
     * @return LengthAwarePaginator<int, TimelineEntry>
     */
    #[Computed]
    public function entries(): LengthAwarePaginator
    {
        return app(DocumentTimelineService::class)->forSubject(
            $this->subject,
            $this->authenticatedUser(),
            1,
            $this->perPage,
        );
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function mentionSuggestions(): Collection
    {
        if (strlen(trim($this->mentionQuery)) < 1) {
            return collect();
        }

        return app(MentionParser::class)->suggest($this->authenticatedUser(), $this->mentionQuery);
    }

    public function updatedBody(): void
    {
        if (preg_match('/@([A-Za-z][A-Za-z0-9._\\- ]*)$/', $this->body, $m)) {
            $this->mentionQuery = trim($m[1]);
        } else {
            $this->mentionQuery = '';
        }
        unset($this->mentionSuggestions);
    }

    public function insertMention(string $name): void
    {
        $this->body = preg_replace('/@([A-Za-z][A-Za-z0-9._\\- ]*)$/', '@'.$name.' ', $this->body) ?? $this->body;
        $this->mentionQuery = '';
        unset($this->mentionSuggestions);
    }

    public function setReply(?int $id): void
    {
        $this->replyToId = $id;
        $this->editingId = null;
    }

    public function startEdit(int $id): void
    {
        $activity = Activity::query()->findOrFail($id);
        abort_unless($activity->isEditableBy($this->authenticatedUser()), 403);
        $this->editingId = $id;
        $this->body = $activity->body ?? '';
        $this->internal = $activity->visibility === ActivityVisibility::Internal;
        $this->replyToId = null;
    }

    public function cancelCompose(): void
    {
        $this->reset(['body', 'internal', 'replyToId', 'editingId', 'attachment', 'mentionQuery']);
    }

    public function submit(ActivityService $activities): void
    {
        try {
            if ($this->editingId) {
                $activity = Activity::query()->findOrFail($this->editingId);
                $activities->updateComment($activity, $this->authenticatedUser(), $this->body);
                Flux::toast(variant: 'success', text: __('scf.activity.comment_updated'));
            } else {
                $activities->addComment(
                    $this->subject,
                    $this->authenticatedUser(),
                    $this->body,
                    $this->replyToId,
                    $this->internal,
                );
                Flux::toast(variant: 'success', text: __('scf.activity.comment_added'));
            }

            if ($this->attachment) {
                $activities->attachFile($this->subject, $this->authenticatedUser(), $this->attachment);
            }

            $this->cancelCompose();
            unset($this->entries);
            $this->dispatch('activity-updated');
        } catch (ValidationException $e) {
            Flux::toast(variant: 'danger', text: collect($e->errors())->flatten()->first());
        } catch (HttpException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage() ?: __('Unauthorized.'));
        }
    }

    public function deleteActivity(int $id, ActivityService $activities): void
    {
        try {
            $activity = Activity::query()->findOrFail($id);
            $activities->deleteComment($activity, $this->authenticatedUser());
            Flux::toast(variant: 'success', text: __('scf.activity.comment_deleted'));
            unset($this->entries);
            $this->dispatch('activity-updated');
        } catch (ValidationException $e) {
            Flux::toast(variant: 'danger', text: collect($e->errors())->flatten()->first());
        } catch (HttpException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage() ?: __('Unauthorized.'));
        }
    }

    public function loadMore(): void
    {
        $this->perPage += (int) config('activity.per_page', 15);
        unset($this->entries);
    }

    public function canComment(): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->can('activities.comment')
            || $user->can('activities.create')
            || $user->can('activities.manage');
    }

    public function canInternalNote(): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->can('activities.internal_note') || $user->can('activities.manage');
    }

    public function render(): View
    {
        return view('livewire.activity.timeline');
    }

    private function authenticatedUser(): User
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }
}

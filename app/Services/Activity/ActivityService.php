<?php

namespace App\Services\Activity;

use App\Enums\ActivityType;
use App\Enums\ActivityVisibility;
use App\Enums\NotificationCategory;
use App\Enums\NotificationPriority;
use App\Models\Activity;
use App\Models\User;
use App\Services\Documents\ManagedDocumentService;
use App\Services\Notifications\NotificationCenterService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ActivityService
{
    public function __construct(
        protected NotificationCenterService $notifications,
        protected ManagedDocumentService $documents,
        protected MentionParser $mentions,
    ) {}

    public function addComment(
        Model $subject,
        User $actor,
        string $body,
        ?int $parentId = null,
        bool $internal = false,
    ): Activity {
        $this->assertCanViewSubject($actor, $subject);
        $this->throttle($actor, 'comment');

        if ($internal) {
            abort_unless($actor->can('activities.internal_note') || $actor->can('activities.manage'), 403);
        } else {
            abort_unless($actor->can('activities.comment') || $actor->can('activities.create') || $actor->can('activities.manage'), 403);
        }

        $body = $this->sanitizeBody($body);
        if ($body === '') {
            throw ValidationException::withMessages([
                'body' => [__('scf.activity.body_required')],
            ]);
        }

        $parent = null;
        if ($parentId) {
            $parent = Activity::query()
                ->where('subject_type', $subject->getMorphClass())
                ->where('subject_id', $subject->getKey())
                ->whereKey($parentId)
                ->firstOrFail();
        }

        return DB::transaction(function () use ($subject, $actor, $body, $parent, $internal) {
            $activity = Activity::query()->create([
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => $subject->getKey(),
                'type' => $internal ? ActivityType::InternalNote : ActivityType::Comment,
                'event_key' => $internal ? 'internal_note.created' : 'comment.created',
                'user_id' => $actor->id,
                'title' => $internal ? __('scf.activity.type_internal_note') : __('scf.activity.type_comment'),
                'body' => $body,
                'visibility' => $internal ? ActivityVisibility::Internal : ActivityVisibility::Public,
                'parent_id' => $parent?->id,
                'is_system' => false,
                'metadata' => [],
            ]);

            $mentioned = $this->mentions->resolveAndAttach($activity, $body, $actor);
            $this->notifyMentions($activity, $subject, $actor, $mentioned);
            $this->notifyReply($activity, $subject, $actor, $parent);

            return $activity->fresh(['user', 'mentions.user', 'document']);
        });
    }

    public function updateComment(Activity $activity, User $actor, string $body): Activity
    {
        if ($activity->is_system || ! $activity->type->isUserGenerated()) {
            throw ValidationException::withMessages([
                'activity' => [__('scf.activity.immutable_system')],
            ]);
        }

        abort_unless($activity->isEditableBy($actor), 403);
        $this->assertCanViewSubject($actor, $activity->subject);

        $body = $this->sanitizeBody($body);
        if ($body === '') {
            throw ValidationException::withMessages([
                'body' => [__('scf.activity.body_required')],
            ]);
        }

        return DB::transaction(function () use ($activity, $actor, $body) {
            $activity->update([
                'body' => $body,
                'edited_at' => now(),
            ]);

            $activity->mentions()->delete();
            $mentioned = $this->mentions->resolveAndAttach($activity->fresh(), $body, $actor);
            $this->notifyMentions($activity->fresh(), $activity->subject, $actor, $mentioned);

            return $activity->fresh(['user', 'mentions.user', 'document']);
        });
    }

    public function deleteComment(Activity $activity, User $actor): void
    {
        if ($activity->is_system || ! $activity->type->isUserGenerated()) {
            throw ValidationException::withMessages([
                'activity' => [__('scf.activity.immutable_system')],
            ]);
        }

        abort_unless($activity->isDeletableBy($actor), 403);
        $this->assertCanViewSubject($actor, $activity->subject);

        $activity->delete();
    }

    public function attachFile(Model $subject, User $actor, UploadedFile $file, ?string $comment = null): Activity
    {
        $this->assertCanViewSubject($actor, $subject);
        $this->throttle($actor, 'attach');
        abort_unless(
            $actor->can('documents.create') || $actor->can('activities.comment') || $actor->can('activities.manage'),
            403
        );

        return DB::transaction(function () use ($subject, $actor, $file, $comment) {
            $document = $this->documents->upload($actor, $file, [
                'documentable_type' => $subject->getMorphClass(),
                'documentable_id' => $subject->getKey(),
                'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            ]);

            $body = $comment ? $this->sanitizeBody($comment) : null;

            return Activity::query()->create([
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => $subject->getKey(),
                'type' => ActivityType::Attachment,
                'event_key' => 'attachment.uploaded',
                'user_id' => $actor->id,
                'title' => __('scf.activity.attachment_uploaded', ['name' => $document->original_name]),
                'body' => $body,
                'visibility' => ActivityVisibility::Public,
                'managed_document_id' => $document->id,
                'related_type' => $document->getMorphClass(),
                'related_id' => $document->id,
                'is_system' => false,
                'metadata' => [
                    'filename' => $document->original_name,
                    'size' => $document->size,
                    'mime_type' => $document->mime_type,
                ],
            ])->fresh(['user', 'document']);
        });
    }

    /**
     * @param  list<User>  $mentioned
     */
    protected function notifyMentions(Activity $activity, Model $subject, User $actor, array $mentioned): void
    {
        $unique = collect($mentioned)
            ->unique(fn (User $user) => $user->id)
            ->reject(fn (User $user) => $user->id === $actor->id)
            ->values();

        if ($unique->isEmpty()) {
            return;
        }

        $url = $this->subjectUrl($subject);

        $this->notifications->notify(
            recipients: $unique,
            event: 'activity.mentioned',
            title: __('scf.activity.notify_mention_title', ['user' => $actor->name]),
            body: __('scf.activity.notify_mention_body', [
                'document' => class_basename($subject),
                'snippet' => Str::limit($activity->body ?? '', 120),
            ]),
            category: NotificationCategory::Information,
            priority: NotificationPriority::Medium,
            module: 'activities',
            actionUrl: $url,
            meta: [
                'activity_id' => $activity->id,
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => $subject->getKey(),
                'actor_id' => $actor->id,
            ],
        );
    }

    protected function notifyReply(Activity $activity, Model $subject, User $actor, ?Activity $parent): void
    {
        if (! $parent || ! $parent->user_id || $parent->user_id === $actor->id) {
            return;
        }

        $parent->loadMissing('user');
        if (! $parent->user) {
            return;
        }

        $this->notifications->notify(
            recipients: $parent->user,
            event: 'activity.reply',
            title: __('scf.activity.notify_reply_title', ['user' => $actor->name]),
            body: __('scf.activity.notify_reply_body', [
                'snippet' => Str::limit($activity->body ?? '', 120),
            ]),
            category: NotificationCategory::Information,
            priority: NotificationPriority::Low,
            module: 'activities',
            actionUrl: $this->subjectUrl($subject),
            meta: [
                'activity_id' => $activity->id,
                'parent_id' => $parent->id,
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => $subject->getKey(),
            ],
        );
    }

    protected function subjectUrl(Model $subject): ?string
    {
        $routes = config('activity.subject_routes', []);
        $route = $routes[$subject::class] ?? null;

        if (! is_string($route) || ! Route::has($route)) {
            return null;
        }

        return route($route, $subject);
    }

    protected function sanitizeBody(string $body): string
    {
        $body = strip_tags($body);
        $body = str_replace("\0", '', $body);

        return trim($body);
    }

    protected function assertCanViewSubject(User $actor, ?Model $subject): void
    {
        if (! $subject) {
            abort(404);
        }

        if (Gate::forUser($actor)->allows('view', $subject)) {
            return;
        }

        // Fallback: module read permission when policy missing
        abort_unless($actor->can('activities.read') || $actor->can('activities.view_all'), 403);
    }

    protected function throttle(User $actor, string $action): void
    {
        $key = "activities:{$action}:{$actor->id}";
        if (RateLimiter::tooManyAttempts($key, 30)) {
            throw ValidationException::withMessages([
                'body' => [__('scf.activity.rate_limited')],
            ]);
        }
        RateLimiter::hit($key, 60);
    }
}

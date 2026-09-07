<?php

namespace App\Services\Api;

use App\Enums\ActivityType;
use App\Enums\ActivityVisibility;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ApiAuditService
{
    public function tokenCreated(User $user, ?int $tokenId, ?string $tokenName = null): void
    {
        $this->log('api.token.created', $user, null, [
            'token_id' => $tokenId,
            'token_name' => $tokenName,
        ]);
    }

    public function tokenRevoked(User $user, ?int $tokenId): void
    {
        $this->log('api.token.revoked', $user, null, [
            'token_id' => $tokenId,
        ]);
    }

    public function recordCreated(User $user, Model $subject): void
    {
        $this->log('api.record.created', $user, $subject);
    }

    public function recordUpdated(User $user, Model $subject): void
    {
        $this->log('api.record.updated', $user, $subject);
    }

    public function recordDeleted(User $user, Model $subject): void
    {
        $this->log('api.record.deleted', $user, $subject);
    }

    public function workflowTransition(User $user, Model $subject, string $action): void
    {
        $this->log('api.workflow.'.$action, $user, $subject, [
            'action' => $action,
        ]);
    }

    public function financialPosting(User $user, Model $subject, string $action): void
    {
        $this->log('api.financial.'.$action, $user, $subject, [
            'action' => $action,
        ]);
    }

    public function failedOperation(User $user, ?Model $subject, string $action, string $reason): void
    {
        $this->log('api.operation.failed', $user, $subject, [
            'action' => $action,
            'reason' => $reason,
        ], ActivityType::SystemEvent);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function log(
        string $event,
        User $user,
        ?Model $subject,
        array $context = [],
        ActivityType $type = ActivityType::SystemEvent,
    ): void {
        $request = request();

        $payload = array_merge($context, [
            'event' => $event,
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'request_id' => $request->attributes->get('request_id'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'token_id' => data_get($user->currentAccessToken(), 'id'),
        ]);

        Log::info($event, $payload);

        if ($subject !== null && config('api.audit.activity_enabled', true)) {
            try {
                Activity::query()->create([
                    'subject_type' => $subject->getMorphClass(),
                    'subject_id' => $subject->getKey(),
                    'type' => $type,
                    'event_key' => $event,
                    'user_id' => $user->id,
                    'title' => __('scf.api.audit_event', ['event' => $event]),
                    'body' => null,
                    'visibility' => ActivityVisibility::Internal,
                    'is_system' => true,
                    'metadata' => $payload,
                ]);
            } catch (\Throwable) {
                // Activity logging must not break API responses.
            }
        }
    }
}

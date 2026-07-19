<?php

namespace App\Services\Sales;

use App\Models\SalesDocumentEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class SalesDocumentEventLogger
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function log(
        Model $document,
        string $event,
        User $user,
        ?string $fromStatus = null,
        ?string $toStatus = null,
        ?string $reason = null,
        ?float $amount = null,
        ?Model $related = null,
        ?array $metadata = null,
    ): SalesDocumentEvent {
        return SalesDocumentEvent::query()->create([
            'document_type' => $document->getMorphClass(),
            'document_id' => $document->getKey(),
            'event' => $event,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'reason' => $reason,
            'amount' => $amount,
            'related_type' => $related?->getMorphClass(),
            'related_id' => $related?->getKey(),
            'user_id' => $user->id,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}

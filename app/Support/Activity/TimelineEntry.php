<?php

namespace App\Support\Activity;

use App\Enums\ActivityType;
use App\Enums\ActivityVisibility;
use App\Models\User;
use Carbon\CarbonInterface;

final class TimelineEntry
{
    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public readonly string $id,
        public readonly string $source,
        public readonly ActivityType $type,
        public readonly ?string $eventKey,
        public readonly ?User $actor,
        public readonly string $title,
        public readonly ?string $body,
        public readonly ActivityVisibility $visibility,
        public readonly ?array $oldValues,
        public readonly ?array $newValues,
        public readonly array $meta,
        public readonly CarbonInterface $occurredAt,
        public readonly bool $editable,
        public readonly bool $deletable,
        public readonly ?int $activityId = null,
        public readonly bool $edited = false,
        public readonly bool $hasAttachment = false,
        public readonly ?int $parentId = null,
    ) {}
}

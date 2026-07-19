<?php

namespace App\Services\Accounting;

use App\Models\AccountingAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class AccountingAuditService
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function log(string $action, ?Model $subject = null, ?User $user = null, ?array $metadata = null): AccountingAuditLog
    {
        return AccountingAuditLog::query()->create([
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'user_id' => $user?->id,
            'branch_id' => $metadata['branch_id'] ?? null,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}

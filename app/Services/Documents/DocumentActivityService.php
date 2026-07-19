<?php

namespace App\Services\Documents;

use App\Enums\DocumentActivityAction;
use App\Models\DocumentActivity;
use App\Models\ManagedDocument;
use App\Models\User;
use Illuminate\Support\Facades\Request;

class DocumentActivityService
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function log(
        ManagedDocument $document,
        DocumentActivityAction $action,
        ?User $user = null,
        ?array $metadata = null,
    ): DocumentActivity {
        return DocumentActivity::query()->create([
            'managed_document_id' => $document->id,
            'user_id' => $user?->id,
            'action' => $action,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}

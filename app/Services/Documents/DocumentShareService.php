<?php

namespace App\Services\Documents;

use App\Enums\DocumentActivityAction;
use App\Models\DocumentShare;
use App\Models\ManagedDocument;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DocumentShareService
{
    public function __construct(
        protected DocumentActivityService $activity,
    ) {}

    /**
     * @param  array{expires_at?: string|null, download_limit?: int|null, password?: string|null}  $options
     */
    public function createShare(ManagedDocument $document, User $user, array $options = []): DocumentShare
    {
        $share = DocumentShare::query()->create([
            'managed_document_id' => $document->id,
            'token' => Str::random(config('documents.share_token_length', 48)),
            'password' => isset($options['password']) && $options['password']
                ? Hash::make($options['password'])
                : null,
            'expires_at' => $options['expires_at'] ?? null,
            'download_limit' => $options['download_limit'] ?? null,
            'download_count' => 0,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $this->activity->log($document, DocumentActivityAction::Share, $user, [
            'share_id' => $share->id,
            'expires_at' => $share->expires_at?->toIso8601String(),
        ]);

        return $share;
    }

    public function revoke(DocumentShare $share): DocumentShare
    {
        $share->update(['is_active' => false]);

        return $share->refresh();
    }

    /**
     * Atomically claim one download against the share limit.
     *
     * @return bool False when the share is no longer downloadable (expired, inactive, or limit reached).
     */
    public function recordDownload(DocumentShare $share): bool
    {
        $affected = DocumentShare::query()
            ->whereKey($share->id)
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->where(function ($query): void {
                $query->whereNull('download_limit')
                    ->orWhereColumn('download_count', '<', 'download_limit');
            })
            ->update([
                'download_count' => DB::raw('download_count + 1'),
            ]);

        if ($affected !== 1) {
            return false;
        }

        $share->refresh();
        $this->activity->log($share->document, DocumentActivityAction::Download, null, [
            'share_id' => $share->id,
            'via' => 'share_link',
        ]);

        return true;
    }

    public function findAccessible(string $token): ?DocumentShare
    {
        $share = DocumentShare::query()
            ->with('document')
            ->where('token', $token)
            ->first();

        if ($share === null || ! $share->isAccessible()) {
            return null;
        }

        return $share;
    }
}

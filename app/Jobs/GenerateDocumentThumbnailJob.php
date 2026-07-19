<?php

namespace App\Jobs;

use App\Models\ManagedDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GenerateDocumentThumbnailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $documentId,
    ) {}

    public function handle(): void
    {
        $document = ManagedDocument::query()->find($this->documentId);

        if ($document === null) {
            return;
        }

        if (! in_array($document->mime_type, config('documents.thumbnail_mimes', []), true)) {
            return;
        }

        $thumbPath = 'documents/thumbnails/'.Str::uuid().'.jpg';

        if (str_starts_with((string) $document->mime_type, 'image/')) {
            $contents = Storage::disk($document->disk)->get($document->path);
            Storage::disk($document->disk)->put($thumbPath, $contents);
            $document->update(['thumbnail_path' => $thumbPath]);

            return;
        }

        if ($document->mime_type === 'application/pdf') {
            $document->update(['thumbnail_path' => null]);
        }
    }
}

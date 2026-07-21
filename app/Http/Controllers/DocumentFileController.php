<?php

namespace App\Http\Controllers;

use App\Models\ManagedDocument;
use App\Services\Documents\DocumentActivityService;
use App\Enums\DocumentActivityAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentFileController extends Controller
{
    public function __construct(
        protected DocumentActivityService $activity,
    ) {}

    public function download(Request $request, ManagedDocument $managedDocument): StreamedResponse
    {
        $this->authorize('download', $managedDocument);
        $this->activity->log($managedDocument, DocumentActivityAction::Download, $request->user());

        return response()->streamDownload(function () use ($managedDocument): void {
            $stream = Storage::disk($managedDocument->disk)->readStream($managedDocument->path);
            if (is_resource($stream)) {
                fpassthru($stream);
                fclose($stream);
            }
        }, $managedDocument->original_name, [
            'Content-Type' => $managedDocument->mime_type ?? 'application/octet-stream',
        ]);
    }

    public function preview(Request $request, ManagedDocument $managedDocument)
    {
        $this->authorize('view', $managedDocument);

        if (! $managedDocument->isPreviewable()) {
            if ($managedDocument->isOfficeDocument()) {
                return view('pages.documents.office-preview', [
                    'document' => $managedDocument,
                ]);
            }

            abort(415, __('scf.dms.preview_not_supported'));
        }

        $this->activity->log($managedDocument, DocumentActivityAction::Preview, $request->user());

        return response()->file(Storage::disk($managedDocument->disk)->path($managedDocument->path), [
            'Content-Type' => $managedDocument->mime_type,
            'Content-Disposition' => 'inline; filename="'.$managedDocument->original_name.'"',
        ]);
    }

    public function print(Request $request, ManagedDocument $managedDocument)
    {
        $this->authorize('print', $managedDocument);
        abort_unless($managedDocument->isPreviewable(), 415, __('scf.dms.preview_not_supported'));

        $this->activity->log($managedDocument, DocumentActivityAction::Print, $request->user());

        return view('print.a4.document-preview', [
            'document' => $managedDocument,
            'url' => route('documents.preview', $managedDocument),
        ]);
    }
}

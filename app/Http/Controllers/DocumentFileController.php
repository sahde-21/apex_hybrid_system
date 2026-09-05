<?php

namespace App\Http\Controllers;

use App\Enums\DocumentActivityAction;
use App\Models\ManagedDocument;
use App\Services\Documents\DocumentActivityService;
use App\Support\Http\SafeContentDisposition;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentFileController extends Controller
{
    public function __construct(
        protected DocumentActivityService $activity,
    ) {}

    public function download(Request $request, ManagedDocument $managedDocument): StreamedResponse
    {
        $this->authorize('download', $managedDocument);
        $this->activity->log($managedDocument, DocumentActivityAction::Download, $request->user('web'));

        return response()->streamDownload(function () use ($managedDocument): void {
            $stream = Storage::disk($managedDocument->disk)->readStream($managedDocument->path);
            if (is_resource($stream)) {
                fpassthru($stream);
                fclose($stream);
            }
        }, SafeContentDisposition::sanitizeFilename($managedDocument->original_name), [
            'Content-Type' => $managedDocument->mime_type ?? 'application/octet-stream',
        ]);
    }

    public function preview(Request $request, ManagedDocument $managedDocument): View|BinaryFileResponse
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

        $this->activity->log($managedDocument, DocumentActivityAction::Preview, $request->user('web'));

        $response = response()->file(Storage::disk($managedDocument->disk)->path($managedDocument->path), [
            'Content-Type' => $managedDocument->mime_type,
        ]);

        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            SafeContentDisposition::sanitizeFilename($managedDocument->original_name),
            SafeContentDisposition::asciiFallback($managedDocument->original_name),
        );

        return $response;
    }

    public function print(Request $request, ManagedDocument $managedDocument): View
    {
        $this->authorize('print', $managedDocument);
        abort_unless($managedDocument->isPreviewable(), 415, __('scf.dms.preview_not_supported'));

        $this->activity->log($managedDocument, DocumentActivityAction::Print, $request->user('web'));

        return view('print.a4.document-preview', [
            'document' => $managedDocument,
            'url' => route('documents.preview', $managedDocument),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\Documents\DocumentShareService;
use App\Support\Http\SafeContentDisposition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentShareController extends Controller
{
    public function __construct(
        protected DocumentShareService $shares,
    ) {}

    public function show(Request $request, string $token): View
    {
        $share = $this->shares->findAccessible($token);
        abort_if($share === null, 404);

        if ($share->password && ! $request->session()->get('dms_share_'.$token)) {
            return view('pages.documents.share-password', [
                'token' => $token,
            ]);
        }

        return view('pages.documents.share-download', [
            'share' => $share,
            'document' => $share->document,
        ]);
    }

    public function unlock(Request $request, string $token): RedirectResponse
    {
        $share = $this->shares->findAccessible($token);
        abort_if($share === null, 404);

        $request->validate(['password' => ['required', 'string']]);
        abort_unless($share->checkPassword($request->string('password')->toString()), 403);

        $request->session()->put('dms_share_'.$token, true);

        return redirect()->route('documents.share.show', $token);
    }

    public function download(Request $request, string $token): StreamedResponse
    {
        $share = $this->shares->findAccessible($token);
        abort_if($share === null, 404);

        if ($share->password && ! $request->session()->get('dms_share_'.$token)) {
            abort(403);
        }

        $document = $share->document;
        abort_unless($this->shares->recordDownload($share), 404);

        return Storage::disk($document->disk)->download(
            $document->path,
            SafeContentDisposition::sanitizeFilename($document->original_name),
        );
    }
}

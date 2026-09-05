<?php

use App\Models\ManagedDocument;
use App\Services\Documents\DocumentShareService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake(config('documents.disk', 'local'));
});

test('share download claim refuses after limit is exhausted', function () {
    $user = actingAsSuperAdmin();
    $document = ManagedDocument::factory()->for($user, 'owner')->create();
    $shares = app(DocumentShareService::class);

    $share = $shares->createShare($document, $user, [
        'download_limit' => 1,
    ]);

    expect($shares->recordDownload($share))->toBeTrue()
        ->and($share->fresh()->download_count)->toBe(1)
        ->and($shares->recordDownload($share->fresh()))->toBeFalse()
        ->and($share->fresh()->download_count)->toBe(1);
});

test('unlimited shares still allow repeated downloads', function () {
    $user = actingAsSuperAdmin();
    $document = ManagedDocument::factory()->for($user, 'owner')->create();
    $shares = app(DocumentShareService::class);

    $share = $shares->createShare($document, $user, [
        'download_limit' => null,
    ]);

    expect($shares->recordDownload($share))->toBeTrue()
        ->and($shares->recordDownload($share->fresh()))->toBeTrue()
        ->and($share->fresh()->download_count)->toBe(2);
});

test('http download endpoint returns not found after atomic limit claim', function () {
    $user = actingAsSuperAdmin();
    $document = ManagedDocument::factory()->for($user, 'owner')->create();
    $share = app(DocumentShareService::class)->createShare($document, $user, [
        'download_limit' => 1,
    ]);

    $this->get(route('documents.share.download', $share->token))->assertOk();
    $this->get(route('documents.share.download', $share->token))->assertNotFound();
});

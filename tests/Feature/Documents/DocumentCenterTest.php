<?php

use App\Models\DocumentActivity;
use App\Models\DocumentShare;
use App\Models\ManagedDocument;
use App\Services\Documents\DocumentShareService;
use App\Services\Documents\ManagedDocumentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake(config('documents.disk', 'local'));
});

test('users with documents permission can open document center', function () {
    actingAsSuperAdmin();

    $this->get(route('documents.index'))->assertOk();
});

test('users without documents permission cannot open document center', function () {
    actingAsUserWithPermissions(['dashboard.read']);

    $this->get(route('documents.index'))->assertForbidden();
});

test('users can download documents', function () {
    $user = actingAsSuperAdmin();
    $document = ManagedDocument::factory()->for($user, 'owner')->create();

    $this->get(route('documents.download', $document))->assertOk();
});

test('managed document service stores uploads with activity log', function () {
    $user = actingAsSuperAdmin();
    $file = UploadedFile::fake()->createWithContent('invoice.txt', 'invoice body');

    $document = app(ManagedDocumentService::class)->upload($user, $file, [
        'category' => 'invoices',
    ]);

    expect($document)->toBeInstanceOf(ManagedDocument::class)
        ->and(DocumentActivity::query()->where('managed_document_id', $document->id)->count())->toBeGreaterThan(0);
});

test('share links allow downloads', function () {
    $user = actingAsSuperAdmin();
    $document = ManagedDocument::factory()->for($user, 'owner')->create();

    $share = app(DocumentShareService::class)->createShare($document, $user, [
        'download_limit' => 5,
    ]);

    expect($share->token)->not->toBeEmpty()
        ->and($share->checkPassword(null))->toBeTrue();

    $this->get(route('documents.share.show', $share->token))->assertOk();
    $this->get(route('documents.share.download', $share->token))->assertOk();
});

test('password protected shares require correct password', function () {
    $user = actingAsSuperAdmin();
    $document = ManagedDocument::factory()->for($user, 'owner')->create();

    $share = app(DocumentShareService::class)->createShare($document, $user, [
        'password' => 'secret-pass',
    ]);

    expect($share->checkPassword('secret-pass'))->toBeTrue()
        ->and($share->checkPassword('wrong'))->toBeFalse();
});

test('document center livewire lists uploaded documents', function () {
    $user = actingAsSuperAdmin();
    ManagedDocument::factory()->for($user, 'owner')->create(['name' => 'Quarterly Report']);

    Livewire::actingAs($user)
        ->test('pages::documents.center')
        ->assertSee('Quarterly Report')
        ->assertSee(__('scf.dms.center_title'));
});

test('users can soft delete and restore documents', function () {
    $user = actingAsSuperAdmin();
    $document = ManagedDocument::factory()->for($user, 'owner')->create();

    app(ManagedDocumentService::class)->softDelete($document, $user);
    expect(ManagedDocument::query()->find($document->id))->toBeNull();

    app(ManagedDocumentService::class)->restore($document->id, $user);
    expect(ManagedDocument::query()->find($document->id))->not->toBeNull();
});

test('recycle bin page requires documents read permission', function () {
    actingAsSuperAdmin();

    $this->get(route('documents.recycle-bin'))->assertOk();
});

test('users can move documents between folders', function () {
    $user = actingAsSuperAdmin();
    $folder = \App\Models\DocumentFolder::query()->create([
        'name' => 'Contracts',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);
    $document = ManagedDocument::factory()->for($user, 'owner')->create();

    app(ManagedDocumentService::class)->move($document, $folder->id, $user);

    expect($document->fresh()->folder_id)->toBe($folder->id);
});

test('expired share links are inaccessible', function () {
    $user = actingAsSuperAdmin();
    $document = ManagedDocument::factory()->for($user, 'owner')->create();

    $share = app(DocumentShareService::class)->createShare($document, $user, [
        'expires_at' => now()->subHour()->toDateTimeString(),
    ]);

    expect(app(DocumentShareService::class)->findAccessible($share->token))->toBeNull();
});

test('share download limits are enforced', function () {
    $user = actingAsSuperAdmin();
    $document = ManagedDocument::factory()->for($user, 'owner')->create();

    $share = app(DocumentShareService::class)->createShare($document, $user, [
        'download_limit' => 1,
    ]);

    $this->get(route('documents.share.download', $share->token))->assertOk();
    expect(app(DocumentShareService::class)->findAccessible($share->token))->toBeNull();
});

test('download endpoint records activity', function () {
    $user = actingAsSuperAdmin();
    $document = ManagedDocument::factory()->for($user, 'owner')->create();

    $this->get(route('documents.download', $document))->assertOk();

    expect(DocumentActivity::query()
        ->where('managed_document_id', $document->id)
        ->where('action', 'download')
        ->exists())->toBeTrue();
});

test('new document versions increment version number', function () {
    $user = actingAsSuperAdmin();
    $document = ManagedDocument::factory()->for($user, 'owner')->create(['version' => 1]);
    $file = UploadedFile::fake()->createWithContent('v2.txt', 'version two');

    $updated = app(ManagedDocumentService::class)->newVersion($document, $file, $user);

    expect($updated->version)->toBe(2)
        ->and($updated->versions()->count())->toBeGreaterThanOrEqual(1);
});

test('dms translations exist for english arabic and kurdish', function () {
    foreach (['en', 'ar', 'ckb'] as $locale) {
        app()->setLocale($locale);
        expect(__('scf.dms.brand'))->not->toBe('scf.dms.brand')
            ->and(__('scf.dms.center_title'))->not->toBe('scf.dms.center_title')
            ->and(__('scf.dms.move'))->not->toBe('scf.dms.move');
    }
});

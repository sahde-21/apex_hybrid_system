<?php

use App\Models\ManagedDocument;
use App\Services\Documents\ManagedDocumentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    Storage::fake(config('documents.disk', 'local'));
});

test('svg uploads are rejected', function () {
    $user = actingAsSuperAdmin();
    $file = UploadedFile::fake()->createWithContent(
        'xss.svg',
        '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"></svg>'
    );

    expect(fn () => app(ManagedDocumentService::class)->upload($user, $file))
        ->toThrow(HttpException::class);
});

test('svg is not previewable even if stored with spoofed mime historically', function () {
    $user = actingAsSuperAdmin();
    $document = ManagedDocument::factory()->for($user, 'owner')->create([
        'original_name' => 'legacy.svg',
        'mime_type' => 'image/svg+xml',
    ]);

    expect($document->isPreviewable())->toBeFalse();
    $this->get(route('documents.preview', $document))->assertStatus(415);
});

test('valid mime and extension are accepted', function () {
    $user = actingAsSuperAdmin();
    $file = UploadedFile::fake()->createWithContent('notes.txt', 'hello world');

    $document = app(ManagedDocumentService::class)->upload($user, $file, [
        'category' => 'general',
    ]);

    expect($document->original_name)->toBe('notes.txt')
        ->and($document->mime_type)->toBeIn(['text/plain', 'text/csv']);
});

test('mismatched mime and extension are rejected', function () {
    $user = actingAsSuperAdmin();
    $file = UploadedFile::fake()->create('invoice.pdf', 100, 'image/png');

    expect(fn () => app(ManagedDocumentService::class)->upload($user, $file))
        ->toThrow(HttpException::class);
});

test('spoofed extension with allowed mime is rejected', function () {
    $user = actingAsSuperAdmin();
    $file = UploadedFile::fake()->create('malware.exe', 100, 'application/pdf');

    expect(fn () => app(ManagedDocumentService::class)->upload($user, $file))
        ->toThrow(HttpException::class);
});

test('spoofed mime with allowed extension is rejected', function () {
    $user = actingAsSuperAdmin();
    $file = UploadedFile::fake()->create('photo.png', 100, 'application/pdf');

    expect(fn () => app(ManagedDocumentService::class)->upload($user, $file))
        ->toThrow(HttpException::class);
});

test('preview content disposition is safe for hostile original names', function () {
    $user = actingAsSuperAdmin();
    Storage::disk(config('documents.disk', 'local'))->put('documents/safe.txt', 'preview-body');

    $document = ManagedDocument::factory()->for($user, 'owner')->create([
        'original_name' => "q2\r\nContent-Type: text/html\r\n\r\n<script>.txt",
        'mime_type' => 'text/plain',
        'path' => 'documents/safe.txt',
        'disk' => config('documents.disk', 'local'),
    ]);

    $response = $this->get(route('documents.preview', $document));
    $response->assertOk();

    $disposition = $response->headers->get('Content-Disposition');
    expect($disposition)->not->toBeNull()
        ->and($disposition)->not->toContain("\r")
        ->and($disposition)->not->toContain("\n")
        ->and($disposition)->toStartWith('inline;');
});

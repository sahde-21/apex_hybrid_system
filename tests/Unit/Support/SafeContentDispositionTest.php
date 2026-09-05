<?php

use App\Support\Http\SafeContentDisposition;

test('sanitizeFilename strips CR LF and path separators', function () {
    $safe = SafeContentDisposition::sanitizeFilename("report\r\nX-Injected: yes.pdf");

    expect($safe)->not->toContain("\r")
        ->and($safe)->not->toContain("\n")
        ->and($safe)->not->toContain('/');
});

test('inline disposition encodes quotes and unicode safely', function () {
    $header = SafeContentDisposition::inline('Q2 "Q3" تقرير.pdf');

    expect($header)->toStartWith('inline;')
        ->and($header)->not->toContain("\r")
        ->and($header)->not->toContain("\n")
        ->and($header)->toContain('filename');
});

test('empty or hostile names fall back to download', function () {
    expect(SafeContentDisposition::sanitizeFilename('///'))->toBe('download')
        ->and(SafeContentDisposition::sanitizeFilename("\n\r"))->toBe('download');
});

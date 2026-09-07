<?php

namespace App\Support\Http;

use Symfony\Component\HttpFoundation\HeaderUtils;

/**
 * Builds Content-Disposition values without header injection from user filenames.
 */
final class SafeContentDisposition
{
    public static function sanitizeFilename(string $filename): string
    {
        $filename = str_replace(["\r", "\n", "\0", '/', '\\'], '-', $filename);
        $filename = trim($filename, " \t.-");

        return $filename !== '' ? $filename : 'download';
    }

    public static function asciiFallback(string $filename): string
    {
        $safe = self::sanitizeFilename($filename);
        $fallback = preg_replace('/[^\x20-\x7E]/', '_', $safe) ?? 'download';
        $fallback = trim($fallback, " \t.-");

        return $fallback !== '' ? $fallback : 'download';
    }

    public static function inline(string $filename): string
    {
        return HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_INLINE,
            self::sanitizeFilename($filename),
            self::asciiFallback($filename),
        );
    }

    public static function attachment(string $filename): string
    {
        return HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            self::sanitizeFilename($filename),
            self::asciiFallback($filename),
        );
    }
}

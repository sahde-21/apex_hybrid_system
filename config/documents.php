<?php

return [

    'disk' => env('DOCUMENTS_DISK', 'local'),

    'max_upload_kb' => (int) env('DOCUMENTS_MAX_UPLOAD_KB', 51200),

    'cache_ttl' => (int) env('DOCUMENTS_CACHE_TTL', 300),

    'share_token_length' => 48,

    'allowed_mimes' => [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/csv',
        'text/plain',
        'application/json',
        'application/zip',
        'application/x-zip-compressed',
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
    ],

    'preview_mimes' => [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
        'text/plain',
        'text/csv',
        'application/json',
    ],

    'thumbnail_mimes' => [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
    ],

];

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar', 'ckb']) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $document->name }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-zinc-50 antialiased dark:bg-zinc-950">
    <main class="mx-auto flex min-h-screen max-w-lg items-center px-4 py-10">
        <div class="portal-glass w-full rounded-2xl p-6">
            <p class="text-sm text-sky-600">{{ __('scf.dms.shared_document') }}</p>
            <h1 class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-white">{{ $document->name }}</h1>
            <p class="mt-2 text-sm text-zinc-500">{{ $document->original_name }} · {{ $document->humanSize() }}</p>
            <a href="{{ route('documents.share.download', $share->token) }}" class="mt-6 inline-flex w-full items-center justify-center rounded-xl bg-sky-600 px-4 py-3 font-medium text-white">
                {{ __('scf.dms.download_shared') }}
            </a>
        </div>
    </main>
</body>
</html>

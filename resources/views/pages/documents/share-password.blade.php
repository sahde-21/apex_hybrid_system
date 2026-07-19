<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar', 'ckb']) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('scf.dms.shared_document') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-zinc-50 antialiased dark:bg-zinc-950">
    <main class="mx-auto flex min-h-screen max-w-md items-center px-4">
        <div class="portal-glass w-full rounded-2xl p-6">
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-white">{{ __('scf.dms.enter_password') }}</h1>
            <form method="POST" action="{{ route('documents.share.unlock', $token) }}" class="mt-4 space-y-4">
                @csrf
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('scf.dms.share_password') }}</label>
                <input type="password" name="password" required class="w-full rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
                <button type="submit" class="w-full rounded-lg bg-sky-600 px-4 py-2 text-white">{{ __('scf.dms.unlock') }}</button>
            </form>
        </div>
    </main>
</body>
</html>

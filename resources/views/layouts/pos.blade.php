<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar', 'ckb']) ? 'rtl' : 'ltr' }}">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-zinc-100 antialiased dark:bg-zinc-950">
    {{ $slot }}
    @fluxScripts
</body>
</html>

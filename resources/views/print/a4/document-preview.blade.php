<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar', 'ckb']) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ $document->name }}</title>
    <style>
        @page { size: A4; margin: 12mm; }
        body { font-family: "Segoe UI", Arial, sans-serif; margin: 0; }
        iframe { width: 100%; height: 95vh; border: 0; }
    </style>
</head>
<body onload="window.print()">
    <iframe src="{{ $url }}" title="{{ $document->name }}"></iframe>
</body>
</html>

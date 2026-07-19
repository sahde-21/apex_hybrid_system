<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar', 'ckb']) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('scf.receipt') }}</title>
    <style>
        @page { size: 80mm auto; margin: 0; }
        body { font-family: monospace; font-size: 11px; width: 72mm; margin: 0 auto; padding: 8px 4px; }
        h1 { font-size: 14px; text-align: center; }
        .line { border-top: 1px dashed #000; margin: 8px 0; }
        .row { display: flex; justify-content: space-between; gap: 8px; }
    </style>
</head>
<body onload="window.print()">
    <h1>{{ config('app.name') }}</h1>
    <div class="line"></div>
    @foreach($record->getAttributes() as $key => $value)
        @if(! in_array($key, ['created_at', 'updated_at', 'deleted_at']))
            <div class="row"><span>{{ str($key)->headline() }}</span><span>{{ is_array($value) ? json_encode($value) : $value }}</span></div>
        @endif
    @endforeach
    <div class="line"></div>
    <div style="text-align:center;">{{ $printedAt->format('Y-m-d H:i') }}</div>
</body>
</html>

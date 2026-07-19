<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar', 'ckb']) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ $record->reference_number ?? class_basename($record) }} #{{ $record->id }}</title>
    <style>
        @page { size: A4; margin: 18mm; }
        body { font-family: "Segoe UI", Arial, sans-serif; font-size: 12px; margin: 24px; color: #111; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        .meta { color: #555; margin-bottom: 24px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: start; }
        th { background: #f5f5f5; }
    </style>
</head>
<body onload="window.print()">
    <h1>{{ config('app.name') }}</h1>
    <div class="meta">{{ __('scf.printed_at') }}: {{ $printedAt->format('Y-m-d H:i') }}</div>
    <table>
        @foreach($record->getAttributes() as $key => $value)
            @if(! in_array($key, ['created_at', 'updated_at', 'deleted_at']))
                <tr>
                    <th>{{ str($key)->headline() }}</th>
                    <td>{{ is_array($value) ? json_encode($value) : $value }}</td>
                </tr>
            @endif
        @endforeach
    </table>
</body>
</html>

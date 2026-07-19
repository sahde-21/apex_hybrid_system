<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar', 'ckb']) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { size: A4; margin: 16mm; }
        body { font-family: "Segoe UI", Arial, sans-serif; font-size: 12px; color: #0f172a; }
        h1 { font-size: 20px; margin: 0 0 8px; }
        .meta { color: #64748b; margin-bottom: 18px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid #e2e8f0; padding: 8px 6px; text-align: start; }
        th { background: #0f172a; color: #fff; font-size: 11px; text-transform: uppercase; }
        @media print { body { print-color-adjust: exact; -webkit-print-color-adjust: exact; } }
    </style>
</head>
<body onload="window.print()">
    <h1>{{ $title }}</h1>
    <div class="meta">{{ __('scf.printed_at') }}: {{ $printedAt->format('Y-m-d H:i') }}</div>
    <table>
        <thead>
            <tr>
                @foreach ($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($headers) }}">{{ __('scf.bi.no_rows') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

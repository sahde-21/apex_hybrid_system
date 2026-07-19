<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar', 'ckb']) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('scf.expenses') }} {{ $record->reference_number }}</title>
    <style>
        @page { size: A4; margin: 18mm; }
        body { font-family: "Segoe UI", Arial, sans-serif; font-size: 13px; color: #0f172a; margin: 0; }
        .brand { display: flex; justify-content: space-between; border-bottom: 3px solid #0f172a; padding-bottom: 16px; margin-bottom: 24px; }
        .brand h1 { margin: 0; font-size: 22px; }
        .doc-type { font-size: 26px; font-weight: 700; text-transform: uppercase; color: #334155; }
        .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 28px; }
        .box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 16px; }
        .box h3 { margin: 0 0 8px; font-size: 11px; text-transform: uppercase; color: #64748b; }
        .totals { margin-top: 24px; text-align: end; font-size: 18px; font-weight: 700; }
        .footer { margin-top: 40px; padding-top: 16px; border-top: 1px dashed #cbd5e1; color: #64748b; font-size: 11px; }
        @media print { body { print-color-adjust: exact; -webkit-print-color-adjust: exact; } }
    </style>
</head>
<body onload="window.print()">
    <div class="brand">
        <div>
            <h1>{{ config('app.name') }}</h1>
            <p style="margin:6px 0 0;color:#64748b;">{{ __('scf.app_name') }}</p>
        </div>
        <div class="doc-type">{{ __('scf.expenses') }}</div>
    </div>
    <div class="meta">
        <div class="box">
            <h3>{{ __('scf.category') }}</h3>
            <p><strong>{{ $record->category }}</strong></p>
            <p>{{ $record->description }}</p>
            <p>{{ $record->contact?->name ?? '—' }}</p>
        </div>
        <div class="box">
            <h3>{{ __('scf.reference') }}</h3>
            <p><strong>{{ $record->reference_number }}</strong></p>
            <p>{{ __('scf.date') }}: {{ $record->expense_date?->format('Y-m-d') }}</p>
            <p>{{ __('scf.payment_method') }}: {{ $record->payment_method ?? '—' }}</p>
        </div>
    </div>
    <div class="totals">{{ __('scf.total') }}: {{ number_format((float) $record->amount, 2) }}</div>
    <div class="footer">{{ __('scf.printed_at') }}: {{ $printedAt->format('Y-m-d H:i') }}</div>
</body>
</html>

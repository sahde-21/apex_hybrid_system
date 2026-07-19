<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar', 'ckb']) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('scf.payments') }} {{ $record->reference_number }}</title>
    <style>
        @page { size: A4; margin: 18mm; }
        * { box-sizing: border-box; }
        body { font-family: "Segoe UI", "Helvetica Neue", Arial, sans-serif; font-size: 13px; color: #0f172a; margin: 0; }
        .sheet { max-width: 210mm; margin: 0 auto; }
        .brand { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #0f172a; padding-bottom: 16px; margin-bottom: 24px; }
        .brand h1 { margin: 0; font-size: 22px; }
        .doc-type { font-size: 28px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #334155; }
        .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 28px; }
        .box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 16px; }
        .box h3 { margin: 0 0 8px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: #64748b; }
        .box p { margin: 4px 0; }
        .amount { margin-top: 24px; padding: 20px; border: 2px solid #0f172a; border-radius: 10px; text-align: center; }
        .amount .label { font-size: 12px; text-transform: uppercase; letter-spacing: 0.08em; color: #64748b; }
        .amount .value { font-size: 36px; font-weight: 700; margin-top: 6px; }
        .footer { margin-top: 40px; padding-top: 16px; border-top: 1px dashed #cbd5e1; color: #64748b; font-size: 11px; display: flex; justify-content: space-between; }
        @media print { body { print-color-adjust: exact; -webkit-print-color-adjust: exact; } }
    </style>
</head>
<body onload="window.print()">
<div class="sheet">
    <div class="brand">
        <div>
            <h1>{{ config('app.name') }}</h1>
            <p style="margin:6px 0 0;color:#64748b;">{{ __('scf.app_name') }}</p>
        </div>
        <div class="doc-type">{{ __('scf.payments') }}</div>
    </div>

    <div class="meta">
        <div class="box">
            <h3>{{ __('scf.contact') }}</h3>
            <p><strong>{{ $record->contact?->name ?? '—' }}</strong></p>
        </div>
        <div class="box">
            <h3>{{ __('scf.reference') }}</h3>
            <p><strong>{{ $record->reference_number }}</strong></p>
            <p>{{ __('scf.date') }}: {{ $record->payment_date?->format('Y-m-d') }}</p>
            <p>{{ __('scf.type') }}: {{ $record->type?->label() ?? $record->type }}</p>
            <p>{{ __('scf.method') }}: {{ $record->payment_method ?? '—' }}</p>
        </div>
    </div>

    <div class="amount">
        <div class="label">{{ __('scf.amount') }}</div>
        <div class="value">{{ number_format((float) $record->amount, 2) }}</div>
    </div>

    @if($record->notes)
        <p style="margin-top:24px;color:#475569;"><strong>{{ __('scf.notes') }}:</strong> {{ $record->notes }}</p>
    @endif

    <div class="footer">
        <span>{{ __('scf.thank_you') }}</span>
        <span>{{ __('scf.printed_at') }}: {{ $printedAt->format('Y-m-d H:i') }}</span>
    </div>
</div>
</body>
</html>

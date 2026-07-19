<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar', 'ckb']) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('scf.bills') }} {{ $record->reference_number }}</title>
    <style>
        @page { size: 80mm auto; margin: 0; }
        * { box-sizing: border-box; }
        body { font-family: "Courier New", Courier, monospace; font-size: 11px; width: 72mm; margin: 0 auto; padding: 8px 4px; color: #000; }
        .center { text-align: center; }
        h1 { font-size: 14px; margin: 0 0 4px; }
        .muted { color: #333; font-size: 10px; }
        .line { border-top: 1px dashed #000; margin: 8px 0; }
        .row { display: flex; justify-content: space-between; gap: 8px; margin: 3px 0; }
        .total { font-size: 13px; font-weight: bold; margin-top: 6px; }
        .barcode { margin-top: 8px; letter-spacing: 0.12em; font-size: 10px; }
        @media print { body { print-color-adjust: exact; -webkit-print-color-adjust: exact; } }
    </style>
</head>
<body onload="window.print()">
    <div class="center">
        <h1>{{ config('app.name') }}</h1>
        <div class="muted">{{ __('scf.bills') }}</div>
    </div>
    <div class="line"></div>
    <div class="row"><span>{{ __('scf.reference') }}</span><span>{{ $record->reference_number }}</span></div>
    <div class="row"><span>{{ __('scf.date') }}</span><span>{{ $record->bill_date?->format('Y-m-d') }}</span></div>
    <div class="row"><span>{{ __('scf.due') }}</span><span>{{ $record->due_date?->format('Y-m-d') ?? '—' }}</span></div>
    <div class="row"><span>{{ __('scf.supplier') }}</span><span>{{ $record->contact?->name ?? '—' }}</span></div>
    <div class="row"><span>{{ __('scf.status') }}</span><span>{{ $record->status?->label() ?? $record->status }}</span></div>
    <div class="line"></div>
    <div class="row"><span>{{ __('scf.tax') }}</span><span>{{ number_format((float) $record->tax_amount, 2) }}</span></div>
    <div class="row total"><span>{{ __('scf.total') }}</span><span>{{ number_format((float) $record->total_amount, 2) }}</span></div>
    @if($record->notes)
        <div class="line"></div>
        <div class="muted">{{ __('scf.notes') }}: {{ $record->notes }}</div>
    @endif
    <div class="line"></div>
    <div class="center">
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data={{ urlencode($record->reference_number) }}" width="80" height="80" alt="{{ $record->reference_number }}">
        <div class="barcode">*{{ $record->reference_number }}*</div>
    </div>
    <div class="center muted">{{ __('scf.thank_you') }}</div>
    <div class="center muted">{{ $printedAt->format('Y-m-d H:i') }}</div>
</body>
</html>

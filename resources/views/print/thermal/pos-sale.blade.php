<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar', 'ckb']) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('scf.pos') }} {{ $record->reference_number }}</title>
    <style>
        @page { size: 80mm auto; margin: 0; }
        * { box-sizing: border-box; }
        body { font-family: "Courier New", Courier, monospace; font-size: 11px; width: 72mm; margin: 0 auto; padding: 8px 4px; color: #000; }
        .center { text-align: center; }
        h1 { font-size: 14px; margin: 0 0 4px; }
        .muted { color: #333; font-size: 10px; }
        .line { border-top: 1px dashed #000; margin: 8px 0; }
        .row { display: flex; justify-content: space-between; gap: 8px; margin: 3px 0; }
        .item { margin: 6px 0; }
        .total { font-size: 13px; font-weight: bold; margin-top: 6px; }
        @media print { body { print-color-adjust: exact; -webkit-print-color-adjust: exact; } }
    </style>
</head>
<body onload="window.print()">
    <div class="center">
        <h1>{{ config('app.name') }}</h1>
        <div class="muted">{{ $record->is_return ? __('scf.pos_refund') : __('scf.pos_receipt') }}</div>
        <div class="muted">{{ $record->register?->name }}</div>
    </div>
    <div class="line"></div>
    <div class="row"><span>{{ __('scf.reference') }}</span><span>{{ $record->reference_number }}</span></div>
    <div class="row"><span>{{ __('scf.date') }}</span><span>{{ $record->created_at?->format('Y-m-d H:i') }}</span></div>
    <div class="row"><span>{{ __('scf.cashier') }}</span><span>{{ $record->user?->name ?? '—' }}</span></div>
    <div class="row"><span>{{ __('scf.customer') }}</span><span>{{ $record->contact?->name ?? __('scf.walk_in') }}</span></div>
    <div class="line"></div>
    @foreach($record->items as $item)
        <div class="item">
            <div>{{ $item->name }}</div>
            <div class="row">
                <span>{{ $item->quantity }} x {{ number_format((float) $item->unit_price, 2) }}</span>
                <span>{{ number_format((float) $item->line_total, 2) }}</span>
            </div>
        </div>
    @endforeach
    <div class="line"></div>
    <div class="row"><span>{{ __('scf.subtotal') }}</span><span>{{ number_format((float) $record->subtotal_amount, 2) }}</span></div>
    <div class="row"><span>{{ __('scf.discount') }}</span><span>{{ number_format((float) $record->discount_amount, 2) }}</span></div>
    <div class="row"><span>{{ __('scf.tax') }}</span><span>{{ number_format((float) $record->tax_amount, 2) }}</span></div>
    <div class="row total"><span>{{ __('scf.total') }}</span><span>{{ number_format((float) $record->total_amount, 2) }}</span></div>
    <div class="line"></div>
    @foreach($record->payments as $payment)
        <div class="row">
            <span>{{ $payment->method->label() }}</span>
            <span>{{ number_format((float) $payment->amount, 2) }}</span>
        </div>
    @endforeach
    <div class="line"></div>
    <div class="center">
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data={{ urlencode($record->reference_number) }}" width="80" height="80" alt="{{ $record->reference_number }}">
        <div style="margin-top:8px;letter-spacing:0.12em;font-size:10px;">*{{ $record->reference_number }}*</div>
    </div>
    <div class="center muted">{{ __('scf.thank_you') }}</div>
    <div class="center muted">{{ $printedAt->format('Y-m-d H:i') }}</div>
</body>
</html>

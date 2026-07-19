<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar', 'ckb']) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('scf.invoices') }} {{ $record->reference_number }}</title>
    <style>
        @page { size: A4; margin: 18mm; }
        * { box-sizing: border-box; }
        body { font-family: "Segoe UI", "Helvetica Neue", Arial, sans-serif; font-size: 13px; color: #0f172a; margin: 0; background: #fff; }
        .sheet { max-width: 210mm; margin: 0 auto; }
        .brand { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #0f172a; padding-bottom: 16px; margin-bottom: 24px; }
        .brand h1 { margin: 0; font-size: 22px; letter-spacing: 0.02em; }
        .brand .doc-type { font-size: 28px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #334155; }
        .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 28px; }
        .box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 16px; }
        .box h3 { margin: 0 0 8px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: #64748b; }
        .box p { margin: 4px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { padding: 10px 12px; border-bottom: 1px solid #e2e8f0; text-align: start; }
        th { background: #0f172a; color: #fff; font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; }
        .totals { margin-top: 20px; margin-inline-start: auto; width: 280px; }
        .totals .row { display: flex; justify-content: space-between; padding: 6px 0; }
        .totals .grand { border-top: 2px solid #0f172a; margin-top: 8px; padding-top: 10px; font-size: 16px; font-weight: 700; }
        .footer { margin-top: 40px; padding-top: 16px; border-top: 1px dashed #cbd5e1; color: #64748b; font-size: 11px; display: flex; justify-content: space-between; }
        .notes { margin-top: 24px; color: #475569; }
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
        <div class="doc-type">{{ __('scf.invoices') }}</div>
    </div>

    <div class="meta">
        <div class="box">
            <h3>{{ __('scf.customer') }}</h3>
            <p><strong>{{ $record->contact?->name ?? '—' }}</strong></p>
            @if($record->contact?->email)<p>{{ $record->contact->email }}</p>@endif
            @if($record->contact?->phone)<p>{{ $record->contact->phone }}</p>@endif
        </div>
        <div class="box">
            <h3>{{ __('scf.reference') }}</h3>
            <p><strong>{{ $record->reference_number }}</strong></p>
            <p>{{ __('scf.date') }}: {{ $record->invoice_date?->format('Y-m-d') }}</p>
            <p>{{ __('scf.due') }}: {{ $record->due_date?->format('Y-m-d') ?? '—' }}</p>
            <p>{{ __('scf.status') }}: {{ $record->status?->label() ?? $record->status }}</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ __('scf.reference') }}</th>
                <th>{{ __('scf.tax') }}</th>
                <th>{{ __('scf.total') }}</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $record->reference_number }}</td>
                <td>{{ number_format((float) $record->tax_amount, 2) }}</td>
                <td>{{ number_format((float) $record->total_amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="totals">
        <div class="row"><span>{{ __('scf.tax') }}</span><span>{{ number_format((float) $record->tax_amount, 2) }}</span></div>
        <div class="row grand"><span>{{ __('scf.total') }}</span><span>{{ number_format((float) $record->total_amount, 2) }}</span></div>
    </div>

    @if($record->notes)
        <div class="notes"><strong>{{ __('scf.notes') }}:</strong> {{ $record->notes }}</div>
    @endif

    <div class="footer">
        <span>{{ __('scf.thank_you') }}</span>
        <span>{{ __('scf.printed_at') }}: {{ $printedAt->format('Y-m-d H:i') }}</span>
    </div>
    <div style="margin-top:16px;text-align:center;">
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode($record->reference_number) }}" width="100" height="100" alt="{{ $record->reference_number }}">
        <div style="margin-top:6px;letter-spacing:0.14em;font-family:monospace;font-size:11px;">*{{ $record->reference_number }}*</div>
    </div>
</div>
</body>
</html>

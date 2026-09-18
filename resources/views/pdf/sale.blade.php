<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        .header { width: 100%; border-bottom: 2px solid #111; padding-bottom: 10px; margin-bottom: 16px; }
        .logo { max-height: 60px; }
        .company { font-size: 18px; font-weight: bold; }
        .muted { color: #555; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px; }
        th { background: #f3f3f3; text-align: left; }
        .right { text-align: right; }
        .totals { width: 340px; margin-left: auto; margin-top: 12px; }
        .totals td { border: none; padding: 4px 0; }
        .totals .grand { font-weight: bold; border-top: 1px solid #111; }
        h1 { font-size: 20px; margin: 0 0 8px; }
        .tagline-middle { text-align: center; color: #555; margin: 28px 0 12px; }
        .terms-footer { color: #555; font-size: 10px; border-top: 1px solid #ccc; padding-top: 6px; }
        .terms-footer strong { display: block; margin-bottom: 4px; color: #222; }
    </style>
</head>
<body>
    <table class="header" style="border:none">
        <tr>
            <td style="border:none; width:70%">
                @if($settings->logoPath())
                    <img class="logo" src="{{ $settings->logoPath() }}" alt="logo">
                @endif
                <div class="company">{{ $settings->name }}</div>
                <div class="muted">
                    @if($settings->address){{ $settings->address }}<br>@endif
                    @if($settings->phone)Phone: {{ $settings->phone }}@endif
                    @if($settings->email) &nbsp; Email: {{ $settings->email }}@endif
                </div>
            </td>
            <td style="border:none; text-align:right">
                <h1>{{ $title }}</h1>
                <div><strong>{{ $document->number }}</strong></div>
                <div>{{ $document->document_datetime?->format('d M Y, h:i A') }}</div>
            </td>
        </tr>
    </table>

    <p>
        <strong>Customer:</strong> {{ $document->customer?->name }}<br>
        @if($document->customer?->phone)Phone: {{ $document->customer->phone }}<br>@endif
        @if($document->customer?->address){{ $document->customer->address }}@endif
    </p>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Item</th>
                <th class="right">Qty</th>
                <th class="right">Unit Price</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($document->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->product_name }}</td>
                    <td class="right">{{ $item->quantity }}</td>
                    <td class="right">{{ $settings->formatMoney($item->unit_price) }}</td>
                    <td class="right">{{ $settings->formatMoney($item->line_total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td class="right">{{ $settings->formatMoney($document->subtotal) }}</td></tr>
        <tr><td>Discount</td><td class="right">{{ $settings->formatMoney($document->discount) }}</td></tr>
        @foreach($document->taxes as $tax)
            <tr>
                <td>{{ $tax->name }} ({{ number_format($tax->rate_percent, 2) }}%)</td>
                <td class="right">{{ $settings->formatMoney($tax->amount) }}</td>
            </tr>
        @endforeach
        <tr class="grand"><td>Total</td><td class="right">{{ $settings->formatMoney($document->total) }}</td></tr>
        @isset($paid)
            <tr><td>Paid</td><td class="right">{{ $settings->formatMoney($paid) }}</td></tr>
            <tr><td>Due</td><td class="right">{{ $settings->formatMoney($due) }}</td></tr>
        @endisset
    </table>

    @if($document->notes)
        <p><strong>Notes:</strong> {{ $document->notes }}</p>
    @endif
    @if($settings->taglineText())
        <p class="tagline-middle">{!! nl2br(e($settings->taglineText())) !!}</p>
    @endif
    @if($settings->invoiceTermsText())
        <htmlpagefooter name="invoiceFooter">
            <div class="terms-footer">
                <strong>Terms and Conditions</strong>
                {!! nl2br(e($settings->invoiceTermsText())) !!}
            </div>
        </htmlpagefooter>
        <sethtmlpagefooter name="invoiceFooter" value="on" />
    @endif
</body>
</html>

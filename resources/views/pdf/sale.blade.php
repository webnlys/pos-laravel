<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        th, td { border: 1px solid #ccc; padding: 6px; }
        th { background: #f3f3f3; text-align: left; }
        .header { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .header td { border: none; vertical-align: middle; padding: 8px 12px; }
        .header-logo { width: 18%; text-align: center; }
        .header-info { width: 50%; }
        .header-meta { width: 32%; text-align: right; }
        .right { text-align: right; }
        tfoot .grand td { font-weight: bold; }
        h1 { font-size: 20px; margin: 0 0 8px; }
        .tagline-middle { text-align: center; color: #555; margin: 28px 0 12px; }
        .terms-footer { color: #555; font-size: 12px; border-top: 1px solid #ccc; padding-top: 6px; }
        .terms-footer strong { display: block; margin-bottom: 4px; color: #222; font-size: 13px; }
        .terms-footer .terms-body { padding-left: 4px; margin-top: 6px; }
        .terms-list { margin: 6px 0 0 18px; padding: 0; }
        .terms-list li { margin: 0 0 4px; }
    </style>
</head>
<body>
    <table class="header" cellspacing="0" cellpadding="0">
        <tr>
            <td class="header-logo" style="border:none; border-right: 1px solid #999999; border-bottom: 1px solid #999999;">
                @if($settings->logoPath())
                    <img class="logo" src="{{ $settings->logoPath() }}" alt="logo">
                @endif
            </td>
            <td class="header-info" style="border:none; border-left: 1px solid #999999; border-right: 1px solid #999999; border-bottom: 1px solid #999999;">
                <div class="company" style="font-size: 18px; font-weight: bold;">{{ $settings->name }}</div>
                <div class="muted">
                    @if($settings->address)<strong>Address: </strong>{{ $settings->address }}<br>@endif
                    @if($settings->phone)<strong>Phone: </strong>{{ $settings->phone }}<br>@endif
                    @if($settings->email)<strong>Email: </strong>{{ $settings->email }}@endif
                </div>
            </td>
            <td class="header-meta" style="border:none; border-bottom: 1px solid #999999;">
                <h1>{{ $title }}</h1>
                <div><strong>Invoice No: </strong>{{ $document->number }}</div>
                <div><strong>Date: </strong>{{ $document->document_datetime?->format('d M Y, h:i A') }}</div>
            </td>
        </tr>
    </table>

    <p style="margin-top: 20px; margin-bottom: 20px;">
        <strong>Customer:</strong> {{ $document->customer?->name }}<br>
        @if($document->customer?->email)<strong>Email: </strong>{{ $document->customer->email }}<br>@endif
        @if($document->customer?->phone)<strong>Phone: </strong>{{ $document->customer->phone }}<br>@endif
        @if($document->customer?->address)<strong>Address: </strong>{{ $document->customer->address }}@endif
    </p>

    <table class="table-body" cellspacing="0" cellpadding="0">
        <thead>
            <tr>
                <th>#</th>
                <th>Product</th>
                <th class="right">Qty</th>
                <th class="right">Price ({{ $settings->currencyCode() }})</th>
                <th class="right">Discount ({{ $settings->currencyCode() }})</th>
                <th>Tax</th>
                <th class="right">Amount ({{ $settings->currencyCode() }})</th>
            </tr>
        </thead>
        <tbody>
            @foreach($document->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->product_name }}</td>
                    <td class="right">{{ $item->quantity }}{{ $item->unit_name ? ' '.$item->unit_name : '' }}</td>
                    <td class="right">{{ number_format((float) $item->unit_price, 2) }}</td>
                    <td class="right">{{ number_format((float) ($item->discount ?? 0), 2) }}</td>
                    <td>
                        @if($item->tax_name)
                            {{ $item->tax_name }} ({{ number_format($item->tax_rate_percent, 2) }}%)
                        @else
                            —
                        @endif
                    </td>
                    <td class="right">{{ number_format((float) $item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" class="right">Subtotal</td>
                <td class="right">{{ number_format((float) $document->subtotal, 2) }}</td>
            </tr>
            <tr>
                <td colspan="6" class="right">Discount</td>
                <td class="right">{{ number_format((float) $document->discount, 2) }}</td>
            </tr>
            <tr>
                <td colspan="6" class="right">Tax</td>
                <td class="right">{{ number_format((float) $document->tax_total, 2) }}</td>
            </tr>
            <tr class="grand">
                <td colspan="6" class="right">Total</td>
                <td class="right">{{ number_format((float) $document->total, 2) }}</td>
            </tr>
            <tr>
                <td colspan="6" class="right">Paid</td>
                <td class="right">{{ number_format((float) $document->paidAmount(), 2) }}</td>
            </tr>
            <tr>
                <td colspan="6" class="right">Due</td>
                <td class="right">{{ number_format((float) $document->dueAmount(), 2) }}</td>
            </tr>
            @if($document->advanceAmount() > 0)
            <tr>
                <td colspan="6" class="right">Advance</td>
                <td class="right">{{ number_format((float) $document->advanceAmount(), 2) }}</td>
            </tr>
            @endif
        </tfoot>
    </table>

    {{-- amount in words --}}
        <p>
            <strong>Amount in words:</strong>
            {{ $settings->amountInWords($document->total) }}
        </p>

    @if($document->notes)
        <p><strong>Notes:</strong> {{ $document->notes }}</p>
    @endif
    @if(count($settings->invoiceTermsLines()))
    <div class="terms-footer">
        <strong>Terms and Conditions</strong> <br/>
        <ul class="terms-list terms-body">
            @foreach($settings->invoiceTermsLines() as $line)
                <li>{{ $line }}</li>
            @endforeach
        </ul>
    </div>
    @endif
    @if($settings->invoiceTermsText())
        <htmlpagefooter name="invoiceFooter">
            <p class="tagline-middle">{!! nl2br(e($settings->taglineText())) !!}</p>
        </htmlpagefooter>
        <sethtmlpagefooter name="invoiceFooter" value="on" />
    @endif
</body>
</html>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #222;
            line-height: 1.6;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 6px;
        }

        th {
            background: #f3f3f3;
            text-align: left;
        }

        .header {
            width: 186mm;
            table-layout: fixed;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .header td {
            border: none;
            vertical-align: middle;
            padding: 8px 12px;
        }

        .header-logo {
            width: 30mm;
            text-align: center;
        }

        .header-info {
            width: 104mm;
        }

        .header-meta {
            width: 52mm;
            text-align: right;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            border: none;
            padding: 0 0 6px 0;
        }

        .line-company {
            font-size: 19px;
            font-weight: bold;
            white-space: nowrap;
            padding-bottom: 4px !important;
        }

        .line-tagline {
            color: #555;
            padding-bottom: 10px !important;
        }

        .line-title {
            font-size: 20px;
            font-weight: bold;
            padding-bottom: 10px !important;
        }

        .table-body {
            width: 100%;
            border-collapse: collapse;
        }

        .col-num {
            width: 6%;
        }

        .col-product {
            width: 34%;
        }

        .col-qty {
            width: 10%;
        }

        .col-price {
            width: 14%;
        }

        .col-discount {
            width: 12%;
        }

        .col-tax {
            width: 12%;
        }

        .col-amount {
            width: 12%;
        }

        .right {
            text-align: right;
        }

        tfoot .grand td {
            font-weight: bold;
        }

        h1 {
            font-size: 20px;
            margin: 0 0 8px;
        }

        .terms-footer {
            color: #555;
            font-size: 12px;
            border-top: 1px solid #ccc;
            padding-top: 6px;
        }

        .terms-footer strong {
            display: block;
            margin-bottom: 4px;
            color: #222;
            font-size: 13px;
        }

        .terms-footer .terms-body {
            padding-left: 4px;
            margin-top: 6px;
        }

        .terms-list {
            margin: 6px 0 0 18px;
            padding: 0;
        }

        .terms-list li {
            margin: 0 0 4px;
        }

        .signature-block {
            text-align: right;
            margin-top: 40px;
        }

        .signature-box {
            float: right;
            width: 220px;
            text-align: center;
        }

        .signature-box img {
            max-height: 50px;
            max-width: 200px;
            margin-bottom: 4px;
        }

        .signature-box .signature-space {
            height: 50px;
        }

        .signature-box .signature-line {
            border-top: 1px solid #333;
            margin-top: 4px;
            padding-top: 4px;
        }

        .print-footer {
            clear: both;
            text-align: center;
            color: #555;
            margin-top: 24px;
            padding-top: 8px;
            border-top: 1px solid #ccc;
        }

        .print-footer .print-date {
            font-size: 11px;
            color: #888;
            margin-top: 2px;
        }

        .barcode-wrap {
            text-align: center;
            margin-bottom: 8px;
        }

        .barcode-wrap img {
            width: 40mm;
            height: 8mm;
        }

        .line-barcode {
            text-align: right;
            padding-bottom: 6px !important;
        }

        .barcode-img {
            width: 40mm;
            height: 8mm;
        }


        .doc-status-banner {
            text-align: center;
            margin: 0 0 7px;
            font-size: 24px;
            font-weight: bold;
            line-height: 1.1;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .doc-status-banner .doc-type {
            color: #000;
        }

        .doc-status-banner .doc-status {
            margin-left: 6px;
            text-transform: none;
            letter-spacing: normal;
        }

        .doc-status-divider {
            border: none;
            border-top: 2px solid #000;
            margin: 0 0 10px;
        }

        .status-converted,
        .status-paid {
            color: #198754;
        }

        .status-unpaid {
            color: #dc3545;
        }

        .status-partial {
            color: #fd7e14;
        }

        .status-advance {
            color: #0dcaf0;
        }
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
                <table class="info-table" cellspacing="0" cellpadding="0">
                    <tr>
                        <td class="line-company">{{ $settings->name }}</td>
                    </tr>
                    @if($settings->taglineText())
                    <tr>
                        <td class="line-tagline">{{ $settings->taglineText() }}</td>
                    </tr>
                    @endif
                    @if($settings->address)
                    <tr>
                        <td><strong>Address: </strong>{{ $settings->address }}</td>
                    </tr>
                    @endif
                    @if($settings->phone)
                    <tr>
                        <td><strong>Phone: </strong>{{ $settings->phone }}</td>
                    </tr>
                    @endif
                    @if($settings->email)
                    <tr>
                        <td><strong>Email: </strong>{{ $settings->email }}</td>
                    </tr>
                    @endif
                    @if($settings->website)
                    <tr>
                        <td style="padding-bottom: 0;"><strong>Website: </strong>{{ $settings->website }}</td>
                    </tr>
                    @endif
                </table>
            </td>
            <td class="header-meta" style="border:none; border-bottom: 1px solid #999999;">
                <table class="info-table" cellspacing="0" cellpadding="0">
                    <tr>
                        <td class="line-barcode" align="right"><img class="barcode-img" src="{{ \App\Support\BarcodeImage::dataUri($document->number) }}" alt="barcode"></td>
                    </tr>
                    <tr>
                        <td align="right" style="padding-top: 5px;"><strong>Quotation No: </strong>{{ $document->number }}</td>
                    </tr>
                    <tr>
                        <td align="right" style="padding-bottom: 0;"><strong>Date: </strong>{{ $document->document_datetime?->format('d M Y') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <div class="doc-status-banner">
        <span class="doc-type">{{ $title }}</span>
        @if($document->status !== 'draft')
        <span class="doc-status status-{{ $document->status }}">({{ ucfirst($document->status) }})</span>
        @endif
    </div>
    <hr class="doc-status-divider">

    <p style="margin-top: 4px; margin-bottom: 20px;">
        <strong>Customer:</strong> {{ $document->customer?->name }}<br>
        @if($document->customer?->email)<strong>Email: </strong>{{ $document->customer->email }}<br>@endif
        @if($document->customer?->phone)<strong>Phone: </strong>{{ $document->customer->phone }}<br>@endif
        @if($document->customer?->address)<strong>Address: </strong>{{ $document->customer->address }}@endif
    </p>

    @php
    $fmtPct = fn ($value) => rtrim(rtrim(number_format((float) $value, 2), '0'), '.');
    $showLineTax = ($document->tax_mode ?? 'per_item') === 'per_item';
    $labelColspan = $showLineTax ? 6 : 5;
    $taxLabel = 'Tax';
    if (($document->tax_mode ?? null) === 'overall' && $document->overall_tax_name) {
    $taxLabel = $document->overall_tax_name.' ('.$fmtPct($document->overall_tax_rate_percent).'%)';
    } elseif ($showLineTax) {
    $usedTaxes = $document->items->filter(fn ($item) => $item->tax_name)->unique('tax_id');
    if ($usedTaxes->count() === 1) {
    $onlyTax = $usedTaxes->first();
    $taxLabel = $onlyTax->tax_name.' ('.$fmtPct($onlyTax->tax_rate_percent).'%)';
    }
    }
    @endphp
    <table class="table-body" width="100%" cellspacing="0" cellpadding="0">
        <thead>
            <tr>
                <th class="col-num">#</th>
                <th class="col-product">Product</th>
                <th class="right col-qty">Qty</th>
                <th class="right col-price">Unit Price ({{ $settings->currencyCode() }})</th>
                <th class="right col-discount">Discount ({{ $settings->currencyCode() }})</th>
                @if($showLineTax)
                <th class="right col-tax">{{ $taxLabel }}</th>
                @endif
                <th class="right col-amount">Total ({{ $settings->currencyCode() }})</th>
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
                @if($showLineTax)
                <td class="right">
                    @if($item->tax_name)
                    {{ number_format((float) $item->tax_amount, 2) }}
                    @else
                    —
                    @endif
                </td>
                @endif
                <td class="right">{{ number_format((float) $item->line_total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="{{ $labelColspan }}" class="right">Subtotal</td>
                <td class="right">{{ number_format((float) $document->subtotal, 2) }}</td>
            </tr>
            <tr>
                <td colspan="{{ $labelColspan }}" class="right">Discount</td>
                <td class="right">{{ number_format((float) $document->discount, 2) }}</td>
            </tr>
            <tr>
                <td colspan="{{ $labelColspan }}" class="right">{{ $taxLabel }}</td>
                <td class="right">{{ number_format((float) $document->tax_total, 2) }}</td>
            </tr>
            <tr class="grand">
                <td colspan="{{ $labelColspan }}" class="right">Total</td>
                <td class="right">{{ number_format((float) $document->total, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <p>
        <strong>Amount in words:</strong>
        {{ $settings->amountInWords($document->total) }}
    </p>

    @if($document->notes)
    <p><strong>Notes:</strong> {{ $document->notes }}</p>
    @endif
    @if(count($settings->quotationTermsLines()))
    <div class="terms-footer">
        <strong>Terms and Conditions</strong> <br />
        <ul class="terms-list terms-body">
            @foreach($settings->quotationTermsLines() as $line)
            <li>{{ $line }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="signature-block">
        <div class="signature-box">
            @if($settings->signaturePath())
            <img src="{{ $settings->signaturePath() }}" alt="signature">
            @else
            <div class="signature-space"></div>
            @endif
            <div class="signature-line">Authorized Signature</div>
        </div>
    </div>

    <div class="print-footer">
        <!-- <div class="barcode-wrap">
            <img src="{{ \App\Support\BarcodeImage::dataUri($document->number) }}" alt="barcode">
        </div> -->
        <div>Thank you for your business</div>
        <div>{{ $settings->name }}</div>
        <div class="print-date">Printed on: {{ now()->format('d M Y, h:i A') }}</div>
    </div>
</body>

</html>
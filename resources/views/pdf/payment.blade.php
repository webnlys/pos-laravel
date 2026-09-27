<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; line-height: 1.6; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px; }
        th { background: #f3f3f3; text-align: left; }
        .header { width: 186mm; table-layout: fixed; border-collapse: collapse; margin-bottom: 16px; }
        .header td { border: none; vertical-align: middle; padding: 8px 12px; }
        .header-logo { width: 30mm; text-align: center; }
        .header-info { width: 104mm; }
        .header-meta { width: 52mm; text-align: right; }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { border: none; padding: 0 0 6px 0; }
        .line-company { font-size: 19px; font-weight: bold; white-space: nowrap; padding-bottom: 4px !important; }
        .line-tagline { color: #555; padding-bottom: 10px !important; }
        .line-barcode { text-align: right; padding-bottom: 6px !important; }
        .barcode-img { width: 40mm; height: 8mm; }
        .right { text-align: right; }
        tfoot .grand td { font-weight: bold; }
        .doc-status-banner { text-align: center; margin: 0 0 7px; font-size: 24px; font-weight: bold; line-height: 1.1; text-transform: uppercase; letter-spacing: 2px; color: #0d6efd; }
        .doc-status-divider { border: none; border-top: 2px solid #000; margin: 0 0 10px; }
        .signature-block { text-align: right; margin-top: 40px; }
        .signature-box { float: right; width: 220px; text-align: center; }
        .signature-box img { max-height: 50px; max-width: 200px; margin-bottom: 4px; }
        .signature-box .signature-space { height: 50px; }
        .signature-box .signature-line { border-top: 1px solid #333; margin-top: 4px; padding-top: 4px; }
        .print-footer { clear: both; text-align: center; color: #555; margin-top: 24px; padding-top: 8px; border-top: 1px solid #ccc; }
        .print-footer .print-date { font-size: 11px; color: #888; margin-top: 2px; }
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
                    <tr><td class="line-company">{{ $settings->name }}</td></tr>
                    @if($settings->taglineText())
                        <tr><td class="line-tagline">{{ $settings->taglineText() }}</td></tr>
                    @endif
                    @if($settings->address)
                        <tr><td><strong>Address: </strong>{{ $settings->address }}</td></tr>
                    @endif
                    @if($settings->phone)
                        <tr><td><strong>Phone: </strong>{{ $settings->phone }}</td></tr>
                    @endif
                    @if($settings->email)
                        <tr><td style="padding-bottom: 0;"><strong>Email: </strong>{{ $settings->email }}</td></tr>
                    @endif
                </table>
            </td>
            <td class="header-meta" style="border:none; border-bottom: 1px solid #999999;">
                <table class="info-table" cellspacing="0" cellpadding="0">
                    <tr><td class="line-barcode" align="right"><img class="barcode-img" src="{{ \App\Support\BarcodeImage::dataUri($payment->number) }}" alt="barcode"></td></tr>
                    <tr><td align="right"><strong>Receipt No: </strong>{{ $payment->number }}</td></tr>
                    <tr><td align="right" style="padding-bottom: 0;"><strong>Date: </strong>{{ $payment->paid_at?->format('d M Y') }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="doc-status-banner">{{ $title }}</div>
    <hr class="doc-status-divider">

    <p style="margin-top: 4px; margin-bottom: 20px;">
        <strong>Customer:</strong> {{ $payment->customer?->name }}<br>
        @if($payment->customer?->email)<strong>Email: </strong>{{ $payment->customer->email }}<br>@endif
        @if($payment->customer?->phone)<strong>Phone: </strong>{{ $payment->customer->phone }}<br>@endif
        @if($payment->customer?->address)<strong>Address: </strong>{{ $payment->customer->address }}@endif
    </p>

    <table cellspacing="0" cellpadding="0">
        <tbody>
            <tr>
                <td><strong>Applied to invoice</strong></td>
                <td>{{ $payment->sale?->number ?? 'Advance (no invoice)' }}</td>
            </tr>
            <tr>
                <td><strong>Payment method</strong></td>
                <td>{{ ucfirst($payment->method) }}</td>
            </tr>
        </tbody>
        <tfoot>
            <tr class="grand">
                <td class="right">Amount received</td>
                <td class="right">{{ $settings->currencyCode() }} {{ number_format((float) $payment->amount, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <p style="margin-top: 16px;">
        <strong>Amount in words:</strong>
        {{ $settings->amountInWords($payment->amount) }}
    </p>

    @if($payment->notes)
        <p><strong>Notes:</strong><br>{!! nl2br(e($payment->notes)) !!}</p>
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
        <div>Thank you for your business</div>
        <div>{{ $settings->name }}</div>
        <div class="print-date">Printed on: {{ now()->format('d M Y, h:i A') }}</div>
    </div>
</body>
</html>

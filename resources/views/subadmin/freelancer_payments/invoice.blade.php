<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Invoice #{{ $payment->id }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            font-size: 13px;
            color: #1f2937;
            max-width: 1000px;
            margin: 0 auto;
            padding: 28px;
            line-height: 1.5;
        }
        .top-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 24px;
            margin-bottom: 36px;
            padding-bottom: 24px;
            border-bottom: 2px solid #e5e7eb;
        }
        .branding { text-align: left; }
        .branding-logo {
            width: 160px;
            height: auto;
            max-height: 72px;
            object-fit: contain;
        }
        .sender-details { text-align: right; }
        .sender-details .company-name {
            font-size: 18px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            color: #111827;
        }
        .sender-details .address {
            font-size: 12px;
            color: #6b7280;
            line-height: 1.6;
        }
        .sender-details .gstin {
            font-size: 11px;
            margin-top: 6px;
            font-weight: 600;
            color: #374151;
        }
        .mid-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 24px;
            margin-bottom: 28px;
        }
        .to-block { flex: 1; min-width: 220px; }
        .to-block .to-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
            margin-bottom: 10px;
        }
        .to-block .to-line {
            font-size: 13px;
            line-height: 1.7;
            color: #374151;
        }
        .to-block .to-line strong { display: inline-block; min-width: 100px; font-weight: 600; color: #111827; }
        .title-block { text-align: right; }
        .title-block .doc-title {
            font-size: 22px;
            font-weight: 700;
            margin: 0 0 6px 0;
            color: #111827;
        }
        .title-block .title-underline {
            height: 3px;
            background: #111827;
            margin-bottom: 10px;
            width: 80px;
            margin-left: auto;
        }
        .title-block .period { font-size: 12px; color: #6b7280; }

        .statement-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 20px 0;
            font-size: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            border-radius: 8px;
            overflow: hidden;
        }
        .statement-table th,
        .statement-table td {
            border: 1px solid #e5e7eb;
            padding: 12px 14px;
            text-align: left;
            vertical-align: top;
        }
        .statement-table th {
            background: #f9fafb;
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #374151;
        }
        .statement-table tbody tr:nth-child(even) { background: #fafafa; }
        .statement-table tbody tr:hover { background: #f3f4f6; }
        .statement-table .text-right { text-align: right; }
        .statement-table .col-date { width: 110px; white-space: nowrap; }
        .statement-table .col-transactions { width: 160px; }
        .statement-table .col-details { min-width: 180px; }
        .statement-table .col-visit { width: 110px; }
        .statement-table .col-rate { width: 90px; text-align: right; }
        .statement-table .col-received { width: 100px; text-align: right; }
        .statement-table .col-balance { width: 100px; text-align: right; }
        .details-line { margin: 2px 0; font-size: 11px; }
        .details-line strong { color: #4b5563; }
        .total-row {
            font-weight: 700;
            font-size: 13px;
            background: #f3f4f6 !important;
            border-top: 2px solid #d1d5db;
        }
        .total-row .col-balance { color: #059669; }
        .balance-row { background: #ecfdf5 !important; }
        .balance-row td { font-weight: 600; color: #047857; }
        @media print {
            body { padding: 16px; }
            .no-print { display: none !important; }
            .statement-table { box-shadow: none; }
        }
        .btn-print {
            background: #fe992e;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .btn-print:hover { background: #e08820; }
    </style>
</head>
<body>
    @if(empty($forApp))
    <button class="btn-print no-print" onclick="window.print()">🖨️ Print / Save as PDF</button>
    @endif

    <div class="top-section">
        <div class="branding">
            @if(!empty($logoBase64))
                <img src="data:image/png;base64,{{ $logoBase64 }}" alt="Carelix" class="branding-logo">
            @elseif(file_exists(public_path('images/carelix-logo.png')))
                <img src="{{ asset('images/carelix-logo.png') }}" alt="Carelix" class="branding-logo">
            @endif
        </div>
        <div class="sender-details">
            <div class="company-name">Carelix Healthcare Pvt. Ltd.</div>
            <div class="address">
                141, GMD Galleria, Sector 48,<br>
                Sohna Road, Gurgaon, Haryana 122101,<br>
                India
            </div>
            <div class="gstin">GSTIN 06AALCC6534D1ZS</div>
        </div>
    </div>

    <div class="mid-section">
        <div class="to-block">
            <div class="to-label">To</div>
            <div class="to-line"><strong>Name:</strong> {{ $payment->jobRequest->name ?? 'N/A' }}</div>
            <div class="to-line"><strong>ID:</strong> {{ $payment->jobRequest->id ?? '—' }}</div>
            <div class="to-line"><strong>Phone:</strong> {{ $payment->jobRequest->contact_no ?? '—' }}</div>
        </div>
        <div class="title-block">
            <h2 class="doc-title">Payment Statement</h2>
            <div class="title-underline"></div>
            <div class="period">{{ $payment->payment_date->format('d/m/Y') }} &nbsp;|&nbsp; Invoice #{{ $payment->id }}</div>
        </div>
    </div>

    <table class="statement-table">
        <thead>
            <tr>
                <th class="col-date">Date and time</th>
                <th class="col-transactions">Transactions</th>
                <th class="col-details">Details</th>
                <th class="col-visit">Visit</th>
                <th class="col-rate">Rate per day (₹)</th>
                <th class="col-received">Received amount (₹)</th>
                <th class="col-balance">Balance amount (₹)</th>
            </tr>
        </thead>
        <tbody>
            @php
                $fmt = function($val) {
                    $v = (float) $val;
                    $s = rtrim(sprintf('%.4f', $v), '0');
                    return rtrim($s, '.') ?: '0';
                };
                $fmtBalance = function($val) {
                    return number_format(round((float) $val, 2), 2, '.', '');
                };
                $runningBalance = round((float) ($balanceBeforeThisPayment ?? 0), 2);
            @endphp
            @if($lineItems->isNotEmpty())
                @foreach($lineItems as $item)
                @php
                    $rowAmount = (float) ($item->vendor_payment ?? 0);
                    $remainingAfterThisLead = round($runningBalance - $rowAmount, 2);
                    $runningBalance = $remainingAfterThisLead;
                    $absentDatesFormatted = '—';
                    if (!empty($item->absent_dates) && is_array($item->absent_dates)) {
                        $absentDatesFormatted = collect($item->absent_dates)->map(function ($d) {
                            try {
                                return \Carbon\Carbon::parse($d)->format('d/m/Y');
                            } catch (\Exception $e) {
                                return $d;
                            }
                        })->join(', ');
                    }
                @endphp
                <tr>
                    <td class="col-date">{{ $payment->payment_date->format('d/m/Y') }} {{ $payment->created_at ? $payment->created_at->format('H:i') : '' }}</td>
                    <td class="col-transactions">
                        <div class="details-line"><strong>Txn ID:</strong> {{ $payment->transaction_id ?: '—' }}</div>
                        <div class="details-line"><strong>Invoice No.:</strong> {{ $item->id }}</div>
                    </td>
                    <td class="col-details">
                        <div class="details-line"><strong>Lead ID:</strong> {{ $item->operationLead ? $item->operationLead->lead_id : 'N/A' }}</div>
                        <div class="details-line"><strong>Customer:</strong> {{ $item->operationLead ? $item->operationLead->customer_name : 'N/A' }}</div>
                        <div class="details-line"><strong>Service:</strong> {{ $item->operationLead ? $item->operationLead->query : 'N/A' }}</div>
                        <div class="details-line"><strong>Staff:</strong> {{ $item->staff_name ?? (optional($item->freelanceStaff)->name ?? 'N/A') }}</div>
                        <div class="details-line"><strong>Duty hrs:</strong> {{ $item->duty_hours ?? '—' }}</div>
                        <div class="details-line"><strong>Payment term:</strong> {{ $item->payment_term ?? '—' }}</div>
                        <div class="details-line"><strong>Absent date:</strong> {{ $absentDatesFormatted }}</div>
                    </td>
                    <td class="col-visit">
                        {{ $item->deployment_from_date ? $item->deployment_from_date->format('d/m/Y') : '—' }}
                        /
                        {{ $item->deployment_to_date ? $item->deployment_to_date->format('d/m/Y') : '—' }}
                    </td>
                    <td class="col-rate">{{ $fmt($item->vendor_rate_per_day ?? 0) }}</td>
                    <td class="col-received">{{ $fmt($rowAmount) }}</td>
                    <td class="col-balance">₹ {{ $fmtBalance($remainingAfterThisLead) }}</td>
                </tr>
                @endforeach
            @else
                <tr>
                    <td class="col-date">{{ $payment->payment_date->format('d/m/Y') }} {{ $payment->created_at ? $payment->created_at->format('H:i') : '' }}</td>
                    <td class="col-transactions">
                        <div class="details-line"><strong>Txn ID:</strong> {{ $payment->transaction_id ?: '—' }}</div>
                        <div class="details-line"><strong>Invoice No.:</strong> —</div>
                    </td>
                    <td class="col-details">—</td>
                    <td class="col-visit">—</td>
                    <td class="col-rate">—</td>
                    <td class="col-received">{{ $fmt($payment->amount) }}</td>
                    <td class="col-balance">₹ {{ $fmtBalance($balanceAmount ?? 0) }}</td>
                </tr>
            @endif
            <tr class="total-row balance-row">
                <td colspan="4" class="text-right">
                    <strong>Payment received</strong>
                </td>
                <td class="col-rate">—</td>
                <td class="col-received">₹ {{ $fmt($payment->amount) }}</td>
                <td class="col-balance">₹ {{ $fmtBalance($balanceAmount ?? 0) }}</td>
            </tr>
        </tbody>
    </table>

    @if($payment->description)
        <p style="font-size: 12px; color: #6b7280;"><strong>Description:</strong> {{ $payment->description }}</p>
    @endif

    <script>
        if (window.location.search.includes('print=1')) {
            window.onload = function() { window.print(); };
        }
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_id }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; font-size: 13px; color: #1f2937; max-width: 900px; margin: 0 auto; padding: 24px; line-height: 1.5; }
        .no-print { margin-bottom: 16px; }
        .btn { display: inline-block; padding: 10px 18px; border-radius: 6px; font-weight: 600; cursor: pointer; border: none; text-decoration: none; font-size: 13px; }
        .btn-print { background: #fe992e; color: #fff; }
        .btn-print:hover { background: #e08820; color: #fff; }
        .btn-download { background: #dc3545; color: #fff; margin-left: 8px; }
        .btn-download:hover { background: #c82333; color: #fff; }
        .invoice-header { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 24px; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 2px solid #e5e7eb; }
        .company-block { flex: 1; min-width: 260px; }
        .company-logo { margin-bottom: 8px; }
        .company-logo img { max-height: 86px; width: auto; display: block; }
        .company-name { font-size: 15px; font-weight: 700; color: #111827; margin-bottom: 4px; }
        .company-meta { font-size: 12px; color: #6b7280; line-height: 1.6; }
        .invoice-title-block { text-align: right; }
        .invoice-title-main { font-size: 28px; font-weight: 800; color: #111827; letter-spacing: 2px; }
        .bill-row { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 48px; margin-bottom: 24px; align-items: flex-start; }
        .bill-to-block { flex: 1; min-width: 220px; max-width: 45%; }
        .bill-to-label { font-size: 13px; font-weight: 700; margin-bottom: 10px; color: #374151; }
        .bill-to-line { font-size: 13px; color: #374151; line-height: 1.6; margin-bottom: 8px; }
        .bill-to-line:last-child { margin-bottom: 0; }
        .bill-to-line strong { font-weight: 600; color: #1f2937; }
        .invoice-meta-block { min-width: 260px; max-width: 45%; flex: 1; text-align: left; }
        .invoice-meta-label { font-size: 13px; font-weight: 700; margin-bottom: 10px; color: #374151; }
        .invoice-meta-line { font-size: 13px; color: #374151; line-height: 1.6; margin-bottom: 8px; }
        .invoice-meta-line:last-child { margin-bottom: 0; }
        .invoice-meta-line .meta-label { font-weight: 600; color: #1f2937; }
        .invoice-meta-line .meta-value { font-weight: 400; }
        .service-table { width: 100%; border-collapse: collapse; margin: 16px 0; font-size: 13px; }
        .service-table th, .service-table td { border: 1px solid #e2e8f0; padding: 12px 14px; text-align: left; vertical-align: top; }
        .service-table th { background: #f1f5f9; font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px; color: #334155; }
        .service-table .text-right { text-align: right; }
        .service-table .text-center { text-align: center; }
        .service-table .desc-cell { line-height: 1.6; }
        .service-table .desc-cell strong { display: inline-block; min-width: 110px; }
        .service-table .amount-deduction { color: #dc2626; }
        .summary-block { margin-top: 20px; max-width: 320px; margin-left: auto; font-size: 13px; border: 1px solid #e2e8f0; border-collapse: collapse; }
        .summary-block .summary-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 16px; border-bottom: 1px solid #e2e8f0; }
        .summary-block .summary-row:last-child { border-bottom: none; }
        .summary-block .summary-row .label { font-weight: 600; color: #334155; }
        .summary-block .summary-row .value { font-weight: 600; color: #0f172a; text-align: right; }
        .summary-block .summary-row.grand-total .label,
        .summary-block .summary-row.grand-total .value { font-weight: 700; font-size: 14px; color: #0f172a; }
        .footer-row { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 32px; margin-top: 32px; padding-top: 24px; border-top: 1px solid #e5e7eb; }
        .footer-left { flex: 1; min-width: 260px; }
        .amount-words { font-size: 12px; color: #4b5563; margin-bottom: 16px; }
        .company-details-block { font-size: 13px; color: #374151; line-height: 1.8; padding-left: 14px; border-left: 4px solid #fe992e; }
        .company-detail-line { margin-bottom: 6px; }
        .company-detail-line:last-child { margin-bottom: 0; }
        .footer-right { text-align: right; min-width: 220px; }
        .company-contact-list { list-style: none; margin: 0; padding: 0; }
        .company-contact-item { display: flex; align-items: center; justify-content: flex-end; gap: 10px; font-size: 12px; color: #374151; line-height: 1.6; margin-bottom: 8px; }
        .company-contact-item:last-child { margin-bottom: 0; }
        .company-contact-item .contact-icon { flex-shrink: 0; width: 18px; height: 18px; color: #6b7280; }
        .company-contact-item .contact-icon svg { width: 100%; height: 100%; display: block; }
        .company-contact-item a { color: #374151; text-decoration: none; }
        .company-contact-item a:hover { color: #111827; text-decoration: underline; }
        .bank-details-wrap { margin-top: 24px; display: flex; justify-content: center; }
        .bank-details-block { border: 1px solid #d1d5db; padding: 0; font-size: 13px; color: #111827; font-weight: 700; max-width: 480px; width: 100%; }
        .bank-details-block table { width: 100%; border-collapse: collapse; }
        .bank-details-block td { padding: 10px 14px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        .bank-details-block tr:last-child td { border-bottom: none; }
        .bank-details-block .bank-label { color: #111827; width: 42%; }
        .bank-details-block .bank-value { color: #111827; }
        .invoice-footer-note { margin-top: 28px; text-align: center; font-size: 12px; color: #374151; line-height: 1.9; }
        .invoice-footer-note .line-1 .em { font-style: italic; }
        .invoice-footer-note .line-2 { color: #6b7280; }
        .invoice-footer-note .line-3 { display: flex; align-items: center; justify-content: center; flex-wrap: wrap; gap: 4px 8px; }
        .invoice-footer-note .line-3 .highlight { background: #d1fae5; padding: 2px 8px; border-radius: 4px; color: #065f46; font-weight: 600; }
        .invoice-footer-note .line-3 a { color: #2563eb; text-decoration: underline; }
        .invoice-footer-note .line-3 a:hover { color: #1d4ed8; }
        .signature-label { font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 40px; }
        .signature-caption { font-size: 11px; color: #6b7280; margin-top: 4px; }
        .terms-block { margin-top: 28px; padding-top: 16px; border-top: 1px solid #e5e7eb; font-size: 11px; color: #4b5563; line-height: 1.6; }
        .terms-block .terms-title { font-size: 13px; font-weight: 700; margin-bottom: 10px; color: #374151; }
        .terms-block ol { margin: 0; padding-left: 20px; }
        .terms-block li { margin-bottom: 6px; }
        /* Keep footer and contact block together in PDF – no split across pages */
        .invoice-header,
        .bill-row,
        .invoice-content-block,
        .summary-block,
        .footer-row,
        .footer-left,
        .footer-right,
        .company-contact-list,
        .terms-block,
        .bank-details-wrap,
        .invoice-footer-note { page-break-inside: avoid; }
        /* PDF-only: table layout so footer columns don’t split across pages */
        body.for-pdf .footer-row { display: table; width: 100%; table-layout: fixed; }
        body.for-pdf .footer-row .footer-left { display: table-cell; vertical-align: top; width: 50%; padding-right: 16px; }
        body.for-pdf .footer-row .footer-right { display: table-cell; vertical-align: top; width: 50%; text-align: right; }
        body.for-pdf .company-contact-item { display: block; margin-bottom: 10px; line-height: 1.6; font-size: 12px; color: #374151; }
        body.for-pdf .company-contact-item .contact-icon { display: inline-block; width: 16px; height: 16px; margin-right: 8px; vertical-align: middle; }
        body.for-pdf .company-contact-item .contact-icon svg { width: 100%; height: 100%; }
        body.for-pdf { font-family: 'DejaVu Sans', sans-serif; }
        body.for-pdf .summary-block { display: table; width: 100%; max-width: 320px; margin-left: auto; border: 1px solid #e2e8f0; }
        body.for-pdf .summary-block .summary-row { display: table-row; }
        body.for-pdf .summary-block .summary-row .label,
        body.for-pdf .summary-block .summary-row .value { display: table-cell; padding: 10px 16px; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }
        body.for-pdf .summary-block .summary-row .value { text-align: right; }
        body.for-pdf .summary-block .summary-row:last-child .label,
        body.for-pdf .summary-block .summary-row:last-child .value { border-bottom: none; }
        /* PDF: match image structure – single page, table-based sections */
        body.for-pdf { padding: 16px; font-size: 12px; }
        body.for-pdf .invoice-header { display: table; width: 100%; table-layout: fixed; }
        body.for-pdf .invoice-header .company-block { display: table-cell; vertical-align: top; width: 60%; }
        body.for-pdf .invoice-header .invoice-title-block { display: table-cell; vertical-align: top; width: 40%; }
        body.for-pdf .bill-row { display: table; width: 100%; table-layout: fixed; }
        body.for-pdf .bill-row .bill-to-block { display: table-cell; vertical-align: top; width: 50%; padding-right: 48px; }
        body.for-pdf .bill-row .invoice-meta-block { display: table-cell; vertical-align: top; width: 50%; }
        body.for-pdf .bill-to-label { margin-bottom: 10px; }
        body.for-pdf .bill-to-line,
        body.for-pdf .invoice-meta-line { margin-bottom: 8px; font-size: 13px; line-height: 1.6; }
        body.for-pdf .service-table { font-size: 12px; }
        body.for-pdf .service-table th,
        body.for-pdf .service-table td { padding: 8px 10px; }
        body.for-pdf .terms-block { font-size: 11px; margin-top: 20px; padding-top: 12px; }
        body.for-pdf .terms-block li { margin-bottom: 4px; }
        body.for-pdf .bank-details-wrap { margin-top: 16px; }
        body.for-pdf .bank-details-block { font-size: 12px; }
        body.for-pdf .invoice-footer-note { margin-top: 16px; font-size: 11px; }
        @media print { .no-print { display: none !important; } body { padding: 16px; } }
    </style>
</head>
<body class="{{ !empty($forPdf) ? 'for-pdf' : '' }}">
    @if(empty($forPdf) && empty($forApp))
    <div class="no-print">
        <button type="button" class="btn btn-print" onclick="window.print()">🖨️ Print</button>
        <a href="{{ request()->url() }}?download=1" class="btn btn-download">📥 Download PDF</a>
    </div>
    @endif

    @php
        $lead = $invoice->operationLead;
        $workDays = (int) ($invoice->work_days ?? 1);
        $ratePerDay = $workDays > 0 ? round((float) $invoice->payment_amount / $workDays, 2) : (float) $invoice->payment_amount;
        $subTotal = (float) $invoice->payment_amount;
        $received = (float) $totalReceived;
        $balance = max(0, $subTotal - $received);
        $invoiceDate = $invoice->from_date ?? $invoice->created_at ?? now();
        $invoiceNo = $invoice->invoice_id ?? '—';
        $firstDeployment = \App\Models\OperationDeploymentDetails::where('operation_lead_id', $lead->id)->orderBy('deployment_from_date')->first();
        $dueDate = $firstDeployment && $firstDeployment->deployment_from_date ? $firstDeployment->deployment_from_date : $invoice->from_date;
        $paymentTermDisplay = $lead->payment_plan ?? ($firstDeployment ? $firstDeployment->payment_term : null) ?? '—';
        $invoiceFrom = $invoice->from_date ? \Carbon\Carbon::parse($invoice->from_date)->startOfDay() : null;
        $invoiceTo = $invoice->to_date ? \Carbon\Carbon::parse($invoice->to_date)->endOfDay() : null;
        $deploymentForInvoice = null;
        if ($invoiceFrom && $invoiceTo) {
            $deploymentForInvoice = \App\Models\OperationDeploymentDetails::where('operation_lead_id', $lead->id)
                ->where('deployment_from_date', '<=', $invoiceTo)
                ->where('deployment_to_date', '>=', $invoiceFrom)
                ->orderBy('deployment_from_date')
                ->first();
        }
        $staffNameRaw = ($deploymentForInvoice && !empty(trim($deploymentForInvoice->staff_name ?? ''))) ? $deploymentForInvoice->staff_name : ($lead->staff_name ?? '—');
        $staffNameDisplay = $staffNameRaw === '—' ? '—' : trim(preg_replace('/\s*\([^)]*\)\s*/', ' ', $staffNameRaw));
        if ($staffNameDisplay === '') {
            $staffNameDisplay = '—';
        }
        $serviceHrsDisplay = $lead->shift_type ? (stripos($lead->shift_type, 'hrs') !== false ? $lead->shift_type : $lead->shift_type . ' Hrs') : '—';
        $fromToDateDisplay = $invoice->from_date && $invoice->to_date ? $invoice->from_date->format('d.m.Y') . ' to ' . $invoice->to_date->format('d.m.Y') : '—';

        // Previous period absent deduction (vendor/freelancer absent in last service → show on next invoice)
        $hasAbsentDeduction = false;
        $absentDatesText = '';
        $absentDays = 0;
        $absentRate = 0;
        $absentAmount = 0;
        $previousInvoice = \App\Models\PaymentInvoice::where('operation_lead_id', $lead->id)
            ->where('to_date', '<', $invoice->from_date)
            ->orderBy('to_date', 'desc')
            ->first();
        if ($previousInvoice) {
            $prevFrom = \Carbon\Carbon::parse($previousInvoice->from_date)->startOfDay();
            $prevTo = \Carbon\Carbon::parse($previousInvoice->to_date)->endOfDay();
            $deployments = \App\Models\OperationDeploymentDetails::where('operation_lead_id', $lead->id)
                ->whereNotNull('absent_dates')
                ->where('deployment_from_date', '<=', $prevTo)
                ->where('deployment_to_date', '>=', $prevFrom)
                ->get();
            $absentDatesInPeriod = [];
            foreach ($deployments as $d) {
                $arr = is_array($d->absent_dates) ? $d->absent_dates : (is_string($d->absent_dates) ? json_decode($d->absent_dates, true) : []);
                if (empty($arr) || !is_array($arr)) continue;
                foreach ($arr as $dateStr) {
                    try {
                        $dt = \Carbon\Carbon::parse($dateStr)->startOfDay();
                        if ($dt->between($prevFrom, $prevTo)) {
                            $absentDatesInPeriod[$dt->format('Y-m-d')] = true;
                        }
                    } catch (\Exception $e) {}
                }
            }
            if (!empty($absentDatesInPeriod)) {
                $absentDays = count($absentDatesInPeriod);
                $absentRate = $previousInvoice->work_days > 0 ? round((float) $previousInvoice->payment_amount / (float) $previousInvoice->work_days, 2) : 0;
                $absentAmount = round($absentRate * $absentDays, 2);
                $hasAbsentDeduction = true;
                $datesForDisplay = array_keys($absentDatesInPeriod);
                sort($datesForDisplay);
                $absentDatesText = implode(', ', array_map(function($d) { return \Carbon\Carbon::parse($d)->format('d.m.Y'); }, $datesForDisplay));
            }
        }
        $displaySubTotal = $hasAbsentDeduction ? round($subTotal - $absentAmount, 2) : $subTotal;
        $rupee = !empty($forPdf) ? 'Rs. ' : '₹';
    @endphp

    <div class="invoice-header">
        <div class="company-block">
            <div class="company-logo">
                @php
                    $logoPath = public_path('images/carelix-logo.png');
                    $logoSrc = null;
                    if (file_exists($logoPath)) {
                        $logoSrc = !empty($forPdf)
                            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
                            : asset('images/carelix-logo.png');
                    }
                @endphp
                @if($logoSrc)
                    <img src="{{ $logoSrc }}" alt="Carelix">
                @endif
            </div>
            <div class="company-name">Carelix Healthcare Pvt. Ltd.</div>
            <div class="company-meta">
                Sohna Road, 141 Jmd Galleria, Gurugram, Sector 48, haryana, 122001, IN<br>
                PH: 7666426664
            </div>
        </div>
        <div class="invoice-title-block">
            <div class="invoice-title-main">INVOICE</div>
        </div>
    </div>

    <div class="bill-row">
        <div class="bill-to-block">
            <div class="bill-to-label">Bill to:</div>
            <div class="bill-to-line"><strong>Customer name:</strong> {{ $lead->customer_name ?? 'N/A' }}</div>
            <div class="bill-to-line"><strong>Patient name:</strong> {{ $lead->patient_name ?? '—' }}</div>
            <div class="bill-to-line"><strong>City:</strong> {{ $lead->location ?? '—' }}</div>
            <div class="bill-to-line"><strong>Number:</strong> {{ $lead->contact_no ?? '—' }}</div>
            <div class="bill-to-line"><strong>Address:</strong> {{ $lead->address ?? '—' }}</div>
        </div>
        <div class="invoice-meta-block">
            <div class="invoice-meta-line"><span class="meta-label">Invoice no.:</span> <span class="meta-value">{{ $invoiceNo }}</span></div>
            <div class="invoice-meta-line"><span class="meta-label">Invoice Date:</span> <span class="meta-value">{{ $invoiceDate ? \Carbon\Carbon::parse($invoiceDate)->format('d.m.Y') : '—' }}</span></div>
            <div class="invoice-meta-line"><span class="meta-label">Invoice Period:</span> <span class="meta-value">{{ $invoice->from_date ? $invoice->from_date->format('d.m.Y') : '—' }} to {{ $invoice->to_date ? $invoice->to_date->format('d.m.Y') : '—' }}</span></div>
            <div class="invoice-meta-line"><span class="meta-label">Due date:</span> <span class="meta-value">{{ $dueDate ? \Carbon\Carbon::parse($dueDate)->format('d.m.Y') : '—' }}</span></div>
            <div class="invoice-meta-line"><span class="meta-label">Payment term:</span> <span class="meta-value">{{ $paymentTermDisplay }}</span></div>
        </div>
    </div>

    <div class="invoice-content-block">
    <table class="service-table">
        <thead>
            <tr>
                <th>DESCRIPTION</th>
                <th class="text-center">QTY.</th>
                <th class="text-center">DAYS</th>
                <th class="text-right">RATE P/D</th>
                <th class="text-right">AMOUNT</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="desc-cell">
                {{ $lead->query ?? '—' }}<br>
                    {{ $staffNameDisplay }}<br>
                    {{ $fromToDateDisplay }}<br>
                    {{ $lead->patient_gender ?? '—' }}<br>
                    {{ $serviceHrsDisplay }}
                </td>
                <td class="text-center">1</td>
                <td class="text-center">{{ $workDays }}</td>
                <td class="text-right">{{ $rupee }}{{ number_format($ratePerDay, 2) }}</td>
                <td class="text-right">{{ $rupee }}{{ number_format($subTotal, 2) }}</td>
            </tr>
            @if($hasAbsentDeduction)
            <tr>
                <td class="desc-cell">
                    <strong>Absent (previous period):</strong> Absent on dates {{ $absentDatesText }}
                </td>
                <td class="text-center">1</td>
                <td class="text-center">{{ $absentDays }}</td>
                <td class="text-right">{{ $rupee }}{{ number_format($absentRate, 2) }}</td>
                <td class="text-right amount-deduction">-{{ $rupee }}{{ number_format($absentAmount, 2) }}</td>
            </tr>
            @endif
        </tbody>
    </table>

    <div class="summary-block">
        <div class="summary-row"><span class="label">Sub Total</span><span class="value">{{ $rupee }}{{ number_format($displaySubTotal, 2) }} INR</span></div>
        <div class="summary-row"><span class="label">Received</span><span class="value">{{ $rupee }}{{ number_format($received, 2) }} INR</span></div>
        <div class="summary-row"><span class="label">IGST/CGST (0.00%)</span><span class="value">{{ $rupee }}0.00 INR</span></div>
        <div class="summary-row"><span class="label">SGST (0.00%)</span><span class="value">{{ $rupee }}0.00 INR</span></div>
        <div class="summary-row grand-total"><span class="label">Grand Total</span><span class="value">{{ $rupee }}{{ number_format($displaySubTotal, 2) }} INR</span></div>
    </div>
    </div>

    <div class="footer-row">
        <div class="footer-left">
            <div class="company-details-block">
                <div class="company-detail-line">GST No.: 06AALCC6534D1ZS</div>
                <div class="company-detail-line">PAN No.: AALCC6534D</div>
                <div class="company-detail-line">CIN No.: U86904HR2024PTC119035</div>
                <div class="company-detail-line">TAN No.: RTKC08373B</div>
            </div>
        </div>
        <div class="footer-right">
            <ul class="company-contact-list">
                <li class="company-contact-item">
                    <span class="contact-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" stroke="none"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg></span>
                    <span>+91 7666426664</span>
                </li>
                <li class="company-contact-item">
                    <span class="contact-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" stroke="none"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg></span>
                    <span>info@carelixhealthcare.com</span>
                </li>
                <li class="company-contact-item">
                    <span class="contact-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" stroke="none"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zm6.93 6h-2.95c-.32-1.25-.78-2.45-1.38-3.56 1.84.63 3.37 1.91 4.33 3.56zM12 4.04c.83 1.2 1.48 2.53 1.91 3.96h-3.82c.43-1.43 1.08-2.76 1.91-3.96zM4.26 14C4.1 13.36 4 12.69 4 12s.1-1.36.26-2h3.38c-.08.66-.14 1.32-.14 2s.06 1.34.14 2H4.26zm.82 2h2.95c.32 1.25.78 2.45 1.38 3.56-1.84-.63-3.37-1.9-4.33-3.56zm2.95-8H5.08c.96-1.66 2.49-2.93 4.33-3.56C8.81 5.55 8.35 6.75 8.03 8zM12 19.96c-.83-1.2-1.48-2.53-1.91-3.96h3.82c-.43 1.43-1.08 2.76-1.91 3.96zM14.34 14H9.66c-.09-.66-.16-1.32-.16-2s.07-1.35.16-2h4.68c.09.65.16 1.32.16 2s-.07 1.34-.16 2zm.25 5.56c.6-1.11 1.06-2.31 1.38-3.56h2.95c-.96 1.65-2.49 2.93-4.33 3.56zM16.36 14c.08-.66.14-1.32.14-2s-.06-1.34-.14-2h3.38c.16.64.26 1.31.26 2s-.1 1.36-.26 2h-3.38z"/></svg></span>
                    <a href="https://carelixhealthcare.com/" target="_blank" rel="noopener">www.carelixhealthcare.com</a>
                </li>
            </ul>
        </div>
    </div>

    <div class="terms-block">
        <div class="terms-title">Terms &amp; Conditions</div>
        <ol>
            <li>Payment once made is non-refundable. However, full refund will be issued if the company is unable to provide the service or staff does not reach the service location.</li>
            <li>Service hours are calculated from the time staff reaches the service location.</li>
            <li>Any additional hours beyond the booked service period will be charged separately as per company rates.</li>
            <li>In case of any issue with the assigned staff, the company will provide a replacement subject to availability.</li>
            <li>The company shall not be liable for any medical complications, patient condition changes, or unforeseen incidents during the service period.</li>
            <li>The company is not responsible for any loss or damage to valuables or personal belongings at the service location.</li>
            <li>The client must provide a safe and suitable working environment for the staff during the service period.</li>
            <li>In case of delayed payment, the company reserves the right to suspend or withdraw the service.</li>
            <li>Client shall not directly hire company staff during service or within 6 months after service. Violation will attract {{ $rupee }}50,000 penalty.</li>
            <li>All disputes are subject to Gurugram, Haryana jurisdiction only.</li>
        </ol>
    </div>

    <div class="bank-details-wrap">
        <div class="bank-details-block">
            <table>
                <tr><td class="bank-label">Account Name</td><td class="bank-value">CARELIX HEALTHCARE PVT LTD</td></tr>
                <tr><td class="bank-label">Bank Name</td><td class="bank-value">HDFC Bank Ltd.</td></tr>
                <tr><td class="bank-label">Account Number</td><td class="bank-value">50200094795147</td></tr>
                <tr><td class="bank-label">RTGS/IFSC Code</td><td class="bank-value">HDFC0009110</td></tr>
                <tr><td class="bank-label">Branch</td><td class="bank-value">Palm Square Sector 66</td></tr>
            </table>
        </div>
    </div>

    <div class="invoice-footer-note">
        <div class="line-1">Auto generated <span class="em">invoice</span>, does not require signature</div>
        <div class="line-2">PDF Generated on {{ now()->format('d/m/Y') }}</div>
        <div class="line-3">
            <span class="highlight">Visit at: General Terms &amp; Conditions</span>
            <a href="https://carelixhealthcare.com/terms-website" target="_blank" rel="noopener">https://carelixhealthcare.com/terms&conditions</a>
            <span>/</span>
            <a href="https://carelixhealthcare.com/privacy-website" target="_blank" rel="noopener">https://carelixhealthcare.com/privacypolicy</a>
        </div>
    </div>

    <script>
        if (window.location.search.includes('print=1')) {
            window.onload = function() { window.print(); };
        }
    </script>
</body>
</html>

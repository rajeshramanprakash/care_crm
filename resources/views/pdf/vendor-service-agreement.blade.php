<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Vendor Service Agreement - Carelix</title>
<style>
  @page {
    size: A4 portrait;
    margin: 15mm 15mm 18mm 15mm;
  }

  body {
    font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
    font-size: 10pt;
    line-height: 1.5;
    color: #222222;
    margin: 0;
    padding: 0;
  }

  .top-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 6px;
  }

  .top-header-left {
    font-size: 8.5pt;
    line-height: 1.3;
  }

  .top-header-left .brand-name {
    font-weight: bold;
    color: #E65100;
    letter-spacing: 0.5px;
    text-transform: uppercase;
  }

  .top-header-left .sub-desc {
    color: #888888;
  }

  .top-header-right {
    font-size: 8.5pt;
    color: #888888;
  }

  .top-divider {
    border: none;
    border-top: 1.5px solid #E65100;
    margin: 0 0 16px 0;
  }

  .title-area {
    text-align: center;
    margin-bottom: 16px;
  }

  h1.doc-title {
    font-size: 16pt;
    font-weight: 800;
    color: #E65100;
    margin: 0 0 4px 0;
    letter-spacing: 0.5px;
    text-transform: uppercase;
  }

  .doc-subtitle {
    font-size: 10pt;
    color: #333333;
    font-weight: normal;
    margin: 0 0 2px 0;
  }

  .doc-version {
    font-size: 8.5pt;
    font-style: italic;
    color: #777777;
    margin: 0;
  }

  .party-table {
    width: 100%;
    border: 1px solid #dcdcdc;
    border-collapse: collapse;
    margin-bottom: 14px;
    font-size: 9pt;
  }

  .party-table td {
    vertical-align: top;
    padding: 10px 12px;
    width: 50%;
  }

  .party-table .left-box {
    background-color: #FFF9F5;
    border-right: 1px solid #dcdcdc;
  }

  .party-table .right-box {
    background-color: #FFFFFF;
  }

  .box-heading-orange {
    font-size: 9pt;
    font-weight: bold;
    color: #E65100;
    margin-bottom: 6px;
  }

  .box-heading-black {
    font-size: 9pt;
    font-weight: bold;
    color: #111111;
    margin-bottom: 6px;
  }

  .callout-box {
    background-color: #FFF9F5;
    border-left: 3.5px solid #E65100;
    padding: 8px 12px;
    font-size: 9pt;
    margin-bottom: 16px;
    line-height: 1.45;
  }

  h2.section-heading {
    font-size: 11pt;
    font-weight: bold;
    color: #E65100;
    margin: 18px 0 6px 0;
    text-transform: uppercase;
  }

  h3.sub-heading {
    font-size: 10pt;
    font-weight: bold;
    color: #333333;
    margin: 10px 0 4px 0;
  }

  p {
    margin: 0 0 8px 0;
    text-align: justify;
  }

  ul {
    margin: 2px 0 8px 20px;
    padding: 0;
  }

  li {
    margin-bottom: 4px;
    text-align: justify;
  }

  .section-divider {
    border: none;
    border-top: 1px solid #e5e5e5;
    margin: 14px 0;
  }

  table.data-table {
    width: 100%;
    border-collapse: collapse;
    margin: 10px 0 14px 0;
    font-size: 8.5pt;
  }

  table.data-table th, table.data-table td {
    border: 1px solid #cccccc;
    padding: 6px 8px;
    text-align: left;
  }

  table.data-table th {
    background-color: #FFF3EB;
    color: #E65100;
    font-weight: bold;
  }

  .signature-section {
    margin-top: 25px;
    page-break-inside: avoid;
  }

  .signature-boxes {
    width: 100%;
    margin-top: 12px;
    border-collapse: collapse;
  }

  .signature-boxes td {
    width: 50%;
    border: 1px dashed #aaaaaa;
    height: 100px;
    vertical-align: bottom;
    padding: 10px;
    font-size: 8.5pt;
    color: #555555;
    background-color: #fafafa;
  }

  .footer-bar {
    font-size: 8pt;
    color: #777777;
    text-align: center;
    margin-top: 24px;
    border-top: 1px solid #e0e0e0;
    padding-top: 6px;
  }
</style>
</head>
<body>

  <div class="top-header">
    <div class="top-header-left">
      <div class="brand-name">Carelix Healthcare Pvt. Ltd.</div>
      <div class="sub-desc">Vendor Service Agreement | Staff Supply | Agreement No: {{ $agreement_number ?? '(Will be generated after signature)' }}</div>
    </div>
    <div class="top-header-right">www.carelixhealthcare.com</div>
  </div>
  <hr class="top-divider">

  <div class="title-area">
    <h1 class="doc-title">Vendor Service Agreement</h1>
    <div class="doc-subtitle">Healthcare Staff Supply — Nurses, Caretakers & Allied Care Roles</div>
    <div class="doc-version">Version: v1.0 | Agreement No: {{ $agreement_number ?? '(Will be generated after signature)' }}</div>
  </div>

  <table class="party-table">
    <tr>
      <td class="left-box">
        <div class="box-heading-orange">COMPANY (Platform)</div>
        <strong>Carelix Healthcare Pvt. Ltd.</strong><br>
        CIN: U86904HR2024PTC119035<br>
        GST: 06AALCC6534D1ZS<br>
        PAN: AALCC6534D | TAN: RTKC08373B<br>
        Address: 141 JMD Galleria, Sohna Road,<br>
        Sector 48, Gurugram, Haryana – 122001
      </td>
      <td class="right-box">
        <div class="box-heading-black">VENDOR (Staff Supply Agency)</div>
        Entity Name: <strong>{{ $vendor->name ?: $vendor->customer_name }}</strong><br>
        Contact Person: <strong>{{ $vendor->customer_name ?: $vendor->name }}</strong><br>
        Mobile: <strong>{{ $vendor->contact_no }}</strong><br>
        Email: <strong>{{ $vendor->email ?: '—' }}</strong><br>
        Carelix Unique ID: <strong>{{ $partner_id ?? $vendor->lead_id }}</strong><br>
        Date of Agreement: <strong>{{ $agreementDate }}</strong>
      </td>
    </tr>
  </table>

  <div class="callout-box">
    <strong>KEY PRINCIPLE:</strong> Carelix pays the Vendor. The Vendor pays its Staff. The Vendor is fully responsible for its Staff — their conduct, qualifications, compliance, and all employment/contractual obligations. Carelix has no direct liability to the Vendor's Staff.
  </div>

  <h2 class="section-heading">1. PREAMBLE</h2>
  <p>This Vendor Service Agreement ("<strong>Agreement</strong>") is entered into between <strong>Carelix Healthcare Pvt. Ltd.</strong> ("<strong>Carelix</strong>") and the Vendor named above. The Vendor supplies verified healthcare staff to Carelix Clients through the Platform on the terms set out herein.</p>

  <hr class="section-divider">

  <h2 class="section-heading">2. DEFINITIONS</h2>
  <ul>
    <li>"<strong>Service</strong>" means the primary care service category the Vendor supplies staff for (e.g. Nursing Care, Patient Attendant, ICU Care, Physiotherapy Support).</li>
    <li>"<strong>Sub-service</strong>" means a specific role within a Service (e.g. under Nursing Care: Post-Surgery Nursing, ICU Nursing, Paediatric Nursing).</li>
    <li>"<strong>Shift Type</strong>" means 12-Hour Shift, 24-Hour Shift, or Single Visit.</li>
    <li>"<strong>Rate Confirmation Letter (RCL)</strong>" means the document confirming agreed rates per Service/Sub-service/City/Shift combination before deployment.</li>
    <li>"<strong>Payout Term</strong>" means the payment settlement cycle applicable to the Vendor as per Schedule A.</li>
    <li>"<strong>Staff</strong>" means the nurses, caretakers, attendants, ward boys, paramedics, and other care personnel supplied by the Vendor.</li>
  </ul>

  <hr class="section-divider">

  <h2 class="section-heading">3. VENDOR REGISTRATION AND ONBOARDING</h2>
  <ul>
    <li>Login via mobile number (OTP-based) on the Carelix app.</li>
    <li>Selection of profile type as Vendor.</li>
    <li>Submission of entity details: name, type, CIN/GSTIN/PAN, authorised contact person, staff categories available, cities covered.</li>
    <li>Upload of KYC: Aadhaar/PAN of authorised representative, business registration certificate, GSTIN certificate, bank details with cancelled cheque.</li>
    <li>Admin reviews, approves, and sends Leegality agreement for signing.</li>
  </ul>
  <p>Rates are confirmed via a separate Rate Confirmation Letter (RCL) before each deployment or rate revision. The RCL varies by Service, Sub-service, City, and Shift Type. Multiple RCLs may be active simultaneously for different cities. This Agreement does not need re-signing when rates change.</p>

  <hr class="section-divider">

  <h2 class="section-heading">4. NATURE OF RELATIONSHIP</h2>
  <p>The Vendor is an independent contractor supplying Staff. This Agreement does not create an employer-employee relationship between Carelix and the Vendor or its Staff. The Vendor bears full employment liability for its Staff including PF, ESI, wages, and all applicable statutory obligations.</p>

  <hr class="section-divider">

  <h2 class="section-heading">5. VENDOR OBLIGATIONS</h2>
  <h3 class="sub-heading">5.1 Staff Quality and Verification</h3>
  <ul>
    <li>Supply only qualified, trained, and background-verified Staff.</li>
    <li>Nursing Staff must hold valid State Nursing Council registration.</li>
    <li>All Staff must be identity-verified (Aadhaar) and criminal record checked before deployment.</li>
    <li>Maintain records of all background checks. Produce to Carelix within 48 hours of request.</li>
  </ul>

  <h3 class="sub-heading">5.2 Staff Conduct</h3>
  <ul>
    <li>The Vendor is fully responsible for Staff conduct during every Assignment.</li>
    <li>Staff must arrive on time, professionally dressed, with identification.</li>
    <li>Staff must not solicit Clients to book directly outside the Platform during the Vendor's active period and for 24 months after termination. Any Client first registered in the Carelix system is deemed a Carelix Client.</li>
    <li>In case of breach of the non-solicitation clause by the Vendor or its Staff, the Vendor shall pay Carelix liquidated damages equivalent to 12 months of average monthly billing for the relevant Client/Staff.</li>
  </ul>

  <h3 class="sub-heading">5.3 Replacement and Incident Reporting</h3>
  <ul>
    <li>Provide a qualified replacement with minimum 4 hours' notice if a Staff member cannot attend.</li>
    <li>Report any incident, complaint, or misconduct at a Client's premises to Carelix immediately. Cooperate fully in any investigation initiated by Carelix.</li>
  </ul>

  <h3 class="sub-heading">5.4 Statutory Compliance</h3>
  <ul>
    <li>Maintain all applicable licences and registrations (Contract Labour Act, Minimum Wages Act, PF, ESI).</li>
    <li>Maintain workmen's compensation and professional indemnity insurance for all deployed Staff.</li>
    <li>File all applicable tax returns and comply with GST obligations.</li>
  </ul>

  <hr class="section-divider">

  <h2 class="section-heading">6. VENDOR'S FULL LIABILITY FOR STAFF</h2>
  <p>The Vendor bears complete and exclusive responsibility for all Staff supplied to Carelix Clients. Carelix has no employment or contractual relationship with the Vendor's Staff.</p>
  <ul>
    <li>The Vendor is solely liable for all employment claims by Staff including wages, statutory dues, and wrongful termination.</li>
    <li>The Vendor is liable for all loss, damage, injury, or harm caused by its Staff to Clients or their property.</li>
    <li>The Vendor indemnifies Carelix fully against all claims, legal costs, and damages arising from Staff misconduct, employment disputes, or regulatory failures.</li>
  </ul>

  <hr class="section-divider">

  <h2 class="section-heading">7. PAYMENT TERMS</h2>
  <p><strong>PAYMENT DIRECTION:</strong> Carelix collects payment from Clients at its own rates. Carelix pays the Vendor the agreed rate per Service/Sub-service/City/Shift as confirmed in the RCL, based on actual duty shifts completed and verified by Carelix. The Vendor has no right to information about or claim over Carelix's Client rates.</p>
  <ul>
    <li>Payment calculated on actual duty shifts completed and verified by Carelix.</li>
    <li>Settlement as per Payout Term in Schedule A.</li>
    <li>Vendor must raise GST-compliant invoices where applicable. Carelix GSTIN: 06AALCC6534D1ZS</li>
    <li>TDS deducted as applicable under the Income Tax Act, 1961. Form 16A issued.</li>
    <li>Carelix may withhold payment during active complaints, disputes, or fraud investigations.</li>
    <li>Carelix may set off losses caused by Vendor's Staff against payments due.</li>
    <li>Payment disputes must be raised in writing within 21 days of settlement date.</li>
  </ul>

  <hr class="section-divider">

  <h2 class="section-heading">8. NON-SOLICITATION</h2>
  <p>During the term and for 24 months after termination, the Vendor must not directly hire, engage, or contract any Staff supplied under this Agreement for any competing arrangement. Breach attracts liquidated damages of 12 months of average monthly billing for the affected Staff member.</p>

  <h2 class="section-heading">9. CONFIDENTIALITY</h2>
  <p>Both Parties keep confidential all non-public information exchanged including rate cards, Client data, Staff details, and business processes. This obligation survives termination for 3 years.</p>

  <hr class="section-divider">

  <h2 class="section-heading">10. TERM AND TERMINATION</h2>
  <p>Commences on Activation. Auto-renews annually unless terminated. Either Party may terminate with 30 days' written notice. Immediate termination applies for false documents, unverified Staff, Staff causing harm, statutory non-compliance, or insolvency. On termination: profile removed, pending payments settled within 30 days. Active deployments must be responsibly handed over.</p>

  <hr class="section-divider">

  <h2 class="section-heading">11. DISPUTE RESOLUTION</h2>
  <p>Disputes resolved first by senior management discussion within 30 days, then by arbitration under the Arbitration and Conciliation Act, 1996. Sole arbitrator, seat: Gurugram. Language: English.</p>

  <h2 class="section-heading">12. GOVERNING LAW</h2>
  <p>Governed by the laws of India. Subject to arbitration, courts at Gurugram, Haryana have exclusive jurisdiction.</p>

  <hr class="section-divider">

  <h2 class="section-heading">GRIEVANCE OFFICER</h2>
  <p>In accordance with the Information Technology Act, 2000, and the DPDP Act, 2023:<br>
  Name: <strong>Komal Gulati</strong> | Designation: <strong>Grievance Redressal Officer</strong><br>
  Email: grievance@carelixhealthcare.com<br>
  Address: Carelix Healthcare Pvt. Ltd., 141 JMD Galleria, Sohna Road, Sector 48, Gurugram, Haryana – 122001<br>
  Response time: Within 30 days of receipt of complaint.</p>

  <hr class="section-divider">

  <h2 class="section-heading">SCHEDULE A — SERVICE, SUB-SERVICE, CITY, SHIFT RATE & PAYOUT TERMS</h2>
  <p style="font-size: 8.5pt;">Specific rates are NOT in this Agreement. They are confirmed via Rate Confirmation Letters (RCLs) issued before each deployment. Multiple RCLs may be active for different cities. This Agreement does not need re-signing when rates change — only a new RCL is required.</p>

  <h3 class="sub-heading">Section 1 — Registered Services, Sub-services & Cities</h3>
  <table class="data-table">
    <thead>
      <tr>
        <th>Service</th>
        <th>Sub-service</th>
        <th>City</th>
        <th>12-Hr Rate (per RCL)</th>
        <th>24-Hr Rate (per RCL)</th>
        <th>Single Visit (per RCL)</th>
      </tr>
    </thead>
    <tbody>
      @if(isset($scheduleRows) && count($scheduleRows) > 0)
        @foreach($scheduleRows as $row)
          <tr>
            <td>{{ $row['service'] }}</td>
            <td>{{ $row['sub_service'] }}</td>
            <td>{{ $row['city'] }}</td>
            <td>{{ $row['rate_12hr'] }}</td>
            <td>{{ $row['rate_24hr'] }}</td>
            <td>{{ $row['rate_visit'] }}</td>
          </tr>
        @endforeach
      @else
        <tr>
          <td>{{ $vendor->job_title ?: 'Staff Supply Care Services' }}</td>
          <td>General Supply</td>
          <td>{{ $vendor->location ?: 'Operating Cities' }}</td>
          <td>As per RCL</td>
          <td>As per RCL</td>
          <td>As per RCL</td>
        </tr>
      @endif
    </tbody>
  </table>

  <h3 class="sub-heading">Section 2 — Payout Terms</h3>
  <table class="data-table">
    <thead>
      <tr>
        <th>Payout Cycle</th>
        <th>Invoice Raised By</th>
        <th>Payment Due Within</th>
        <th>Method</th>
        <th>Min. Threshold</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>Weekly / Bi-weekly / Monthly</td>
        <td>End of cycle</td>
        <td>7 working days</td>
        <td>NEFT / IMPS / UPI</td>
        <td>₹ 0</td>
      </tr>
    </tbody>
  </table>

  <h3 class="sub-heading">Section 3 — Vendor GST Details</h3>
  <table class="data-table">
    <thead>
      <tr>
        <th>Vendor GSTIN / PAN</th>
        <th>GST Applicable on Invoice</th>
        <th>TDS Deducted by Carelix</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>{{ $vendor->pan_card ?: 'As submitted' }}</td>
        <td>As applicable</td>
        <td>Yes (As per IT Rules)</td>
      </tr>
    </tbody>
  </table>

  <h3 class="sub-heading">Section 4 — Vendor Declarations</h3>
  <ul>
    <li>Our entity is duly registered and legally authorised to supply healthcare staff in India.</li>
    <li>All Staff are background-verified including identity and criminal record checks.</li>
    <li>All nursing Staff hold valid State Nursing Council registration.</li>
    <li>We maintain all statutory compliances including PF, ESI, and professional tax for our Staff.</li>
    <li>We hold adequate workmen's compensation / employer's liability insurance for all deployed Staff.</li>
    <li>We accept full responsibility for the conduct and actions of our Staff during Carelix Assignments.</li>
    <li>All information and documents submitted are genuine and accurate.</li>
    <li>The person signing this Agreement is authorised to bind the entity.</li>
  </ul>

  <div class="signature-section">
    <h2 class="section-heading">SIGNATURES</h2>
    <p style="font-size: 8.5pt; color: #555555;">By signing below via Leegality, both Parties confirm they have read, understood, and agree to be bound by this Agreement and Schedule A.</p>
    <table class="signature-boxes">
      <tr>
        <td>
          <strong>For Carelix Healthcare Pvt. Ltd.</strong><br><br><br>
          Authorised Signatory<br>
          Date: {{ $agreementDate }}
        </td>
        <td>
          <strong>For Vendor (Staff Supply Agency)</strong><br><br><br>
          Digital Leegality Sign / eSign<br>
          Name: <strong>{{ $vendor->customer_name ?: $vendor->name }}</strong><br>
          Date: {{ $agreementDate }}
        </td>
      </tr>
    </table>
  </div>

  <div class="footer-bar">
    — Carelix Healthcare Pvt. Ltd. | www.carelixhealthcare.com | +91 7666426664 —
  </div>

</body>
</html>
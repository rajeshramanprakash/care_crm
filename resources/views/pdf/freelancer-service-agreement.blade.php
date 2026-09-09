<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Freelancer Service Agreement - Carelix</title>
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
    margin-bottom: 18px;
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
      <div class="sub-desc">Freelancer Service Agreement | Care Staff | Agreement No: {{ $agreement_number ?? '(Will be generated after signature)' }}</div>
    </div>
    <div class="top-header-right">www.carelixhealthcare.com</div>
  </div>
  <hr class="top-divider">

  <div class="title-area">
    <h1 class="doc-title">Freelancer Service Agreement</h1>
    <div class="doc-subtitle">Care Staff — Nurses, Caretakers, Attendants & Allied Roles</div>
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
        <div class="box-heading-black">FREELANCER (Service Provider)</div>
        Full Name: <strong>{{ $freelancer->name ?: $freelancer->customer_name }}</strong><br>
        Mobile: <strong>{{ $freelancer->contact_no ?: $freelancer->mobile }}</strong><br>
        Job Role: <strong>{{ $freelancer->job_title ?: '—' }}</strong><br>
        Carelix Unique ID: <strong>{{ $partner_id ?? $freelancer->partner_id }}</strong><br>
        Date of Agreement: <strong>{{ $agreementDate }}</strong>
      </td>
    </tr>
  </table>

  <h2 class="section-heading">1. PREAMBLE</h2>
  <p>This Freelancer Service Agreement ("<strong>Agreement</strong>") is entered into between <strong>Carelix Healthcare Pvt. Ltd.</strong> ("<strong>Carelix</strong>") and the Freelancer named above. Carelix operates a digital healthcare marketplace through which patients book home care and healthcare support services. The Freelancer wishes to register and provide care services through the Platform on a per-assignment basis.</p>

  <hr class="section-divider">

  <h2 class="section-heading">2. DEFINITIONS</h2>
  <ul>
    <li>"<strong>Service</strong>" means the primary care service category (e.g. Nursing Care, Patient Attendant, Physiotherapy Support, ICU Care).</li>
    <li>"<strong>Sub-service</strong>" means a specific role or specialisation within a Service (e.g. under Nursing Care: Post-Surgery Nursing, ICU Nursing, Paediatric Nursing).</li>
    <li>"<strong>Shift Type</strong>" means 12-Hour Shift, 24-Hour Shift, or Single Visit, as applicable.</li>
    <li>"<strong>Assignment</strong>" means a confirmed booking by a Client for the Freelancer's services at a specified location, date, and shift.</li>
    <li>"<strong>Carelix Unique ID</strong>" means the unique identifier assigned to the Freelancer upon Activation.</li>
    <li>"<strong>Rate Confirmation Letter (RCL)</strong>" means the document issued by Carelix confirming the Freelancer's agreed rates per service/sub-service/city/shift combination.</li>
    <li>"<strong>Payout Term</strong>" means the payment settlement cycle applicable to the Freelancer as specified in Schedule A.</li>
  </ul>

  <hr class="section-divider">

  <h2 class="section-heading">3. REGISTRATION AND ONBOARDING</h2>
  <ul>
    <li>Login via mobile number (OTP-based authentication).</li>
    <li>Selection of profile type as Freelancer.</li>
    <li>Submission of: full name, age, gender, mobile number, expected rate, shift preference, total experience, job title/service, preferred city.</li>
    <li>Upload of KYC Documents: Aadhaar (mandatory), PAN (mandatory), qualification certificate (optional but required for nursing roles).</li>
    <li>Entry of bank account details and UPI ID.</li>
    <li>Submission for admin verification and Activation.</li>
  </ul>
  <p>Rates are NOT fixed at registration. Each Freelancer's rate is agreed after a discussion between Carelix admin and the Freelancer, and confirmed via a Rate Confirmation Letter (RCL) before the first Assignment. The main Agreement does not need re-signing when rates change — only a new RCL is issued.</p>

  <hr class="section-divider">

  <h2 class="section-heading">4. NATURE OF RELATIONSHIP</h2>
  <p>The Freelancer is an independent contractor. This Agreement does not create an employer-employee relationship. Carelix has no PF, ESI, gratuity, or other statutory employment obligation towards the Freelancer.</p>

  <hr class="section-divider">

  <h2 class="section-heading">5. ASSIGNMENT ALLOCATION AND CONDUCT</h2>
  <h3 class="sub-heading">5.1 Assignment Process</h3>
  <ul>
    <li>Assignments allocated by Carelix based on the Freelancer's registered service, sub-service, city, and availability.</li>
    <li>Freelancer must confirm or reject within the specified time. Acceptance of an Assignment at the current RCL rate constitutes agreement to that rate.</li>
    <li>Report to Client's location on time with Carelix Unique ID and qualification certificate.</li>
    <li>Cancellations require minimum 4 hours' notice. Repeated no-shows attract warnings and may lead to suspension.</li>
  </ul>

  <h3 class="sub-heading">5.2 During Assignment</h3>
  <ul>
    <li>Provide care services with diligence and professional competence.</li>
    <li>Not accept cash/gifts beyond the agreed Assignment fee. Not bring unauthorised persons.</li>
    <li>Not share Client personal or medical information with any third party.</li>
    <li>Not solicit Clients to book directly outside the Platform during the active period and for 12 months after termination. Any Client first registered in the Carelix system is deemed a Carelix Client.</li>
    <li>In case of breach of the non-solicitation clause, the Freelancer shall pay Carelix liquidated damages equivalent to 6 months of average monthly earnings from Carelix.</li>
  </ul>

  <hr class="section-divider">

  <h2 class="section-heading">6. PROHIBITED CONDUCT</h2>
  <ul>
    <li>Physical, verbal, emotional, or financial abuse of Clients.</li>
    <li>Theft, damage, or misuse of Client property.</li>
    <li>Attending an Assignment under the influence of alcohol or any substance.</li>
    <li>Misrepresentation of qualifications or role.</li>
    <li>Photographing or recording Clients without consent.</li>
  </ul>

  <hr class="section-divider">

  <h2 class="section-heading">7. PAYMENT TERMS</h2>
  <p><strong>PAYMENT DIRECTION:</strong> Carelix collects payment from Clients at its own rates. Carelix pays the Freelancer the agreed day/shift rate confirmed in the RCL, based on actual duty shifts completed and verified by Carelix. The Freelancer has no right to information about or claim over the amount Carelix charges Clients.</p>
  <ul>
    <li>Payment is calculated on actual duty shifts completed and verified by Carelix.</li>
    <li>Settlement is made as per the Payout Term in Schedule A.</li>
    <li>TDS deducted as applicable under the Income Tax Act, 1961. Form 16A issued.</li>
    <li>Carelix may withhold payment during disputes or complaints.</li>
    <li>Payment disputes must be raised within 15 days of the relevant settlement date.</li>
  </ul>

  <hr class="section-divider">

  <h2 class="section-heading">8. BACKGROUND VERIFICATION</h2>
  <p>The Freelancer consents to Carelix conducting background verification including identity (Aadhaar), address, and criminal record checks. Activation is subject to satisfactory results. Carelix may conduct periodic re-verification.</p>

  <hr class="section-divider">

  <h2 class="section-heading">9. INDEMNITY AND LIABILITY</h2>
  <ul>
    <li>The Freelancer indemnifies Carelix against claims arising from negligent conduct, theft, damage, or breach of this Agreement.</li>
    <li>Carelix is not liable for clinical outcomes or disputes between Freelancer and Client outside the Platform.</li>
    <li>Carelix's maximum liability is limited to amounts paid to the Freelancer in the preceding 3 months.</li>
  </ul>

  <hr class="section-divider">

  <h2 class="section-heading">10. TERM AND TERMINATION</h2>
  <p>Commences on Activation, continues until terminated by either Party with 15 days' written notice. Immediate termination by Carelix applies for false documents, criminal conduct, repeated misconduct, or material breach. On termination: Carelix ID deactivated, pending payments settled within 30 days, non-solicitation obligations survive 12 months.</p>

  <hr class="section-divider">

  <h2 class="section-heading">11. DISPUTE RESOLUTION</h2>
  <p>Disputes resolved first by negotiation within 15 days, then by arbitration under the Arbitration and Conciliation Act, 1996. Sole arbitrator, seat: Gurugram. Language: English or Hindi.</p>

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

  <h2 class="section-heading">SCHEDULE A — SERVICE, SUB-SERVICE, CITY, SHIFT & RATE CONFIRMATION PROCESS</h2>
  <p style="font-size: 8.5pt;">Specific rates are NOT in this Agreement. They are confirmed via a separate Rate Confirmation Letter (RCL) issued by Carelix after rate discussion with the Freelancer. The Payout Term below applies to all Assignments. This Agreement does not need re-signing when rates are revised — only a new RCL is required.</p>

  <h3 class="sub-heading">Section 1 — Rate Confirmation Letter Process</h3>
  <ul>
    <li>After admin discussion, Carelix issues an RCL specifying: Service, Sub-service, City, Shift Type (12hr/24hr/Single Visit), Agreed Rate per shift, and Effective Date.</li>
    <li>The Freelancer's acceptance of the first Assignment after receiving the RCL constitutes acceptance of those rates.</li>
    <li>Alternatively, Freelancer confirms by replying 'Confirmed' via WhatsApp or app.</li>
    <li>Carelix collects from Clients at its own rates, not disclosed to the Freelancer.</li>
  </ul>

  <h3 class="sub-heading">Section 2 — Registered Service, Sub-service & City</h3>
  <table class="data-table">
    <thead>
      <tr>
        <th>Service</th>
        <th>Sub-service</th>
        <th>City</th>
        <th>Shift Type</th>
        <th>Rate (per RCL)</th>
        <th>Billed Per</th>
      </tr>
    </thead>
    <tbody>
      @if(isset($scheduleRows) && count($scheduleRows) > 0)
        @foreach($scheduleRows as $row)
          <tr>
            <td>{{ $row['service'] }}</td>
            <td>{{ $row['sub_service'] }}</td>
            <td>{{ $row['city'] }}</td>
            <td>{{ $row['shift'] }}</td>
            <td>{{ $row['rate'] }}</td>
            <td>{{ $row['billed_per'] }}</td>
          </tr>
        @endforeach
      @else
        <tr>
          <td>{{ $freelancer->job_title ?: 'Care Service' }}</td>
          <td>General Care</td>
          <td>{{ $freelancer->city ?: 'All Cities' }}</td>
          <td>{{ $freelancer->shift ? ($freelancer->shift === 'both' ? 'Both 12/24 Hr' : $freelancer->shift.' Hr') : '12 Hours' }}</td>
          <td>{{ $freelancer->expected_salary ? '₹ '.number_format((float)$freelancer->expected_salary, 2) : 'As per RCL' }}</td>
          <td>Per shift</td>
        </tr>
      @endif
    </tbody>
  </table>

  <h3 class="sub-heading">Section 3 — Payout Terms</h3>
  <table class="data-table">
    <thead>
      <tr>
        <th>Payout Cycle</th>
        <th>Settlement Day</th>
        <th>Payment Method</th>
        <th>Minimum Threshold</th>
        <th>TDS Applicable</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>Weekly / Bi-weekly / Monthly</td>
        <td>As per cycle terms</td>
        <td>NEFT / IMPS / UPI</td>
        <td>₹ 0</td>
        <td>Yes (As per IT Rules)</td>
      </tr>
    </tbody>
  </table>

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
          <strong>For Freelancer (Service Provider)</strong><br><br><br>
          Digital Leegality Sign / eSign<br>
          Name: <strong>{{ $freelancer->name ?: $freelancer->customer_name }}</strong><br>
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
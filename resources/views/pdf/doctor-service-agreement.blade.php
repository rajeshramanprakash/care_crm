<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Carelix Doctor Service Agreement — {{ $doctor->name }}</title>
    <style>
        @page { margin: 78px 40px 100px; }
        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: 10.5px;
            line-height: 1.25;
            color: #374151;
        }
        .page-header-fixed {
            position: fixed;
            top: -58px;
            left: 0;
            right: 0;
        }
        .top-header {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 6px;
        }
        .top-header td { vertical-align: top; padding: 0; }
        .brand {
            font-size: 12.5px;
            font-weight: 700;
            color: #F07F28;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            line-height: 1.25;
        }
        .brand-sub {
            font-size: 10.5px;
            color: #6b7280;
            margin-top: 0px;
            line-height: 1.3;
        }
        .header-url {
            text-align: right;
            font-size: 10.5px;
            color: #6C757D;
            padding-top: 3px;
        }
        .header-rule {
            border: 0;
            border-top: 1.5px solid #ea8a2b;
            margin: 6px 0 0;
            height: 0;
        }
        .doc-title {
            text-align: center;
            font-size: 17px;
            font-weight: 700;
            color: #ea8a2b;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            margin: 0 0 0px;
            line-height: 1.2;
        }
        .doc-subtitle {
            text-align: center;
            font-size: 10.5px;
            color: #4b5563;
            margin: 0 0 0px;
            line-height: 1.35;
        }
        .doc-version {
            text-align: center;
            font-size: 9px;
            color: #9ca3af;
            font-style: italic;
            margin: 0 0 20px;
        }
        h2 {
            font-size: 14.5px;
            color: #ea8a2b;
            margin: 16px 0 6px;
            font-weight: 700;
            border: 0;
            padding: 0;
        }
        h3 { font-size: 12.5px; margin: 8px 0 2px; color: #374151; font-weight: 700; }
        p { margin: 0 0 6px; text-align: justify; font-size: 12.5px; }
        ul { margin: 2px 0 6px 16px; padding: 0; }
        li { margin-bottom: 2px; font-size: 12.5px; }

        .party-wrap {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 18px;
            border: 1px solid #cfcfcf;
        }
        .party-wrap td {
            width: 50%;
            vertical-align: top;
            padding: 14px 16px;
        }
        .party-company {
            background-color: #FFF3E6;
            border-right: 1px solid #cfcfcf;
        }
        .party-doctor {
            background-color: #F2F3F5;
        }
        .party-title-company {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            color: #ea8a2b;
            letter-spacing: 0.03em;
            margin: 0 0 0px;
        }
        .party-title-doctor {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            color: #1f2937;
            letter-spacing: 0.03em;
            margin: 0 0 0px;
        }
        .party-line {
            margin: 0 0 0px;
            font-size: 10.5px;
            color: #374151;
            line-height: 1.4;
        }
        .party-company .party-line strong {
            color: #111827;
            font-size: 12px;
        }
        .field-row {
            margin: 0 0 7px;
            font-size: 9.5px;
            color: #374151;
            line-height: 1.35;
        }
        .field-table {
            width: 100%;
            border-collapse: collapse;
        }
        .field-table td {
            padding: 0 0 0px;
            vertical-align: bottom;
            font-size: 11.5px;
            color: #374151;
            background: transparent !important;
            border: 0 !important;
        }
        .field-label-td {
            width: 108px;
            white-space: nowrap;
            padding-right: 4px !important;
        }
        .field-value-td {
            border-bottom: 1px solid #6b7280 !important;
            font-weight: 600;
            color: #111827;
            height: 14px;
        }
        .field-label {
            display: inline-block;
            min-width: 98px;
            color: #374151;
        }
        .field-value {
            display: inline-block;
            border-bottom: 1px solid #6b7280;
            min-width: 145px;
            padding: 0 2px 1px;
            color: #111827;
            font-weight: 600;
        }

        table.schedule {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0 10px;
            font-size: 9.5px;
        }
        table.schedule th, table.schedule td {
            border: 1px solid #d1d5db;
            padding: 6px 7px;
            text-align: left;
            vertical-align: top;
        }
        table.schedule th {
            background: #ea8a2b;
    color: #ffffff;
            font-weight: 700;
        }
        .note {
            background: #FFF4E8;
            border: 1px solid #F0B27A;
            padding: 8px 10px;
            font-size: 9.5px;
            margin: 10px 0;
        }
        .footer {
            margin-top: 20px;
            padding-top: 8px;
            border-top: 1px solid #e5e7eb;
            font-size: 8.5px;
            color: #6b7280;
            text-align: center;
        }
        .page-break { page-break-before: always; }
        .muted { color: #6b7280; }
        .small { font-size: 12px; background-color: #F2F3F5; padding: 10px;}
        .smalll { font-size: 12px; background-color: #FFF3E6; padding: 10px;}
    </style>
</head>
<body>
    <div class="page-header-fixed">
        <table class="top-header">
            <tr>
                <td style="width:72%;">
                    <div class="brand">CARELIX HEALTHCARE PVT. LTD.</div>
                    <div class="brand-sub">Doctor Service Agreement | Version: v1.0 | Agreement No: {{ $agreement_number ?? '(Will be generated after signature)' }}</div>
                </td>
                <td class="header-url" style="width:28%;">www.carelixhealthcare.com</td>
            </tr>
        </table>
        <hr class="header-rule">
    </div>

    <div class="doc-title">DOCTOR SERVICE AGREEMENT</div>
    <div class="doc-subtitle">Platform Partner Agreement for Registered Doctors</div>
    <div class="doc-version">Version: v1.0 | Agreement No: {{ $agreement_number ?? '(Will be generated after signature)' }}</div>

    <table class="party-wrap">
        <tr>
            <td class="party-company">
                <div class="party-title-company">COMPANY (Platform)</div>
                <div class="party-line"><strong>Carelix Healthcare Pvt. Ltd.</strong></div>
                <div class="party-line">CIN: U86904HR2024PTC119035</div>
                <div class="party-line">GST: 06AALCC6534D1ZS</div>
                <div class="party-line">PAN: AALCC6534D | TAN: RTKC08373B</div>
                <div class="party-line">Address: 141 JMD Galleria, Sohna Road, Sector 48, Gurugram, Haryana – 122001</div>
            </td>
            <td class="party-doctor">
                <div class="party-title-doctor">DOCTOR (Service Provider)</div>
                <table class="field-table">
                    <tr>
                        <td class="field-label-td">Full Name:</td>
                        <td class="field-value-td">{{ $doctor->name }}@if(!$doctor->name)&nbsp;@endif</td>
                    </tr>
                    <tr>
                        <td class="field-label-td">Mobile:</td>
                        <td class="field-value-td">{{ $doctor->mobile ?: $doctor->contact_no }}@if(!($doctor->mobile ?: $doctor->contact_no))&nbsp;@endif</td>
                    </tr>
                    <tr>
                        <td class="field-label-td">Specialisation:</td>
                        <td class="field-value-td">{{ $specialisation }}@if($specialisation === '')&nbsp;@endif</td>
                    </tr>
                    <tr>
                        <td class="field-label-td">Medical Reg. No.:</td>
                        <td class="field-value-td">{{ $medicalRegNo }}@if($medicalRegNo === '')&nbsp;@endif</td>
                    </tr>
                    <tr>
                        <td class="field-label-td">Date of Agreement:</td>
                        <td class="field-value-td">{{ $agreementDate }}@if(!$agreementDate)&nbsp;@endif</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <h2>1. PREAMBLE</h2>
    <p>
        This Doctor Service Agreement ("Agreement") is entered into between Carelix Healthcare Pvt. Ltd.
        ("Carelix") and the Doctor named above ("Doctor"). Carelix operates a digital healthcare marketplace
        at www.carelixhealthcare.com through which patients book healthcare services. The Doctor wishes
        to register and offer consultation services through the Platform on the terms set out herein.
    </p>

    <h2>2. DEFINITIONS</h2>
    <ul>
        <li><strong>"Platform"</strong> means the Carelix website and mobile application through which Bookings are generated.</li>
        <li><strong>"Service"</strong> means the primary healthcare service category selected by the Doctor at registration (e.g. General Physician, Physiotherapy, Dermatology).</li>
        <li><strong>"Sub-service"</strong> means a specific specialisation or procedure within a Service.</li>
        <li><strong>"Consultation Mode"</strong> means Online (video/phone), Home Visit, or Clinic Visit.</li>
        <li><strong>"Doctor Agreed Rate"</strong> means the rate payable by Carelix to the Doctor per completed Booking as confirmed in Schedule A.</li>
        <li><strong>"Rate Review Period"</strong> means the 3-day window after registration submission during which Carelix admin reviews and may negotiate the Doctor's proposed rate before Activation.</li>
        <li><strong>"Activation"</strong> means the point at which Carelix approves the Doctor's profile and makes it publicly visible on the Platform.</li>
    </ul>

    <h2>3. REGISTRATION AND RATE REVIEW PROCESS</h2>
    <h3>3.1 Doctor Submission</h3>
    <p>
        At the time of registration, the Doctor selects their Service, Sub-service(s), Consultation Mode(s), and
        operating City, and proposes their consultation rate for each combination. This information is submitted
        to Carelix via the app for admin review.
    </p>
    <h3>3.2 Three-Day Review Period</h3>
    <p>
        After the Doctor submits registration, Carelix admin has 3 working days to review the proposed rates.
        During this period, admin may: (a) accept the proposed rates as-is, (b) negotiate revised rates with the
        Doctor via call or WhatsApp, or (c) reject the application. No Booking will be allocated and the Doctor
        will not go live until rates are confirmed and the Agreement is signed.
    </p>
    <ul>
        <li>The Doctor will be contacted by Carelix within 3 working days of submission.</li>
        <li>If rates are revised during the review, the final agreed rates are filled into Schedule A before the Agreement is sent for signing via Leegality.</li>
        <li>The Doctor's signature on this Agreement via Leegality confirms acceptance of the rates in Schedule A.</li>
    </ul>
    <h3>3.3 Activation</h3>
    <p>
        Upon signing, Carelix activates the Doctor's profile. The Doctor's profile displays on the Platform with
        Carelix's own customer-facing rates, which are set exclusively by Carelix and not disclosed to the Doctor.
    </p>

    <h2>4. NATURE OF RELATIONSHIP</h2>
    <p>
        The Doctor is an independent contractor. This Agreement does not create an employer-employee relationship.
        The Doctor is solely responsible for clinical decisions, outcomes, medical indemnity insurance, income tax
        compliance, and maintaining valid medical registration.
    </p>

    <h2>5. CONSULTATION MODES AND RATES</h2>
    <h3>5.1 Selected Modes</h3>
    <p>
        The Doctor offers services in the modes selected at registration and confirmed in Schedule A. Modes not
        selected are not active. Each mode may carry a different agreed rate per city and per service/sub-service combination.
    </p>
    <h3>5.2 Carelix's Customer Pricing</h3>
    <p>
        Carelix exclusively determines the rates charged to Patients on the Platform. These may vary based on offers,
        demand, or promotions. The Doctor has no right to information about or claim over amounts collected from
        Patients by Carelix. The Doctor's agreed rate in Schedule A is guaranteed regardless of what Carelix charges the Patient.
    </p>
    <h3>5.3 Rate Revision</h3>
    <p>
        Rate revisions require the Doctor's written request, Carelix's approval, and a new Schedule A signed by both
        Parties via Leegality. Already-confirmed Bookings are unaffected.
    </p>

    <h2>6. PAYMENT TERMS</h2>
    <ul>
        <li>Carelix pays the Doctor the agreed rate from Schedule A for each completed Booking within 7–15 working days.</li>
        <li>TDS will be deducted as applicable under the Income Tax Act, 1961. Form 16A will be issued.</li>
        <li>The Doctor is responsible for their own GST registration and compliance if applicable.</li>
        <li>Carelix may withhold payment during active disputes or complaints.</li>
    </ul>

    <h2>7. DOCTOR OBLIGATIONS</h2>
    <ul>
        <li>Maintain valid medical registration with the State/National Medical Council at all times.</li>
        <li>Provide consultation services with due care and professional competence.</li>
        <li>Honour confirmed Bookings and give minimum 4 hours' notice for unavoidable cancellations.</li>
        <li>Comply with Telemedicine Practice Guidelines 2020 for online consultations.</li>
        <li>Maintain patient confidentiality and comply with the DPDP Act, 2023.</li>
        <li>Not directly solicit patients acquired through the Platform during the term and for 12 months after termination. Any patient whose details first appeared in the Carelix system is deemed a Carelix patient regardless of subsequent contact.</li>
        <li>In case of breach of the non-solicitation clause, the Doctor shall pay Carelix liquidated damages equivalent to 6 months of average monthly earnings from Carelix.</li>
        <li>Notify Carelix immediately of any suspension or cancellation of medical registration.</li>
    </ul>

    <h2>8. CARELIX OBLIGATIONS</h2>
    <ul>
        <li>List the Doctor's profile after successful KYC verification, rate confirmation, and Agreement signing.</li>
        <li>Facilitate Booking management, patient payment collection, and settlement to the Doctor.</li>
        <li>Maintain confidentiality of the Doctor's KYC and bank details.</li>
    </ul>

    <h2>9. INTELLECTUAL PROPERTY</h2>
    <p>
        The Doctor grants Carelix a non-exclusive licence to display their name, photograph, qualifications, and
        profile on the Platform for facilitating Bookings. Carelix's brand and Platform remain its exclusive property.
    </p>

    <h2>10. INDEMNITY AND LIABILITY</h2>
    <ul>
        <li>The Doctor indemnifies Carelix against claims arising from medical negligence, regulatory action, or breach of this Agreement.</li>
        <li>Carelix's liability is limited to amounts paid to the Doctor in the preceding 3 months.</li>
        <li>Carelix is not liable for clinical outcomes of services provided by the Doctor.</li>
    </ul>

    <h2>11. TERM AND TERMINATION</h2>
    <ul>
        <li>This Agreement commences on Activation and continues until terminated.</li>
        <li>Either Party may terminate with 30 days' written notice.</li>
        <li>Carelix may terminate immediately for false information, lapsed registration, repeated complaints, or material breach.</li>
        <li>On termination: profile removed, pending payments settled within 30 days, non-solicitation obligations survive for 12 months.</li>
    </ul>

    <h2>12. DISPUTE RESOLUTION</h2>
    <p>
        Disputes resolved first by negotiation within 30 days, then by arbitration under the Arbitration and
        Conciliation Act, 1996. Sole arbitrator, seat: Gurugram, Haryana. Language: English.
    </p>

    <h2>13. GOVERNING LAW</h2>
    <p>
        This Agreement is governed by the laws of India. Subject to arbitration, courts at Gurugram, Haryana have exclusive jurisdiction.
    </p>

    <h2>GRIEVANCE OFFICER</h2>
    <p class="small">
        <strong>In accordance with the Information Technology Act, 2000, and the DPDP Act, 2023:</strong><br>
        <strong>Name:</strong> Komal Gulati &nbsp;|&nbsp;
        <strong>Designation:</strong> Grievance Redressal Officer<br>
        <strong>Email:</strong> grievance@carelixhealthcare.com<br>
        <strong>Address:</strong> Carelix Healthcare Pvt. Ltd., 141 JMD Galleria, Sohna Road, Sector 48, Gurugram, Haryana – 122001<br>
        <strong>Response time:</strong> Within 30 days of receipt of complaint.
    </p>

    <h2>CONTACT</h2>
    <p class="small">
        <strong>Carelix Healthcare Pvt. Ltd.</strong><br/>
        CIN: U86904HR2024PTC119035 | PAN: AALCC6534D | TAN: RTKC08373B | GST: 06AALCC6534D1ZS<br>
        Address: 141 JMD Galleria, Sohna Road, Sector 48, Gurugram, Haryana – 122001<br>
        Email: info@carelixhealthcare.com | Phone: +91 7666426664 | Website: www.carelixhealthcare.com
    </p>

    <div class="page-break"></div>

    <div class="doc-title" style="font-size:14px; margin-bottom:10px;">SCHEDULE A — SERVICE, SUB-SERVICE, CONSULTATION MODE &amp; AGREED RATES</div>
    <p class="smalll">
        This Schedule is filled by Carelix admin after the 3-day rate review period. The Doctor's
        signature on this Agreement confirms acceptance of all rates below. Carelix's customer-facing
        rates are not disclosed herein and remain Carelix's exclusive pricing decision.
    </p>

    <h2>Section 1 — Doctor Consultation Rates</h2>
    <table class="schedule">
        <thead>
            <tr>
                <th>Service</th>
                <th>Sub-service</th>
                <th>City</th>
                <th>Mode</th>
                <th>Doctor Agreed Rate (₹)</th>
                <th>Per Booking</th>
            </tr>
        </thead>
        <tbody>
            @if(count($scheduleRows))
                @foreach($scheduleRows as $row)
                    <tr>
                        <td>{{ $row['service'] }}</td>
                        <td>{{ $row['sub_service'] }}</td>
                        <td>{{ $row['city'] }}</td>
                        <td>{{ $row['mode'] }}</td>
                        <td>₹ {{ $row['rate'] }}</td>
                        <td>{{ $row['unit'] }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td>_________________</td>
                    <td>_________________</td>
                    <td>____________</td>
                    <td>Online</td>
                    <td>₹ _______</td>
                    <td>Per consultation</td>
                </tr>
                <tr>
                    <td>_________________</td>
                    <td>_________________</td>
                    <td>____________</td>
                    <td>Home Visit</td>
                    <td>₹ _______</td>
                    <td>Per visit</td>
                </tr>
                <tr>
                    <td>_________________</td>
                    <td>_________________</td>
                    <td>____________</td>
                    <td>Clinic Visit</td>
                    <td>₹ _______</td>
                    <td>Per consultation</td>
                </tr>
            @endif
        </tbody>
    </table>
    <p class="small muted">Add additional rows as required. Each service/sub-service/city/mode combination has its own agreed rate.</p>

    <h2>Section 2 — Home Visit Additional Details</h2>
    <table class="schedule">
        <thead>
            <tr>
                <th>Service</th>
                <th>Sub-service</th>
                <th>City</th>
                <th>Coverage Radius (km)</th>
                <th>Base Location</th>
            </tr>
        </thead>
        <tbody>
            @if(count($homeVisitRows))
                @foreach($homeVisitRows as $row)
                    <tr>
                        <td>{{ $row['service'] }}</td>
                        <td>{{ $row['sub_service'] }}</td>
                        <td>{{ $row['city'] }}</td>
                        <td>{{ $row['radius'] }} km</td>
                        <td>{{ $row['base_location'] }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td>_________________</td>
                    <td>_________________</td>
                    <td>____________</td>
                    <td>_______ km</td>
                    <td>_______________________</td>
                </tr>
            @endif
        </tbody>
    </table>

    <h2>Section 3 — Clinic Visit Additional Details</h2>
    <table class="schedule">
        <thead>
            <tr>
                <th>Service</th>
                <th>Sub-service</th>
                <th>City</th>
                <th>Clinic Name</th>
                <th>Clinic Address</th>
            </tr>
        </thead>
        <tbody>
            @if(count($clinicRows))
                @foreach($clinicRows as $row)
                    <tr>
                        <td>{{ $row['service'] }}</td>
                        <td>{{ $row['sub_service'] }}</td>
                        <td>{{ $row['city'] }}</td>
                        <td>{{ $row['clinic_name'] }}</td>
                        <td>{{ $row['clinic_address'] }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td>_________________</td>
                    <td>_________________</td>
                    <td>____________</td>
                    <td>_____________________</td>
                    <td>_____________________</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="note">
        <strong>IMPORTANT:</strong> Doctor Agreed Rates above are the only amounts payable to the Doctor per
        completed Booking. Carelix collects its own rates from Patients at its sole discretion. The
        Doctor has no right to information about or claim over Patient rates. TDS will be deducted as
        applicable.
    </div>

    <h2>SIGNATURES & EXECUTION</h2>
    <p>
        By signing below via Leegality, both Parties confirm they have read, understood, and agree to be bound
        by this Agreement and Schedule A.
    </p>

    <table style="width: 100%; border-collapse: collapse; margin-top: 15px; margin-bottom: 20px;">
        <tr>
            <td style="width: 48%; vertical-align: top; border: 1px solid #d1d5db; padding: 12px; background: #FFF3E6;">
                <div style="font-weight: 700; color: #ea8a2b; font-size: 11px; text-transform: uppercase; margin-bottom: 6px;">For Carelix Healthcare Pvt. Ltd.</div>
                <div style="height: 55px; border: 1px dashed #ea8a2b; background: #ffffff; padding: 6px; text-align: center; margin-bottom: 8px;">
                    <div style="font-size: 10px; font-weight: 700; color: #ea8a2b; text-transform: uppercase;">Carelix Healthcare Pvt. Ltd.</div>
                    <div style="font-size: 9px; font-weight: 700; color: #166534; margin-top: 4px;">✓ AUTHORISED SIGNATORY</div>
                    <div style="font-size: 8px; color: #6b7280;">Digitally Executed via Leegality</div>
                </div>
                <div style="font-size: 9.5px; color: #374151; line-height: 1.4;">
                    <strong>Signatory:</strong> Carelix Authorised Signatory<br>
                    <strong>Designation:</strong> Authorised Signatory<br>
                    <strong>Date:</strong> {{ $agreementDate }}
                </div>
            </td>
            <td style="width: 4%;"></td>
            <td style="width: 48%; vertical-align: top; border: 1px solid #d1d5db; padding: 12px; background: #F2F3F5;">
                <div style="font-weight: 700; color: #1f2937; font-size: 11px; text-transform: uppercase; margin-bottom: 6px;">For Doctor (Service Provider)</div>
                <div style="height: 55px; border: 1px dashed #6b7280; background: #ffffff; padding: 6px; text-align: center; margin-bottom: 8px;">
                    <div style="font-size: 10px; font-weight: 700; color: #1f2937; text-transform: uppercase;">{{ $doctor->name }}</div>
                    <div style="font-size: 9px; font-weight: 700; color: #166534; margin-top: 4px;">✓ Doctor eSigned</div>
                    <div style="font-size: 8px; color: #6b7280;">Digitally Executed via Leegality</div>
                </div>
                <div style="font-size: 9.5px; color: #374151; line-height: 1.4;">
                    <strong>Doctor Name:</strong> {{ $doctor->name }}<br>
                    <strong>Medical Reg. No:</strong> {{ $medicalRegNo ?: '—' }}<br>
                    <strong>Date:</strong> {{ $agreementDate }}
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        — Carelix Healthcare Pvt. Ltd. | www.carelixhealthcare.com | +91 7666426664 —
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Create {{ ucfirst($type) }} Account - Carelix CRM</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:400,600,700&display=swap">
  <link rel="stylesheet" href="{{ asset('plugins/fontawesome/css/all.min.css') }}">
  <link rel="stylesheet" href="{{ asset('adminlte/css/adminlte.min.css') }}">
  <link rel="stylesheet" href="{{ asset('plugins/toastr/toastr.min.css') }}">
  <style>
    body { margin: 0; font-family: 'Source Sans Pro', Arial, sans-serif; background: #fff; }
    .contain { display: flex; min-height: 100vh; }
    .left {
      background: #ea8a2b; color: #fff; flex: 1.2;
      display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 40px 30px;
    }
    .right { flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 40px 30px; background: #fff; overflow-y: auto; }
    .logo { margin-bottom: 20px; text-align: center; }
    .logo img { max-width: 200px; margin-bottom: 0px; }
    .register-title { font-size: 1.4rem; font-weight: 700; margin-bottom: 10px; color: #222; }
    .register-form { width: 100%; max-width: 450px; margin: 0 auto; }
    .register-form.doctor-register-form { max-width: 560px; }
    .register-form label { font-weight: 600; margin-bottom: 5px; display: block; color: #222; }
    .register-form input, .register-form select, .register-form textarea {
      width: 100%; padding: 10px 12px; margin-bottom: 18px; border: 1px solid #ccc; border-radius: 4px; font-size: 1rem;
    }
    .register-form input[type="file"] {
      padding: 8px;
      border: 2px dashed #ccc;
      background: #f9f9f9;
      cursor: pointer;
    }
    .register-form input[type="file"]:hover {
      border-color: #ea8a2b;
      background: #fff5eb;
    }
    .register-form textarea { min-height: 80px; resize: vertical; }
    .register-form button {
      width: 100%; background: #ea8a2b; color: #fff; border: none; padding: 12px; font-size: 1.1rem;
      border-radius: 4px; font-weight: 600; cursor: pointer; margin-bottom: 10px; transition: background 0.2s;
    }
    .register-form button:hover { background: #d97a1a; }
    .register-form button:disabled { background: #ccc; cursor: not-allowed; }
    .back-link { text-align: center; margin-top: 15px; }
    .back-link a { color: #ea8a2b; text-decoration: none; font-weight: 600; }
    .required { color: red; }
    @media (max-width: 900px) {
      .contain { flex-direction: column; }
      .left, .right { flex: unset; width: 100%; min-height: 350px; }
      .left { padding: 30px 10px; }
      .right { padding: 30px 10px; }
    }
    @if($type === 'doctor')
    .doctor-city-field .select2-container { width: 100% !important; margin-bottom: 18px; }
    .doctor-city-field .select2-container--default .select2-selection--single {
      height: 42px;
      border: 1px solid #ccc;
      border-radius: 4px;
    }
    .doctor-city-field .select2-container--default .select2-selection--single .select2-selection__rendered {
      line-height: 40px;
      padding-left: 12px;
      color: #222;
    }
    .doctor-city-field .select2-container--default .select2-selection--single .select2-selection__arrow {
      height: 40px;
    }
    .doctor-city-field .select2-container--default.select2-container--focus .select2-selection--single {
      border-color: #ea8a2b;
    }
    .select2-dropdown { border-color: #ccc; z-index: 9999; }
    .select2-search--dropdown .select2-search__field {
      border: 1px solid #ccc;
      border-radius: 4px;
      padding: 8px 10px;
    }
    .select2-results__option--highlighted.select2-results__option--selectable {
      background-color: #ea8a2b;
    }
    @endif
    @if(in_array($type, ['freelancer', 'vendor'], true))
    .register-form.freelancer-register-form,
    .register-form.vendor-register-form { max-width: 520px; }
    .register-declaration-list { display: flex; flex-direction: column; gap: 10px; margin-bottom: 18px; }
    .register-form label.register-declaration-item {
      display: flex; align-items: flex-start; gap: 12px; font-weight: 500;
      margin-bottom: 0; padding: 14px 16px; border: 1px solid #e0e0e0; border-radius: 10px;
      background: #fff; cursor: pointer; transition: border-color 0.15s, box-shadow 0.15s;
    }
    .register-form label.register-declaration-item:hover { border-color: #ea8a2b; box-shadow: 0 0 0 1px rgba(234,138,43,0.15); }
    .register-form label.register-declaration-item input[type="checkbox"] {
      width: auto; margin: 3px 0 0 0; flex-shrink: 0; accent-color: #ea8a2b;
    }
    .register-declaration-heading {
      display: block; font-weight: 700; color: #1a2340; margin-bottom: 6px; line-height: 1.35;
    }
    .register-declaration-text {
      display: block; font-weight: 400; font-size: 0.9rem; color: #555; line-height: 1.55;
    }
    .register-legal-link {
      color: #ea8a2b; font-weight: 600; text-decoration: underline;
      display: inline; padding: 2px 0; touch-action: manipulation;
      -webkit-tap-highlight-color: rgba(234, 138, 43, 0.25);
    }
    .register-legal-link:hover, .register-legal-link:active { color: #c96f1a; }
    .provider-help { display: block; margin: 4px 0 14px 0; font-size: 0.875rem; color: #666; line-height: 1.45; }
    .provider-sub-block {
      border: 1px solid #e8e4df; border-radius: 12px; padding: 0.85rem 1rem; margin-bottom: 0.75rem; background: #faf9f7;
    }
    .provider-sub-block-head {
      display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; margin-bottom: 0.65rem; flex-wrap: wrap;
    }
    .provider-sub-block-head strong { color: #1a2340; font-size: 0.95rem; flex: 1; min-width: 0; }
    .register-form.vendor-register-form button.provider-sub-remove-btn,
    .register-form.freelancer-register-form button.provider-sub-remove-btn {
      width: auto; flex-shrink: 0; margin: 0; padding: 0.35rem 0.7rem; font-size: 0.78rem; line-height: 1.2;
      font-weight: 600; background: #fff; color: #b91c1c; border: 1px solid #fecaca; border-radius: 6px; box-shadow: none;
    }
    .register-form.vendor-register-form button.provider-sub-remove-btn:hover,
    .register-form.freelancer-register-form button.provider-sub-remove-btn:hover {
      background: #fef2f2; border-color: #f87171; color: #991b1b;
    }
    .provider-tags-grid {
      display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; margin-bottom: 0;
    }
    @media (max-width: 520px) {
      .provider-tags-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 380px) {
      .provider-tags-grid { grid-template-columns: 1fr; }
    }
    .provider-tag-pill {
      display: block; margin: 0 !important; cursor: pointer; user-select: none; position: relative;
    }
    .provider-tag-pill input[type="checkbox"] {
      position: absolute; opacity: 0; width: 1px; height: 1px; padding: 0; margin: -1px;
      overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0;
    }
    .provider-tag-pill:focus-within .provider-tag-pill-inner {
      outline: 2px solid #ea8a2b; outline-offset: 2px;
    }
    .provider-tag-pill-inner {
      display: flex; align-items: center; justify-content: space-between; gap: 10px;
      min-height: 42px; padding: 9px 12px; border-radius: 10px; border: 2px solid #ea8a2b;
      background: #fff; color: #333; font-size: 0.84rem; font-weight: 600; line-height: 1.35;
      transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
    }
    .provider-tag-pill:hover .provider-tag-pill-inner {
      border-color: #d97a22; box-shadow: 0 2px 10px rgba(234, 138, 43, 0.18);
    }
    .provider-tag-pill input[type="checkbox"]:checked + .provider-tag-pill-inner {
      border-color: #e8892a; background: linear-gradient(135deg, #fe992e 0%, #e8892a 100%);
      color: #fff; box-shadow: 0 4px 14px rgba(234, 138, 43, 0.35);
    }
    .provider-tag-text {
      flex: 1; min-width: 0; white-space: normal; word-break: break-word; overflow-wrap: anywhere;
    }
    .provider-tag-icon {
      flex-shrink: 0; width: 1.1rem; height: 1.1rem; display: inline-flex; align-items: center; justify-content: center;
      border-radius: 50%; font-size: 0.68rem; font-weight: 700; line-height: 1;
      background: rgba(234, 138, 43, 0.15); color: #ea8a2b;
    }
    .provider-tag-pill input[type="checkbox"]:checked + .provider-tag-pill-inner .provider-tag-icon {
      background: rgba(255, 255, 255, 0.25); color: #fff;
    }
    .provider-tag-icon::before { content: '+'; }
    .provider-tag-pill input[type="checkbox"]:checked + .provider-tag-pill-inner .provider-tag-icon::before { content: '✓'; }
    .provider-sub-summary {
      border: 1px solid #e0ddd6; border-radius: 12px; padding: 0.85rem 1rem; margin-bottom: 16px; background: #fff;
    }
    .provider-sub-summary h6 { font-size: 0.88rem; font-weight: 700; margin: 0 0 0.65rem; color: #1a2340; }
    .provider-summary-row {
      border-bottom: 1px dashed #e8e4df; padding: 0.5rem 0; margin-bottom: 0;
    }
    .provider-summary-row:last-child { border-bottom: none; padding-bottom: 0; }
    .provider-summary-row strong { display: block; font-size: 0.88rem; color: #333; margin-bottom: 0.35rem; }
    .provider-summary-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 0; }
    .provider-tag-chip {
      display: inline-block; padding: 0.2rem 0.55rem; border-radius: 999px; background: #fff7ed;
      border: 1px solid #fdba74; font-size: 0.75rem; font-weight: 600; color: #9a3412;
    }
    .provider-price-panel {
      border: 1px solid #e8e8e8; border-radius: 10px; padding: 12px 14px; margin-bottom: 18px; background: #f9fafb;
    }
    .provider-price-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
    .provider-price-table th, .provider-price-table td {
      border: 1px solid #e5e7eb; padding: 8px 10px; text-align: left; vertical-align: top;
    }
    .provider-price-table th { background: #f3f4f6; font-weight: 700; }
    .provider-price-amount { display: block; font-weight: 700; color: #111; margin-bottom: 6px; }
    .provider-radius-label {
      display: block; font-size: 0.72rem; font-weight: 600; color: #64748b;
      text-transform: uppercase; letter-spacing: 0.03em; margin-bottom: 4px;
    }
    .provider-radius-input {
      width: 100%; max-width: 120px; height: 34px; padding: 4px 8px;
      border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem;
    }
    .provider-radius-input:focus {
      outline: none; border-color: #F07F28; box-shadow: 0 0 0 2px rgba(240, 127, 40, 0.15);
    }
    .provider-radius-row td { background: #fffaf5; }
    .bank-pay-method-row {
      display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 14px;
    }
    .bank-pay-method-card {
      display: block; margin: 0; cursor: pointer;
      border: 2px solid #e5e7eb; border-radius: 10px; padding: 12px 14px;
      background: #fff; transition: border-color .15s, background .15s, box-shadow .15s;
    }
    .bank-pay-method-card:hover { border-color: #fdba74; }
    .bank-pay-method-card.is-active {
      border-color: #F07F28; background: #fff7ed;
      box-shadow: 0 0 0 3px rgba(240, 127, 40, 0.12);
    }
    .bank-pay-method-card input { position: absolute; opacity: 0; pointer-events: none; }
    .bank-pay-method-title { display: block; font-weight: 700; color: #1f2937; font-size: 0.95rem; }
    .bank-pay-method-hint { display: block; font-size: 0.78rem; color: #6b7280; margin-top: 2px; }
    @endif
    @if(in_array($type, ['doctor', 'vendor', 'freelancer'], true))
    .reg-mobile-verify-row {
      display: grid;
      grid-template-columns: minmax(0, 1fr) auto;
      gap: 10px;
      align-items: center;
      width: 100%;
      margin-bottom: 18px;
    }
    .reg-mobile-verify-row input[type="tel"],
    .reg-mobile-verify-row input[type="text"] {
      width: 100% !important;
      max-width: 100%;
      margin-bottom: 0 !important;
      min-width: 0;
      box-sizing: border-box;
    }
    .register-form button.reg-otp-btn,
    .register-form button.doctor-otp-btn {
      width: auto !important;
      min-width: 7.5rem;
      margin-bottom: 0 !important;
      padding: 10px 16px;
      font-size: 0.9rem;
      font-weight: 700;
      color: #ea8a2b;
      background: #fff;
      border: 2px solid #ea8a2b;
      border-radius: 8px;
      cursor: pointer;
      white-space: nowrap;
      flex-shrink: 0;
    }
    .register-form button.reg-otp-btn:hover:not(:disabled),
    .register-form button.doctor-otp-btn:hover:not(:disabled) {
      background: #ea8a2b;
      color: #fff;
    }
    .register-form button.reg-otp-btn--verify,
    .register-form button.doctor-otp-btn--verify {
      color: #15803d;
      border-color: #15803d;
    }
    .register-form button.reg-otp-btn--verify:hover:not(:disabled),
    .register-form button.doctor-otp-btn--verify:hover:not(:disabled) {
      background: #15803d;
      color: #fff;
    }
    @media (max-width: 420px) {
      .reg-mobile-verify-row { grid-template-columns: 1fr; }
      .register-form button.reg-otp-btn,
      .register-form button.doctor-otp-btn { width: 100% !important; }
    }
    .reg-mobile-otp-wrap { margin-bottom: 14px; }
    .reg-mobile-verified-badge { display: block; font-size: 0.85rem; color: #15803d; font-weight: 700; margin-top: 6px; }
    input.reg-mobile-verified-lock,
    input.doctor-mobile-verified-lock { background: #f0fdf4 !important; border-color: #86efac !important; }
    @endif
    @if(!empty($registration_address_search_enabled))
    .doctor-address-field { position: relative; margin-bottom: 6px; }
    .doctor-address-suggestions {
      position: absolute; left: 0; right: 0; top: calc(100% + 4px); z-index: 20;
      margin: 0; padding: 0.25rem 0; list-style: none; background: #fff;
      border: 1px solid #cbd5e1; border-radius: 8px; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12);
      max-height: 220px; overflow-y: auto;
    }
    .doctor-address-suggestions[hidden] { display: none !important; }
    .doctor-address-suggestions__item {
      padding: 0.55rem 0.75rem; font-size: 0.9rem; line-height: 1.35; color: #334155; cursor: pointer;
    }
    .doctor-address-suggestions__item:hover,
    .doctor-address-suggestions__item.is-active { background: #fff7ed; color: #9a3412; }
    .doctor-address-suggestions__item--loading { color: #6b7280; font-style: italic; pointer-events: none; }
    .doctor-address-set-ok { display: block; font-size: 0.82rem; color: #15803d; font-weight: 600; margin: 4px 0 8px; }
    .doctor-address-set-ok[hidden] { display: none !important; }
    .doctor-address-set-missing { display: block; font-size: 0.82rem; color: #b45309; margin: 4px 0 8px; }
    .doctor-help { display: block; margin: -10px 0 16px 0; font-size: 0.875rem; color: #666; line-height: 1.45; }
    @endif
  </style>
  @if(!$locations->isEmpty())
  @include('includes.location-select-styles')
  @endif
</head>
<body>
  <div class="contain">
    <div class="left">
      <div class="logo">
        <img src="{{ asset('images/carelix-logo.png') }}" alt="Carelix Healthcare Logo">
      </div>
      <h2 style="text-align: center; margin-top: 20px;" data-i18n="form.create_account_type" data-i18n-type="{{ ucfirst($type) }}">Create {{ ucfirst($type) }} Account</h2>
      <p style="text-align: center; max-width: 400px; margin-top: 10px; line-height: 1.6;" data-i18n="form.required_note">
        Fill in your details to create your account. All fields marked with <span style="color: #fff;">*</span> are required.
      </p>
    </div>
    <div class="right">
      @include('partials.registration-language-bar', ['registration_languages' => $registration_languages ?? collect(), 'type' => $type])
      <div class="register-title" data-i18n="form.registration_title">Registration Form</div>
      
      <form class="register-form @if($type === 'doctor') doctor-register-form @endif @if($type === 'freelancer') freelancer-register-form @endif @if($type === 'vendor') vendor-register-form @endif" id="registerForm" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="type" value="{{ $type }}">
        
        @if($type === 'customer')
          <label for="customer_name">Customer Name <span class="required">*</span></label>
          <input type="text" name="customer_name" id="customer_name" required>
          
          <label for="contact_no">Mobile Number <span class="required">*</span></label>
          <input type="tel" name="contact_no" id="contact_no" maxlength="10" pattern="[0-9]{10}" required>
          
          <label for="location">Location</label>
          <select name="location" id="location" class="location-select">
            <option value="">Select Location</option>
            @foreach($locations as $location)
              <option value="{{ $location->name }}" data-state="{{ $location->state ?? '' }}" data-city-name="{{ $location->name }}">{{ $location->name }}</option>
            @endforeach
          </select>
          
          <label for="address">Address</label>
          @if(!empty($registration_address_search_enabled))
          <div class="doctor-address-field">
            <textarea name="address" id="address" rows="3" placeholder="Type your full address — pick a suggestion (Google Maps)" autocomplete="off" autocorrect="off" spellcheck="false"></textarea>
            <ul class="doctor-address-suggestions" id="customer_address_suggestions" hidden role="listbox"></ul>
          </div>
          <span class="doctor-address-set-ok" id="customer_address_set_ok" hidden><i class="fas fa-check-circle"></i> Address set from Google Maps</span>
          <span class="doctor-help" id="customer_address_hint">Select <strong>Location</strong> (city) above first, then type and choose your address from suggestions.</span>
          @else
          <textarea name="address" id="address" rows="3"></textarea>
          @endif
        
        @elseif($type === 'vendor')
          <label for="customer_name">Customer Name <span class="required">*</span></label>
          <input type="text" name="customer_name" id="customer_name" required>
          
          <label for="contact_no">Contact No <span class="required">*</span></label>
          @include('partials.registration-mobile-otp', [
            'otpPrefix' => 'vendor',
            'sendOtpRoute' => route('register.vendor.send-mobile-otp'),
            'verifyOtpRoute' => route('register.vendor.verify-mobile-otp'),
          ])
          
          <label for="name">Name <span class="required">*</span></label>
          <input type="text" name="name" id="name" required>
          
          <label for="age">Age <span class="required">*</span></label>
          <input type="number" name="age" id="age" min="0" max="150" required>
          
          <label for="gender">Gender <span class="required">*</span></label>
          <select name="gender" id="gender" required>
            <option value="">Select Gender</option>
            <option value="male">Male</option>
            <option value="female">Female</option>
            <option value="other">Other</option>
          </select>
          
          <label for="total_experience">Total Experience</label>
          <input type="text" name="total_experience" id="total_experience" placeholder="e.g., 2 years, 5 years">

          <label for="location">Location <span class="required">*</span></label>
          <select name="location" id="location" class="location-select" required>
            <option value="">Select Location</option>
            @foreach($locations as $location)
              <option value="{{ $location->name }}" data-state="{{ $location->state ?? '' }}" data-city-name="{{ $location->name }}">{{ $location->name }}</option>
            @endforeach
          </select>

          <label for="job_title">Job Title <span class="required">*</span></label>
          <select name="job_title" id="job_title" required disabled>
            <option value="">Select location first</option>
          </select>

          @include('partials.provider-registration-service-fields')

          <label for="shift">12/24 hr / One-time <span class="required">*</span></label>
          <select name="shift" id="shift" required>
            <option value="">Select option</option>
            <option value="12">12 Hours</option>
            <option value="24">24 Hours</option>
            <option value="both">Both</option>
            <option value="onetime">One-time</option>
          </select>
          
          <h5 style="margin-top: 20px; margin-bottom: 10px; color: #666;" data-i18n="section.documents">Documents</h5>
          
          <label for="aadhar_card">Aadhar Card <span class="required">*</span> (Image/PDF)</label>
          <input type="file" name="aadhar_card" id="aadhar_card" accept="image/*,.pdf" required>
          
          <label for="pan_card">PAN Card <span class="required">*</span> (Image/PDF)</label>
          <input type="file" name="pan_card" id="pan_card" accept="image/*,.pdf" required>
          
          <label for="qualification_certificate">Qualification Certificate (Optional) (Image/PDF)</label>
          <input type="file" name="qualification_certificate" id="qualification_certificate" accept="image/*,.pdf">
          
          <h5 style="margin-top: 20px; margin-bottom: 10px; color: #666;" data-i18n="section.bank">Bank Details</h5>
          
          <label for="account_name">Account Name <span class="required">*</span></label>
          <input type="text" name="account_name" id="account_name" required>
          
          <label for="account_number">Account Number <span class="required">*</span></label>
          <input type="text" name="account_number" id="account_number" required>
          
          <label for="ifsc_code">IFSC Code <span class="required">*</span></label>
          <input type="text" name="ifsc_code" id="ifsc_code" required>
          
          <label for="upi_id">UPI ID</label>
          <input type="text" name="upi_id" id="upi_id">
          
          <label for="bank_document">Bank Document (Cancelled Cheque) <span class="required">*</span> (Image/PDF)</label>
          <input type="file" name="bank_document" id="bank_document" accept="image/*,.pdf" required>

          <h5 style="margin-top: 24px; margin-bottom: 12px; color: #222; font-weight: 700;" data-i18n="section.declarations">Declarations</h5>
          <div class="register-declaration-list">
            <label class="register-declaration-item">
              <input type="checkbox" name="declaration_entity_accurate" id="declaration_entity_accurate" value="1" required>
              <span>
                <span class="register-declaration-heading">1. Our entity details and documents are accurate</span>
                <span class="register-declaration-text">The entity details submitted (name, type, CIN / PAN / GSTIN) are accurate and genuine. All documents uploaded belong to this entity. I am authorised to register and sign on behalf of this entity. Our entity is legally authorised to supply healthcare and care support staff in India.</span>
              </span>
            </label>
            <label class="register-declaration-item">
              <input type="checkbox" name="declaration_staff_responsibility" id="declaration_staff_responsibility" value="1" required>
              <span>
                <span class="register-declaration-heading">2. We accept full responsibility for our staff</span>
                <span class="register-declaration-text">All staff we supply are background-verified, including identity and criminal record checks. Nursing staff supplied by us hold valid State Nursing Council registration. We maintain all statutory compliances (PF, ESI, professional tax) for our staff. We accept full responsibility for the conduct, qualifications, and actions of all staff supplied to Carelix clients. Staff rates will be confirmed separately via a Rate Confirmation Letter before deployment.</span>
              </span>
            </label>
            <label class="register-declaration-item">
              <input type="checkbox" name="declaration_agrees_terms" id="vendor_declaration_agrees_terms" value="1" required>
              <span>
                <span class="register-declaration-heading">3. I agree to Carelix's terms on behalf of this entity</span>
                <span class="register-declaration-text">I have read and agree to the Carelix <a href="https://carelixhealthcare.com/terms-vendor.php" class="register-legal-link" target="_blank" rel="noopener noreferrer" onclick="event.stopPropagation();">Vendor Terms &amp; Conditions</a> and <a href="https://carelixhealthcare.com/privacy-vendor.php" class="register-legal-link" target="_blank" rel="noopener noreferrer" onclick="event.stopPropagation();">Vendor Privacy Policy</a>. I consent to Carelix collecting, storing, and processing our entity and KYC data for verification and platform operations. I understand that our profile will only go live after Carelix completes its verification and we sign the Vendor Service Agreement via Leegality. I authorise Carelix and its representatives to contact me via Call, SMS, Email or WhatsApp regarding our account and deployments.</span>
              </span>
            </label>
          </div>
        
        @elseif($type === 'freelancer')
          <label for="name">Name <span class="required">*</span></label>
          <input type="text" name="name" id="name" required>
          
          <label for="contact_no">Contact No <span class="required">*</span></label>
          @include('partials.registration-mobile-otp', [
            'otpPrefix' => 'freelancer',
            'sendOtpRoute' => route('register.freelancer.send-mobile-otp'),
            'verifyOtpRoute' => route('register.freelancer.verify-mobile-otp'),
          ])
          
          <label for="age">Age <span class="required">*</span></label>
          <input type="number" name="age" id="age" min="0" max="150" required>
          
          <label for="gender">Gender <span class="required">*</span></label>
          <select name="gender" id="gender" required>
            <option value="">Select Gender</option>
            <option value="male">Male</option>
            <option value="female">Female</option>
            <option value="other">Other</option>
          </select>
          
          <label for="total_experience">Total Experience</label>
          <input type="text" name="total_experience" id="total_experience" placeholder="e.g., 2 years, 5 years">

          <label for="location">Location <span class="required">*</span></label>
          <select name="location" id="location" class="location-select" required>
            <option value="">Select Location</option>
            @foreach($locations as $location)
              <option value="{{ $location->name }}" data-state="{{ $location->state ?? '' }}" data-city-name="{{ $location->name }}">{{ $location->name }}</option>
            @endforeach
          </select>

          <div id="freelancerFullAddressWrap" style="display:none;">
            <label for="full_address">Full Address <span class="required">*</span></label>
            @if(!empty($registration_address_search_enabled))
            <div class="doctor-address-field">
              <textarea name="full_address" id="full_address" rows="3" placeholder="Type your full address — pick a suggestion (Google Maps)" autocomplete="off" autocorrect="off" spellcheck="false"></textarea>
              <ul class="doctor-address-suggestions" id="freelancer_full_address_suggestions" hidden role="listbox"></ul>
            </div>
            <span class="doctor-address-set-ok" id="freelancer_full_address_set_ok" hidden><i class="fas fa-check-circle"></i> Address set from Google Maps</span>
            <span class="doctor-help">Suggestions are limited to the <strong>Location</strong> (city) you selected above.</span>
            @else
            <textarea name="full_address" id="full_address" rows="3" placeholder="House / street / landmark, area…" style="width:100%; resize:vertical;"></textarea>
            @endif
            <input type="hidden" name="full_address_lat" id="full_address_lat" value="">
            <input type="hidden" name="full_address_lng" id="full_address_lng" value="">
            <button type="button" id="fetchFullAddressByGpsBtn" class="btn btn-outline-secondary btn-sm" style="margin-top:8px;">
              <i class="fas fa-map-marker-alt"></i> Fetch by GPS
            </button>
            <small id="fullAddressGpsStatus" class="text-muted d-block mt-1"></small>
          </div>

          <label for="job_title">Job Title <span class="required">*</span></label>
          <select name="job_title" id="job_title" required disabled>
            <option value="">Select location first</option>
          </select>

          @include('partials.provider-registration-service-fields')

          <label for="shift">12/24 hr / One-time <span class="required">*</span></label>
          <select name="shift" id="shift" required>
            <option value="">Select option</option>
            <option value="12">12 Hours</option>
            <option value="24">24 Hours</option>
            <option value="both">Both</option>
            <option value="onetime">One-time</option>
          </select>
          
          <h5 style="margin-top: 20px; margin-bottom: 10px; color: #666;" data-i18n="section.documents">Documents</h5>
          
          <label for="aadhar_card">Aadhar Card <span class="required">*</span> (Image/PDF)</label>
          <input type="file" name="aadhar_card" id="aadhar_card" accept="image/*,.pdf" required>
          
          <label for="pan_card">PAN Card <span class="required">*</span> (Image/PDF)</label>
          <input type="file" name="pan_card" id="pan_card" accept="image/*,.pdf" required>
          
          <label for="qualification_certificate">Qualification Certificate (Optional) (Image/PDF)</label>
          <input type="file" name="qualification_certificate" id="qualification_certificate" accept="image/*,.pdf">
          
          <h5 style="margin-top: 20px; margin-bottom: 10px; color: #666;" data-i18n="section.bank">Bank Details</h5>

          <input type="hidden" name="bank_payment_method" id="bank_payment_method" value="account">
          <div class="bank-pay-method-row" role="radiogroup" aria-label="Payment method">
            <label class="bank-pay-method-card is-active" id="bankPayMethodAccountCard">
              <input type="radio" name="bank_payment_method_radio" value="account" checked>
              <span class="bank-pay-method-title">Account details</span>
              <span class="bank-pay-method-hint">Bank account + cancelled cheque</span>
            </label>
            <label class="bank-pay-method-card" id="bankPayMethodUpiCard">
              <input type="radio" name="bank_payment_method_radio" value="upi">
              <span class="bank-pay-method-title">UPI</span>
              <span class="bank-pay-method-hint">UPI ID only</span>
            </label>
          </div>

          <div id="freelancerBankAccountFields">
            <label for="account_name">Account Name <span class="required">*</span></label>
            <input type="text" name="account_name" id="account_name" required>
            
            <label for="account_number">Account Number <span class="required">*</span></label>
            <input type="text" name="account_number" id="account_number" required>
            
            <label for="ifsc_code">IFSC Code <span class="required">*</span></label>
            <input type="text" name="ifsc_code" id="ifsc_code" required>
            
            <label for="bank_document">Bank Document (Cancelled Cheque) <span class="required">*</span> (Image/PDF)</label>
            <input type="file" name="bank_document" id="bank_document" accept="image/*,.pdf" required>
          </div>

          <div id="freelancerBankUpiFields" style="display:none;">
            <label for="upi_id">UPI ID <span class="required">*</span></label>
            <input type="text" name="upi_id" id="upi_id" placeholder="example@upi">
          </div>

          <h5 style="margin-top: 24px; margin-bottom: 12px; color: #222; font-weight: 700;" data-i18n="section.declarations">Declarations</h5>
          <div class="register-declaration-list">
            <label class="register-declaration-item" id="freelancerDeclarationNurseWrap" style="display: none;">
              <input type="checkbox" name="declaration_details_accurate" id="declaration_details_accurate" value="1">
              <span>
                <span class="register-declaration-heading">1. My details and documents are accurate</span>
                <span class="register-declaration-text">My name matches my Aadhaar and PAN card records. All documents uploaded (Aadhaar, PAN) are genuine and belong to me. The experience, job role, and location I have provided are accurate. My nursing qualification and State Nursing Council registration are valid and active.</span>
              </span>
            </label>
            <label class="register-declaration-item">
              <input type="checkbox" name="declaration_understands_platform" id="declaration_understands_platform" value="1" required>
              <span>
                <span class="register-declaration-heading">2. I understand how Carelix works</span>
                <span class="register-declaration-text">My profile will only go live after Carelix completes its verification. My rate per shift / day will be confirmed separately by Carelix before my first assignment is allocated to me. I consent to Carelix conducting background verification including identity and address checks.</span>
              </span>
            </label>
            <label class="register-declaration-item">
              <input type="checkbox" name="declaration_agrees_terms" id="declaration_agrees_terms" value="1" required>
              <span>
                <span class="register-declaration-heading">3. I agree to Carelix's terms</span>
                <span class="register-declaration-text">I have read and agree to the Carelix <a href="https://carelixhealthcare.com/terms-freelancer.php" class="register-legal-link" target="_blank" rel="noopener noreferrer" onclick="event.stopPropagation();">Freelancer Terms &amp; Conditions</a> and <a href="https://carelixhealthcare.com/privacy-freelancer.php" class="register-legal-link" target="_blank" rel="noopener noreferrer" onclick="event.stopPropagation();">Freelancer Privacy Policy</a>. I consent to Carelix collecting, storing, and processing my personal and KYC data for verification and platform operations. I authorise Carelix and its representatives to contact me via Call, SMS, Email or WhatsApp regarding assignments and my account.</span>
              </span>
            </label>
          </div>

        @elseif($type === 'doctor')
          <style>
            .doctor-hint {
              display: inline-flex; align-items: center; justify-content: center;
              width: 1.1rem; height: 1.1rem; margin-left: 4px;
              font-size: 0.7rem; font-weight: 700; color: #ea8a2b;
              border: 1px solid #ea8a2b; border-radius: 50%; cursor: help; vertical-align: middle;
            }
            .doctor-section {
              border: 1px solid #e8e8e8; border-radius: 12px; padding: 20px 20px 12px;
              margin-bottom: 20px; background: linear-gradient(180deg, #fff 0%, #fafafa 100%);
              box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            }
            .doctor-section h5 {
              display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
              font-size: 1.05rem; font-weight: 700; margin: 0 0 16px 0; padding-bottom: 12px;
              border-bottom: 1px solid #eee; color: #222;
            }
            .doctor-section h5 .section-step {
              display: inline-flex; align-items: center; justify-content: center;
              min-width: 28px; height: 28px; padding: 0 6px; border-radius: 8px;
              background: #ea8a2b; color: #fff; font-size: 0.85rem; font-weight: 700;
            }
            .doctor-section-sub { font-size: 0.875rem; font-weight: 500; color: #666; width: 100%; margin-top: -8px; margin-bottom: 4px; }
            .doctor-help { display: block; margin: -10px 0 16px 0; font-size: 0.875rem; color: #666; line-height: 1.45; }
            .doctor-choice-list { display: flex; flex-direction: column; gap: 10px; margin-bottom: 4px; }
            .doctor-register-form label.doctor-choice {
              display: flex; align-items: flex-start; gap: 12px; font-weight: 500;
              margin-bottom: 0; padding: 14px 16px; border: 1px solid #e0e0e0; border-radius: 10px;
              background: #fff; cursor: pointer; transition: border-color 0.15s, box-shadow 0.15s;
            }
            .doctor-register-form label.doctor-choice:hover { border-color: #ea8a2b; box-shadow: 0 0 0 1px rgba(234,138,43,0.15); }
            .doctor-register-form label.doctor-choice input[type="checkbox"] {
              width: auto; margin: 3px 0 0 0; flex-shrink: 0; accent-color: #ea8a2b;
            }
            .doctor-register-form label.doctor-choice span { line-height: 1.4; color: #333; }
            .doctor-declaration-heading {
              display: block;
              font-weight: 700;
              color: #1a2340;
              margin-bottom: 6px;
              line-height: 1.35;
            }
            .doctor-declaration-text {
              display: block;
              font-weight: 400;
              font-size: 0.9rem;
              color: #555;
              line-height: 1.55;
            }
            .doctor-declaration-text a {
              color: #ea8a2b;
              font-weight: 600;
              text-decoration: underline;
            }
            .doctor-declaration-text a:hover { color: #c96f1a; }
            .doctor-empty-services {
              padding: 12px 14px; border-radius: 8px; background: #fff5f5; border: 1px solid #f5c6cb;
              color: #721c24; font-size: 0.9rem; line-height: 1.45; margin-top: 8px;
            }
            .doctor-mode-list {
              display: flex;
              flex-direction: column;
              gap: 10px;
            }
            .doctor-mode-item .doctor-mode-choice {
              margin-bottom: 0 !important;
            }
            .doctor-mode-item.is-expanded .doctor-mode-choice {
              border-bottom-left-radius: 0;
              border-bottom-right-radius: 0;
              border-bottom-color: transparent;
              box-shadow: none;
            }
            .mode-block {
              display: none;
              margin: 0;
              padding: 14px 16px 16px;
              background: linear-gradient(180deg, #fffbf6 0%, #fff 100%);
              border: 2px solid #ea8a2b;
              border-top: none;
              border-radius: 0 0 10px 10px;
            }
            .mode-block.visible { display: block; }
            .mode-block label:first-child { margin-top: 0; }
            .doctor-geo-btn {
              display: inline-block; margin-top: 8px; margin-bottom: 4px;
              padding: 8px 14px; font-size: 0.9rem; font-weight: 600;
              color: #ea8a2b; background: #fff; border: 2px solid #ea8a2b; border-radius: 8px;
              cursor: pointer; transition: background 0.15s, color 0.15s;
            }
            .doctor-geo-btn:hover { background: #ea8a2b; color: #fff; }
            .doctor-mobile-verify-row {
              display: grid;
              grid-template-columns: minmax(0, 1fr) auto;
              gap: 10px;
              align-items: center;
              width: 100%;
              margin-bottom: 18px;
            }
            .doctor-mobile-verify-row input[type="tel"],
            .doctor-mobile-verify-row input[type="text"] {
              width: 100% !important;
              max-width: 100%;
              margin-bottom: 0 !important;
              min-width: 0;
              box-sizing: border-box;
            }
            .register-form.doctor-register-form button.doctor-otp-btn {
              width: auto !important;
              min-width: 7.5rem;
              margin-bottom: 0 !important;
              padding: 10px 16px;
              font-size: 0.9rem;
              font-weight: 700;
              line-height: 1.2;
              color: #ea8a2b;
              background: #fff;
              border: 2px solid #ea8a2b;
              border-radius: 8px;
              cursor: pointer;
              white-space: nowrap;
              transition: background 0.15s, color 0.15s;
              flex-shrink: 0;
            }
            .register-form.doctor-register-form button.doctor-otp-btn:hover:not(:disabled) {
              background: #ea8a2b;
              color: #fff;
            }
            .register-form.doctor-register-form button.doctor-otp-btn:disabled {
              opacity: 0.55;
              cursor: not-allowed;
              background: #fff;
              color: #ea8a2b;
            }
            @media (max-width: 420px) {
              .doctor-mobile-verify-row {
                grid-template-columns: 1fr;
              }
              .register-form.doctor-register-form button.doctor-otp-btn {
                width: 100% !important;
              }
            }
            .register-form.doctor-register-form button.doctor-otp-btn.doctor-otp-btn--verify {
              color: #15803d;
              border-color: #15803d;
            }
            .register-form.doctor-register-form button.doctor-otp-btn.doctor-otp-btn--verify:hover:not(:disabled) {
              background: #15803d;
              color: #fff;
            }
            .doctor-mobile-otp-wrap { margin-bottom: 14px; }
            .doctor-mobile-verified-badge {
              display: block;
              font-size: 0.85rem;
              color: #15803d;
              font-weight: 700;
              margin-top: 6px;
            }
            .doctor-mobile-verified-badge i { margin-right: 4px; }
            input.doctor-mobile-verified-lock {
              background: #f0fdf4 !important;
              border-color: #86efac !important;
            }
            .doctor-address-field { position: relative; margin-bottom: 6px; }
            .doctor-address-suggestions {
              position: absolute; left: 0; right: 0; top: calc(100% + 4px); z-index: 20;
              margin: 0; padding: 0.25rem 0; list-style: none; background: #fff;
              border: 1px solid #cbd5e1; border-radius: 8px; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12);
              max-height: 220px; overflow-y: auto;
            }
            .doctor-address-suggestions[hidden] { display: none !important; }
            .doctor-address-suggestions__item {
              padding: 0.55rem 0.75rem; font-size: 0.9rem; line-height: 1.35; color: #334155; cursor: pointer;
            }
            .doctor-address-suggestions__item:hover,
            .doctor-address-suggestions__item.is-active { background: #fff7ed; color: #9a3412; }
            .doctor-address-set-ok { display: block; font-size: 0.82rem; color: #15803d; font-weight: 600; margin: 4px 0 8px; }
            .doctor-address-set-ok[hidden] { display: none !important; }
            .doctor-address-set-missing { display: block; font-size: 0.82rem; color: #b45309; margin: 4px 0 8px; }
            .doctor-declaration-note { display: block; margin-top: 12px; padding-top: 12px; border-top: 1px solid #eee; font-size: 0.875rem; color: #666; }
            .doctor-lang-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; margin-bottom: 14px; }
            @media (min-width: 520px) { .doctor-lang-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
            .doctor-lang-item { margin-bottom: 0 !important; }
            .doctor-profile-block {
              border: 1px solid #e8e4df;
              border-radius: 12px;
              padding: 1rem 1.1rem;
              margin-bottom: 1.25rem;
              background: #fff;
            }
            .doctor-profile-block__header {
              display: flex;
              align-items: center;
              justify-content: space-between;
              gap: 0.75rem;
              flex-wrap: wrap;
              margin-bottom: 0.85rem;
              padding-bottom: 0.65rem;
              border-bottom: 1px solid #f0ebe3;
            }
            .doctor-profile-block__header h6 {
              margin: 0;
              font-size: 0.95rem;
              font-weight: 700;
              color: #1a2340;
              display: flex;
              align-items: center;
              gap: 0.45rem;
            }
            .doctor-profile-block__header h6 i {
              color: #ea8a2b;
              font-size: 0.9rem;
            }
            .doctor-profile-block__hint {
              font-size: 0.78rem;
              color: #64748b;
              font-weight: 500;
            }
            .doctor-repeater {
              display: flex;
              flex-direction: column;
              gap: 12px;
              margin-bottom: 0.75rem;
            }
            .doctor-profile-card {
              border: 1px solid #e5e7eb;
              border-radius: 12px;
              background: linear-gradient(180deg, #ffffff 0%, #faf9f7 100%);
              box-shadow: 0 1px 4px rgba(15, 23, 42, 0.05);
              overflow: hidden;
            }
            .doctor-profile-card__head {
              display: flex;
              align-items: center;
              justify-content: space-between;
              gap: 0.75rem;
              padding: 0.65rem 1rem;
              background: #f8fafc;
              border-bottom: 1px solid #e5e7eb;
            }
            .doctor-profile-card__title {
              display: flex;
              align-items: center;
              gap: 0.5rem;
              font-size: 0.88rem;
              font-weight: 700;
              color: #334155;
            }
            .doctor-profile-card__badge {
              display: inline-flex;
              align-items: center;
              justify-content: center;
              min-width: 1.35rem;
              height: 1.35rem;
              padding: 0 0.35rem;
              border-radius: 6px;
              background: #ea8a2b;
              color: #fff;
              font-size: 0.72rem;
              font-weight: 700;
            }
            .doctor-profile-card__title > i {
              color: #ea8a2b;
              font-size: 0.85rem;
            }
            .register-form.doctor-register-form button.doctor-profile-card__remove {
              width: auto;
              margin: 0;
              padding: 0.3rem 0.65rem;
              font-size: 0.78rem;
              line-height: 1.2;
              font-weight: 600;
              background: #fff;
              color: #b91c1c;
              border: 1px solid #fecaca;
              border-radius: 6px;
              box-shadow: none;
            }
            .register-form.doctor-register-form button.doctor-profile-card__remove:hover {
              background: #fef2f2;
              border-color: #f87171;
              color: #991b1b;
            }
            .doctor-profile-card__body {
              padding: 1rem;
            }
            .doctor-profile-grid {
              display: grid;
              grid-template-columns: 1fr;
              gap: 0.75rem;
            }
            @media (min-width: 520px) {
              .doctor-profile-grid--2 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
              }
              .doctor-profile-field--full {
                grid-column: 1 / -1;
              }
            }
            .doctor-profile-field label {
              display: block;
              margin: 0 0 0.35rem;
              font-size: 0.8rem;
              font-weight: 600;
              color: #475569;
            }
            .doctor-profile-field label .optional {
              font-weight: 500;
              color: #94a3b8;
            }
            .doctor-profile-card input,
            .doctor-profile-card textarea {
              margin-bottom: 0 !important;
              border-radius: 8px;
              border-color: #d1d5db;
              font-size: 0.92rem;
            }
            .doctor-profile-card input:focus,
            .doctor-profile-card textarea:focus {
              border-color: #ea8a2b;
              outline: none;
              box-shadow: 0 0 0 3px rgba(234, 138, 43, 0.15);
            }
            .doctor-profile-card textarea {
              min-height: 72px;
              resize: vertical;
            }
            .register-form.doctor-register-form button.doctor-profile-add-btn {
              width: 100%;
              margin: 0;
              padding: 0.65rem 1rem;
              font-size: 0.88rem;
              font-weight: 600;
              background: #fff;
              color: #ea8a2b;
              border: 2px dashed #ea8a2b;
              border-radius: 10px;
              box-shadow: none;
            }
            .register-form.doctor-register-form button.doctor-profile-add-btn:hover {
              background: #fff7ed;
              color: #c96f1a;
              border-style: solid;
            }
            .register-form.doctor-register-form button.doctor-profile-add-btn i {
              margin-right: 0.35rem;
            }
            .doctor-spec-wrap { margin-bottom: 12px; }
            .doctor-tags-grid {
              display: grid;
              grid-template-columns: repeat(3, minmax(0, 1fr));
              gap: 12px;
            }
            @media (max-width: 520px) {
              .doctor-tags-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            }
            @media (max-width: 380px) {
              .doctor-tags-grid { grid-template-columns: 1fr; }
            }
            .doctor-tag-pill {
              display: block;
              margin: 0;
              cursor: pointer;
              user-select: none;
              position: relative;
            }
            .doctor-tag-pill input.spec-cb,
            .doctor-tag-pill input.sub-spec-cb {
              position: absolute;
              opacity: 0;
              width: 1px;
              height: 1px;
              padding: 0;
              margin: -1px;
              overflow: hidden;
              clip: rect(0, 0, 0, 0);
              white-space: nowrap;
              border: 0;
            }
            .doctor-tag-pill:focus-within .doctor-tag-pill-inner {
              outline: 2px solid #ea8a2b;
              outline-offset: 2px;
            }
            .doctor-tag-pill-inner {
              display: flex;
              align-items: center;
              justify-content: space-between;
              gap: 10px;
              min-height: 44px;
              height: 100%;
              padding: 10px 14px;
              border-radius: 12px;
              border: 2px solid #ea8a2b;
              background: #fff;
              color: #333;
              font-size: 0.875rem;
              font-weight: 600;
              line-height: 1.35;
              transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
            }
            .doctor-tag-pill:hover .doctor-tag-pill-inner {
              border-color: #d97a22;
              box-shadow: 0 2px 10px rgba(234, 138, 43, 0.18);
            }
            .doctor-tag-pill input.spec-cb:checked + .doctor-tag-pill-inner,
            .doctor-tag-pill input.sub-spec-cb:checked + .doctor-tag-pill-inner {
              border-color: #e8892a;
              background: linear-gradient(135deg, #fe992e 0%, #e8892a 100%);
              color: #fff;
              box-shadow: 0 4px 14px rgba(234, 138, 43, 0.35);
            }
            .doctor-tag-text {
              flex: 1;
              min-width: 0;
              white-space: normal;
              word-break: break-word;
              overflow-wrap: anywhere;
              overflow: visible;
              text-overflow: unset;
            }
            .doctor-tag-icon {
              flex-shrink: 0;
              width: 1.15rem;
              height: 1.15rem;
              display: inline-flex;
              align-items: center;
              justify-content: center;
              border-radius: 50%;
              font-size: 0.7rem;
              font-weight: 700;
              line-height: 1;
              background: rgba(234, 138, 43, 0.15);
              color: #ea8a2b;
            }
            .doctor-tag-pill input.spec-cb:checked + .doctor-tag-pill-inner .doctor-tag-icon,
            .doctor-tag-pill input.sub-spec-cb:checked + .doctor-tag-pill-inner .doctor-tag-icon {
              background: rgba(255, 255, 255, 0.25);
              color: #fff;
            }
            .doctor-tag-icon::before { content: '+'; }
            .doctor-tag-pill input.spec-cb:checked + .doctor-tag-pill-inner .doctor-tag-icon::before,
            .doctor-tag-pill input.sub-spec-cb:checked + .doctor-tag-pill-inner .doctor-tag-icon::before { content: '✓'; }
            .doctor-sub-service-block {
              border: 1px solid #e8e4df;
              border-radius: 12px;
              padding: 0.85rem 1rem;
              margin-bottom: 0.75rem;
              background: #faf9f7;
            }
            .doctor-sub-service-block-head {
              display: flex;
              justify-content: space-between;
              align-items: center;
              gap: 0.75rem;
              margin-bottom: 0.65rem;
              flex-wrap: wrap;
            }
            .doctor-sub-service-block-head strong {
              color: #1a2340;
              font-size: 0.95rem;
              flex: 1;
              min-width: 0;
            }
            .doctor-sub-service-block .doctor-help {
              margin-bottom: 0.65rem;
              font-size: 0.82rem;
              color: #64748b;
            }
            .doctor-sub-service-block .doctor-tags-grid {
              margin-top: 0.15rem;
            }
            .doctor-sub-summary {
              border: 1px solid #e0ddd6;
              border-radius: 12px;
              padding: 0.85rem 1rem;
              background: #fff;
              margin-top: 0.75rem;
            }
            .doctor-sub-summary h6 {
              font-size: 0.88rem;
              font-weight: 700;
              color: #1a2340;
              margin: 0 0 0.65rem;
            }
            .doctor-sub-summary-item {
              border-bottom: 1px dashed #e8e4df;
              padding: 0.5rem 0;
            }
            .doctor-sub-summary-item:last-child { border-bottom: none; padding-bottom: 0; }
            .doctor-sub-summary-item .sub-name {
              font-weight: 600;
              color: #333;
              font-size: 0.88rem;
              display: block;
              margin-bottom: 0.35rem;
            }
            .register-form.doctor-register-form button.doctor-sub-remove-btn {
              width: auto;
              flex-shrink: 0;
              margin: 0;
              padding: 0.35rem 0.7rem;
              font-size: 0.78rem;
              line-height: 1.2;
              font-weight: 600;
              background: #fff;
              color: #b91c1c;
              border: 1px solid #fecaca;
              border-radius: 6px;
              box-shadow: none;
            }
            .register-form.doctor-register-form button.doctor-sub-remove-btn:hover {
              background: #fef2f2;
              border-color: #f87171;
              color: #991b1b;
            }
            .doctor-summary-tag-chip {
              display: inline-block;
              padding: 0.2rem 0.55rem;
              margin: 0 0.25rem 0.25rem 0;
              font-size: 0.75rem;
              font-weight: 600;
              background: #fff7ed;
              border: 1px solid #fed7aa;
              border-radius: 999px;
              color: #9a3412;
            }
            .doctor-pricing-wrap {
              border: 1px solid #e8e4df;
              border-radius: 12px;
              padding: 0.75rem 0.85rem;
              background: #fff;
              margin-bottom: 14px;
            }
            .doctor-pricing-hint { font-size: 0.82rem; color: #64748b; margin: 0 0 0.6rem; }
            .doctor-pricing-hint--warn { color: #b45309; font-weight: 600; }
            .doctor-pricing-table {
              width: 100%;
              border-collapse: collapse;
              font-size: 0.85rem;
              margin-top: 0.35rem;
            }
            .doctor-pricing-table th,
            .doctor-pricing-table td {
              border: 1px solid #e5e7eb;
              padding: 0.55rem 0.65rem;
              vertical-align: middle;
            }
            .doctor-pricing-table th {
              background: #f8fafc;
              font-weight: 700;
              color: #334155;
              text-align: left;
            }
            .doctor-pricing-table td.doctor-pricing-locked {
              background: #f0fdf4;
              color: #166534;
              font-weight: 600;
            }
            .doctor-pricing-table input[type="number"] {
              margin: 0;
              max-width: 140px;
              padding: 0.35rem 0.5rem;
            }
            .doctor-pricing-badge {
              display: inline-block;
              font-size: 0.72rem;
              font-weight: 700;
              padding: 0.15rem 0.45rem;
              border-radius: 999px;
              background: #dcfce7;
              color: #166534;
            }
            .doctor-pricing-badge--open {
              background: #fff7ed;
              color: #9a3412;
            }
          </style>

          <div class="doctor-section">
            <h5><span class="section-step">1</span> <span data-i18n="doctor.section.basic">Basic details</span></h5>
            <label for="name">Full name (as on Aadhaar / PAN / bank) <span class="required">*</span></label>
            <input type="text" name="name" id="name" required maxlength="255" data-i18n-placeholder="placeholder.full_name" placeholder="Enter your full legal name" autocomplete="name">

            <label for="contact_no">Mobile number <span class="required">*</span></label>
            <div class="doctor-mobile-verify-row">
              <input type="tel" name="contact_no" id="contact_no" maxlength="10" pattern="[0-9]{10}" required data-i18n-placeholder="placeholder.mobile" placeholder="10-digit mobile number" inputmode="numeric" autocomplete="tel">
              <button type="button" class="doctor-otp-btn" id="doctorMobileSendOtpBtn" data-i18n="otp.send">Send OTP</button>
            </div>
            <input type="hidden" id="doctor_mobile_verified_flag" name="mobile_verified" value="0">
            <div id="doctor_mobile_otp_wrap" class="doctor-mobile-otp-wrap" style="display:none;">
              <label for="doctor_mobile_otp">Enter OTP</label>
              <div class="doctor-mobile-verify-row">
                <input type="text" id="doctor_mobile_otp" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" placeholder="6-digit OTP" autocomplete="one-time-code">
                <button type="button" class="doctor-otp-btn doctor-otp-btn--verify" id="doctorMobileVerifyOtpBtn" data-i18n="otp.verify">Verify</button>
              </div>
              <span class="doctor-mobile-verified-badge" id="doctor_mobile_verified_badge" style="display:none;"><i class="fas fa-check-circle"></i> <span data-i18n="otp.verified_short">Mobile verified</span></span>
            </div>

            <label for="doctor_email">Email (Gmail / email ID) <span class="required">*</span></label>
            <input type="email" name="email" id="doctor_email" required maxlength="255" data-i18n-placeholder="placeholder.email" placeholder="yourname@gmail.com" autocomplete="email" value="{{ old('email') }}">

            <label for="doctor_gender">Gender <span class="required">*</span></label>
            <select name="gender" id="doctor_gender" required>
              <option value="">Select gender</option>
              <option value="male" @selected(old('gender') === 'male')>Male</option>
              <option value="female" @selected(old('gender') === 'female')>Female</option>
              <option value="other" @selected(old('gender') === 'other')>Other</option>
            </select>

            <label for="selfie">Selfie <span class="required">*</span></label>
            <input type="file" name="selfie" id="selfie" accept="image/*" required>
            <span class="doctor-help" data-i18n="doctor.selfie.help">Use a clear photo of your face. On phone you can choose gallery or camera from the file picker.</span>
            <div id="selfie-upload-notice" class="alert alert-info py-2 px-3 mt-2 mb-0 small" style="display:none;" role="status" data-i18n-html="doctor.selfie.upload_notice">
              <i class="fas fa-clock"></i> Photo uploaded. <strong>Please wait for approval by admin</strong> — your Carelix profile photo will appear on the website after admin generates and approves your branded coat image.
            </div>
          </div>

          <div class="doctor-section">
            <h5><span class="section-step">2</span> <span data-i18n="doctor.section.city_service">City &amp; consultation service</span></h5>
            <p class="doctor-section-sub" data-i18n="doctor.section.city_service_sub">First choose your city, then your consultation service. If the service has sub-services, select them and choose tags for each sub-service.</p>

            <div class="doctor-city-field">
              <label for="doctor_city">City <span class="required">*</span></label>
              <select name="city" id="doctor_city" class="doctor-city-select location-select" required @if($locations->isEmpty()) disabled @endif>
                <option value="" data-i18n="doctor.city.placeholder">Search or select city</option>
                @foreach($locations as $location)
                  <option value="{{ $location->name }}"
                          data-location-id="{{ $location->id }}"
                          data-state="{{ $location->state ?? '' }}"
                          data-city-name="{{ $location->name }}"
                          @selected(old('city') === $location->name)>{{ $location->name }}</option>
                @endforeach
              </select>
              @if($locations->isEmpty())
                <span class="doctor-help" style="color:#721c24;" data-i18n-html="doctor.city.no_cities">No cities available yet. Admin must add locations under <strong>Locations</strong> in CRM first.</span>
              @else
                <span class="doctor-help" data-i18n="doctor.city.help">Type to search cities added by admin under Locations.</span>
              @endif
            </div>

            <label for="doctor_consultation_service" class="mt-2">Consultation service <span class="required">*</span></label>
            <select name="job_title" id="doctor_consultation_service" required @if($doctor_consultation_services->isEmpty()) disabled @endif>
              <option value="" data-i18n="doctor.consultation.placeholder">Select consultation service</option>
              @foreach($doctor_consultation_services as $svc)
                <option value="{{ $svc->name }}" @selected(old('job_title') === $svc->name)>{{ $svc->name }}</option>
              @endforeach
            </select>
            @php
              $doctorConsultationServicesJson = $doctor_consultation_services->map(function ($svc) {
                  return [
                      'id' => (int) $svc->id,
                      'name' => (string) $svc->name,
                      'tags' => array_values(is_array($svc->specialization_options) ? $svc->specialization_options : []),
                      'sub_services' => $svc->subServices->map(fn ($sub) => [
                          'id' => (int) $sub->id,
                          'name' => (string) $sub->name,
                          'tags' => array_values(is_array($sub->specialization_options) ? $sub->specialization_options : []),
                      ])->values()->all(),
                  ];
              })->values();
            @endphp
            <script type="application/json" id="doctor_consultation_services_data">@json($doctorConsultationServicesJson)</script>
            @if($doctor_consultation_services->isEmpty())
              <div class="doctor-empty-services" data-i18n-html="doctor.consultation.empty">No consultation services are available yet. Please ask your administrator to add options under <strong>Doctor consultation services</strong> in the CRM, then refresh this page.</div>
            @endif

            <div id="sub_service_picker_wrap" class="mt-3" style="display:none;">
              <label for="sub_service_add_select"><span data-i18n="doctor.sub_service.label">Sub-service</span> <span class="required">*</span></label>
              <select id="sub_service_add_select" class="form-control">
                <option value="" data-i18n="doctor.sub_service.placeholder">Select sub-service to add</option>
              </select>
              <span class="doctor-help" data-i18n="doctor.sub_service.help">You can select more than one sub-service. Choose tags for each sub-service separately.</span>
            </div>

            <div id="parent_tags_wrap" class="mt-3" style="display:none;">
              <label><span data-i18n="doctor.tags.label">Tags for this service</span> <span class="required">*</span></label>
              <div id="spec_wrap" class="doctor-spec-wrap"></div>
            </div>

            <div id="sub_service_tags_stack" class="mt-2"></div>

            <div id="sub_service_summary_wrap" class="doctor-sub-summary" style="display:none;">
              <h6><i class="fas fa-list-check"></i> <span data-i18n="doctor.sub_summary.title">Selected sub-services &amp; tags</span></h6>
              <div id="sub_service_summary_list"></div>
            </div>

            <input type="hidden" name="consultation_sub_services" id="consultation_sub_services_json" value="[]">
            <input type="hidden" name="consultation_pricing" id="consultation_pricing_json" value="[]">
          </div>

          <div class="doctor-section" id="doctor_profile_section">
            <h5><span class="section-step">3</span> <span data-i18n="doctor.section.profile">Languages, about &amp; clinical profile</span></h5>
            <p class="doctor-section-sub" data-i18n="doctor.section.profile_sub">Help patients understand how you practise. Add your most recent education and experience first; we will sort them for display.</p>

            <label><span data-i18n="label.fluent_languages">Languages you are fluent in</span> <span class="required">*</span></label>
            <span class="doctor-help" data-i18n="doctor.fluent.note">{{ config('doctor_registration.fluent_languages_note') }}</span>
            <div class="doctor-lang-grid">
              @foreach(config('doctor_registration.fluent_languages', []) as $key => $label)
                <label class="doctor-choice doctor-lang-item"><input type="checkbox" name="fluent_languages[]" value="{{ $key }}"><span data-i18n="doctor.fluent.{{ $key }}">{{ $label }}</span></label>
              @endforeach
            </div>

            <label for="about_text">About you <span class="required">*</span></label>
            <textarea name="about_text" id="about_text" rows="5" maxlength="8000" required data-i18n-placeholder="doctor.about.placeholder" placeholder="First write a few points about yourself — qualification, experience, special interests, city, languages (at least 20 characters). Then click &quot;Generate with AI&quot;; AI will draft your About section from these details. You can edit it before submitting."></textarea>
            <button type="button" class="doctor-geo-btn" id="btn_generate_about" data-i18n="btn.generate_ai">Generate with AI</button>
            <span class="doctor-help" id="about_ai_hint" data-i18n="doctor.about.ai_hint">Powered by Google Gemini. Enter your details in the box above first, then click generate — the AI-written text will appear here. Please review it before you submit.</span>

            <div class="doctor-profile-block">
              <div class="doctor-profile-block__header">
                <h6><i class="fas fa-graduation-cap"></i> <span data-i18n="doctor.edu.title">Education</span> <span class="required">*</span></h6>
                <span class="doctor-profile-block__hint" data-i18n="doctor.edu.hint_recent">Add most recent first</span>
              </div>
              <div id="edu_rows" class="doctor-repeater"></div>
              <button type="button" class="doctor-profile-add-btn doctor-repeater-add" data-add="edu">
                <i class="fas fa-plus"></i> <span data-i18n="doctor.edu.add_btn">Add another education</span>
              </button>
            </div>

            <div class="doctor-profile-block">
              <div class="doctor-profile-block__header">
                <h6><i class="fas fa-briefcase-medical"></i> <span data-i18n="doctor.exp.title">Experience</span> <span class="required">*</span></h6>
                <span class="doctor-profile-block__hint" data-i18n="doctor.exp.hint_recent">Add most recent first</span>
              </div>
              <div id="exp_rows" class="doctor-repeater"></div>
              <button type="button" class="doctor-profile-add-btn doctor-repeater-add" data-add="exp">
                <i class="fas fa-plus"></i> <span data-i18n="doctor.exp.add_btn">Add another experience</span>
              </button>
            </div>

            <input type="hidden" name="education_history" id="education_history_json" value="[]">
            <input type="hidden" name="experience_history" id="experience_history_json" value="[]">
            <input type="hidden" name="specializations" id="specializations_json" value="[]">
          </div>

          <div class="doctor-section">
            <h5><span class="section-step">4</span> <span data-i18n="doctor.section.consultation_mode">Consultation mode</span> <span class="required">*</span></h5>
            <p class="doctor-section-sub"></p>
            <div class="doctor-mode-list" role="group" aria-label="Consultation modes">
              <div class="doctor-mode-item" data-mode="online">
                <label class="doctor-choice doctor-mode-choice">
                  <input type="checkbox" name="consultation_modes[]" value="online" class="mode-cb">
                  <span data-i18n-html="doctor.mode.online"><strong>Online</strong> — Video / phone consultation</span>
                </label>
                <div id="block_online" class="mode-block">
                  <label class="d-block"><span data-i18n="doctor.mode.online_charges">Online consultation charges (₹)</span> <span class="required">*</span></label>
                  <div id="pricing_online_wrap" class="doctor-pricing-wrap" data-mode="online"></div>
                  <input type="number" name="online_charges" id="online_charges" min="0" step="1" class="mode-input-online" style="display:none;" aria-hidden="true" tabindex="-1">
                </div>
              </div>

              <div class="doctor-mode-item" data-mode="home_visit">
                <label class="doctor-choice doctor-mode-choice">
                  <input type="checkbox" name="consultation_modes[]" value="home_visit" class="mode-cb">
                  <span data-i18n-html="doctor.mode.home_visit"><strong>Home visit</strong> — You visit the patient</span>
                </label>
                <div id="block_home_visit" class="mode-block">
              <label class="d-block"><span data-i18n="doctor.mode.home_charges">Home visit charges (₹)</span> <span class="required">*</span>
                <span class="doctor-hint" title="Fee you charge for visiting the patient at home.">?</span>
              </label>
              <div id="pricing_home_visit_wrap" class="doctor-pricing-wrap" data-mode="home_visit"></div>
              <input type="number" name="home_visit_charges" id="home_visit_charges" min="0" step="1" class="mode-input-home" style="display:none;" aria-hidden="true" tabindex="-1">
              <label for="coverage_radius_km"><span data-i18n="doctor.mode.coverage">Coverage radius (km)</span> <span class="required">*</span>
                <span class="doctor-hint" title="Maximum distance you travel from your base location.">?</span>
              </label>
              <input type="number" name="coverage_radius_km" id="coverage_radius_km" min="0.1" step="0.1" class="mode-input-home" data-i18n-placeholder="doctor.mode.coverage_ph" placeholder="e.g. 10">
              <label for="base_location_address"><span data-i18n="doctor.mode.base_location">Base location</span> <span class="required">*</span></label>
              <div class="doctor-address-field">
                <textarea name="base_location_address" id="base_location_address" rows="2" class="mode-input-home" data-i18n-placeholder="doctor.mode.base_ph" placeholder="Type address — pick a suggestion (uses Google Maps via Carelix)" autocomplete="off" autocorrect="off" spellcheck="false"></textarea>
                <ul class="doctor-address-suggestions" id="base_location_suggestions" hidden role="listbox"></ul>
              </div>
              <input type="hidden" name="base_location_lat" id="base_location_lat">
              <input type="hidden" name="base_location_lng" id="base_location_lng">
              <span class="doctor-address-set-ok" id="base_location_set_ok" hidden><i class="fas fa-check-circle"></i> <span data-i18n="doctor.mode.base_ok">Location set from address search</span></span>
              <span class="doctor-address-set-missing" id="base_location_set_missing" data-i18n="doctor.mode.base_missing">Select your city above, type address, then pick a suggestion to set location.</span>
              <span class="doctor-help" data-i18n="doctor.mode.home_help">Home visit patients are matched using this point and your coverage radius.</span>
                </div>
              </div>

              <div class="doctor-mode-item" data-mode="clinic_visit">
                <label class="doctor-choice doctor-mode-choice">
                  <input type="checkbox" name="consultation_modes[]" value="clinic_visit" class="mode-cb">
                  <span data-i18n-html="doctor.mode.clinic_visit"><strong>Clinic visit</strong> — Patient visits your clinic</span>
                </label>
                <div id="block_clinic_visit" class="mode-block">
              <label class="d-block"><span data-i18n="doctor.mode.clinic_charges">Clinic consultation charges (₹)</span> <span class="required">*</span>
                <span class="doctor-hint" title="Fee for consultation at your clinic.">?</span>
              </label>
              <div id="pricing_clinic_visit_wrap" class="doctor-pricing-wrap" data-mode="clinic_visit"></div>
              <input type="number" name="clinic_consultation_charges" id="clinic_consultation_charges" min="0" step="1" class="mode-input-clinic" style="display:none;" aria-hidden="true" tabindex="-1">
              <label for="clinic_name"><span data-i18n="doctor.mode.clinic_name">Clinic name</span> <span class="required">*</span></label>
              <input type="text" name="clinic_name" id="clinic_name" maxlength="255" class="mode-input-clinic" data-i18n-placeholder="doctor.mode.clinic_name_ph" placeholder="Name as patients know it">
              <label for="clinic_address"><span data-i18n="doctor.mode.clinic_address">Clinic address</span> <span class="required">*</span></label>
              <div class="doctor-address-field">
                <textarea name="clinic_address" id="clinic_address" rows="2" class="mode-input-clinic" data-i18n-placeholder="doctor.mode.clinic_ph" placeholder="Type clinic address — pick a suggestion (uses Google Maps via Carelix)" autocomplete="off" autocorrect="off" spellcheck="false"></textarea>
                <ul class="doctor-address-suggestions" id="clinic_address_suggestions" hidden role="listbox"></ul>
              </div>
              <input type="hidden" name="clinic_lat" id="clinic_lat">
              <input type="hidden" name="clinic_lng" id="clinic_lng">
              <span class="doctor-address-set-ok" id="clinic_address_set_ok" hidden><i class="fas fa-check-circle"></i> <span data-i18n="doctor.mode.clinic_ok">Clinic location set from address search</span></span>
              <span class="doctor-address-set-missing" id="clinic_address_set_missing" data-i18n="doctor.mode.clinic_missing">Select your city above, type address, then pick a suggestion to set clinic on map.</span>
              <span class="doctor-help" data-i18n="doctor.mode.clinic_help">Patients searching nearby clinics will see you based on this location.</span>
                </div>
              </div>
            </div>
          </div>

          <div class="doctor-section">
            <h5><span class="section-step">5</span> <span data-i18n="doctor.section.address">Address details</span></h5>
            <label for="permanent_address"><span data-i18n="doctor.address.permanent">Permanent address</span> <span class="required">*</span></label>
            @if(!empty($registration_address_search_enabled))
            <div class="doctor-address-field">
              <textarea name="permanent_address" id="permanent_address" rows="3" required maxlength="2000" data-i18n-placeholder="doctor.address.permanent_ph" placeholder="Type address — pick a suggestion (uses Google Maps via Carelix)" autocomplete="off" autocorrect="off" spellcheck="false"></textarea>
              <ul class="doctor-address-suggestions" id="permanent_address_suggestions" hidden role="listbox"></ul>
            </div>
            <span class="doctor-address-set-ok" id="permanent_address_set_ok" hidden><i class="fas fa-check-circle"></i> <span data-i18n="doctor.address.permanent_ok">Address set from Google Maps</span></span>
            <span class="doctor-help" data-i18n="doctor.address.permanent_help">Select your city above, then type and pick a suggestion.</span>
            @else
            <textarea name="permanent_address" id="permanent_address" rows="3" required maxlength="2000" data-i18n-placeholder="doctor.address.permanent_ph" placeholder="House / street, area, city, state, PIN"></textarea>
            @endif
            <label class="doctor-choice" style="margin-top: 8px; margin-bottom: 12px;"><input type="checkbox" name="current_same_as_permanent" id="current_same_as_permanent" value="1"><span data-i18n="doctor.address.same">My current address is the same as permanent address</span></label>
            <div id="current_address_wrap">
              <label for="current_address"><span data-i18n="doctor.address.current">Current address</span> <span class="required">*</span></label>
              @if(!empty($registration_address_search_enabled))
              <div class="doctor-address-field">
                <textarea name="current_address" id="current_address" rows="3" maxlength="2000" data-i18n-placeholder="doctor.address.current_ph" placeholder="Type address — pick a suggestion (uses Google Maps via Carelix)" autocomplete="off" autocorrect="off" spellcheck="false"></textarea>
                <ul class="doctor-address-suggestions" id="current_address_suggestions" hidden role="listbox"></ul>
              </div>
              <span class="doctor-address-set-ok" id="current_address_set_ok" hidden><i class="fas fa-check-circle"></i> <span data-i18n="doctor.address.current_ok">Address set from Google Maps</span></span>
              <span class="doctor-help" data-i18n="doctor.address.current_help">Select your city above, then type and pick a suggestion.</span>
              @else
              <textarea name="current_address" id="current_address" rows="3" maxlength="2000" data-i18n-placeholder="doctor.address.current_ph" placeholder="Where you stay now if different from above"></textarea>
              @endif
            </div>
          </div>

          <div class="doctor-section">
            <h5><span class="section-step">6</span> <span data-i18n="doctor.section.documents">Document verification</span></h5>
            <p class="doctor-section-sub" data-i18n="doctor.documents_sub">Upload clear images or PDFs (max size as allowed by your browser).</p>
            <label for="aadhar_card"><span data-i18n="doctor.doc.aadhaar">Aadhaar</span> <span class="required">*</span></label>
            <input type="file" name="aadhar_card" id="aadhar_card" accept="image/*,.pdf" required>
            <label for="pan_card"><span data-i18n="doctor.doc.pan">PAN</span> <span class="required">*</span></label>
            <input type="file" name="pan_card" id="pan_card" accept="image/*,.pdf" required>
            <label for="qualification_certificate"><span data-i18n="doctor.doc.degree">Highest education / degree certificate</span> <span class="required">*</span>
              <span class="doctor-hint" title="Should match your consultation service (e.g. MBBS, BPT).">?</span>
            </label>
            <input type="file" name="qualification_certificate" id="qualification_certificate" accept="image/*,.pdf" required>
          </div>

          <div class="doctor-section">
            <h5><span class="section-step">7</span> <span data-i18n="doctor.section.bank">Bank account</span></h5>
            <p class="doctor-section-sub" data-i18n="doctor.bank_sub">Use an account in the same name as in section 1.</p>
            <label for="account_name"><span data-i18n="doctor.bank.holder">Account holder name</span> <span class="required">*</span></label>
            <input type="text" name="account_name" id="account_name" required maxlength="255" data-i18n-placeholder="doctor.bank.holder_ph" placeholder="Must match full name above">
            <label for="bank_name"><span data-i18n="doctor.bank.name">Bank name</span> <span class="required">*</span></label>
            <input type="text" name="bank_name" id="bank_name" required maxlength="255" data-i18n-placeholder="doctor.bank.name_ph" placeholder="e.g. State Bank of India">
            <label for="ifsc_code"><span data-i18n="label.ifsc">IFSC code</span> <span class="required">*</span></label>
            <input type="text" name="ifsc_code" id="ifsc_code" required maxlength="20" data-i18n-placeholder="doctor.bank.ifsc_ph" placeholder="11-character IFSC" style="text-transform: uppercase;">
            <label for="account_number"><span data-i18n="label.account_number">Account number</span> <span class="required">*</span></label>
            <input type="text" name="account_number" id="account_number" required maxlength="50" inputmode="numeric" autocomplete="off">
            <label for="bank_document"><span data-i18n="doctor.bank.cheque">Cancelled cheque or bank statement</span> <span class="required">*</span></label>
            <input type="file" name="bank_document" id="bank_document" accept="image/*,.pdf" required>
          </div>

          <div class="doctor-section">
            <h5><span class="section-step">8</span> <span data-i18n="doctor.section.declarations">Declarations</span></h5>
            <div class="doctor-choice-list">
              <label class="doctor-choice">
                <input type="checkbox" name="declaration_details_accurate" value="1" required>
                <span>
                  <span class="doctor-declaration-heading" data-i18n="doctor.decl.1.heading">1. My details and documents are accurate</span>
                  <span class="doctor-declaration-text" data-i18n="doctor.decl.1.text">My name matches my Aadhaar, PAN and bank records. All documents uploaded (Aadhaar, PAN, degree certificate) are genuine and belong to me. My medical registration with the State / National Medical Council is valid and active. My qualification matches the selected consultation service.</span>
                </span>
              </label>
              <label class="doctor-choice">
                <input type="checkbox" name="declaration_understands_platform" value="1" required>
                <span>
                  <span class="doctor-declaration-heading" data-i18n="doctor.decl.2.heading">2. I understand how Carelix works</span>
                  <span class="doctor-declaration-text" data-i18n="doctor.decl.2.text">My profile will only go live after Carelix completes its verification and I sign the Doctor Service Agreement sent to me separately. My consultation rate will be mutually agreed with Carelix and confirmed before I go live on the platform.</span>
                </span>
              </label>
              <label class="doctor-choice">
                <input type="checkbox" name="declaration_agrees_terms" value="1" required>
                <span>
                  <span class="doctor-declaration-heading" data-i18n="doctor.decl.3.heading">3. I agree to Carelix's terms</span>
                  <span class="doctor-declaration-text" data-i18n-html="doctor.decl.3.text">I have read and agree to the Carelix <a href="https://carelixhealthcare.com/terms-doctor.php" target="_blank" rel="noopener noreferrer">Doctor Terms &amp; Conditions</a> and <a href="https://carelixhealthcare.com/privacy-doctor.php" target="_blank" rel="noopener noreferrer">Doctor Privacy Policy</a>. I consent to Carelix collecting, storing, and processing my personal and KYC data for verification and platform operations. I authorise Carelix and its representatives to contact me via Call, SMS, Email or WhatsApp regarding my account.</span>
                </span>
              </label>
            </div>
          </div>
        @endif
        
        <button type="submit" id="submitBtn" @if($type === 'doctor' && ($doctor_consultation_services->isEmpty() || $locations->isEmpty())) disabled title="Consultation services and cities must be configured first" @endif>Create Account</button>
        @if($type === 'doctor' && $doctor_consultation_services->isEmpty())
          <p style="text-align:center;font-size:0.9rem;color:#721c24;margin:8px 0 0;" data-i18n="doctor.paused.services">Registration is paused until an admin adds consultation services.</p>
        @elseif($type === 'doctor' && $locations->isEmpty())
          <p style="text-align:center;font-size:0.9rem;color:#721c24;margin:8px 0 0;" data-i18n="doctor.paused.cities">Registration is paused until an admin adds cities under Locations.</p>
        @endif
        
        <div class="back-link">
          <a href="{{ route('register') }}"><i class="fas fa-arrow-left"></i> <span data-i18n="link.back_register">Back to Account Type Selection</span></a>
        </div>
      </form>
    </div>
  </div>
  
  <script src="{{ asset('plugins/jquery/jquery.min.js') }}"></script>
  <script src="{{ asset('plugins/toastr/toastr.min.js') }}"></script>
  <script>
    window.registrationI18nConfig = {
      translationsUrl: @json(route('register.translations', ['code' => '__CODE__'])),
      registrationType: @json($type)
    };
  </script>
  <script src="{{ asset('js/registration-i18n.js') }}"></script>
  @if(!empty($registration_address_search_enabled))
  <script>
    window.regAddressApiBase = @json(rtrim($carecrm_api_base ?? url('/api'), '/'));

    window.attachRegistrationGoogleAddressSearch = function (cfg) {
      var $input = $(cfg.input);
      var $lat = cfg.lat ? $(cfg.lat) : $();
      var $lng = cfg.lng ? $(cfg.lng) : $();
      var $list = $(cfg.suggestions);
      var $ok = cfg.okBadge ? $(cfg.okBadge) : null;
      var $missing = cfg.missingHint ? $(cfg.missingHint) : null;
      var addressOnly = !!cfg.addressOnly;
      var citySelector = cfg.citySelector || '#doctor_city, #location';
      if (!$input.length) return;

      var suggestTimer = null;
      var suggestReqId = 0;
      var suggestAbort = null;
      var resolveAbort = null;
      var activeIdx = -1;
      var suggestDebounceMs = 150;

      function selectedCity() {
        var city = '';
        $(citySelector).each(function () {
          var v = String($(this).val() || '').trim();
          if (v) { city = v; return false; }
        });
        return city;
      }

      function apiUrl(path, params) {
        var url = window.regAddressApiBase + '/' + String(path || '').replace(/^\//, '');
        if (params) {
          var qs = new URLSearchParams(params).toString();
          if (qs) url += (url.indexOf('?') === -1 ? '?' : '&') + qs;
        }
        return url;
      }

      function updateSetBadge() {
        if (addressOnly) {
          var hasAddr = String($input.val() || '').trim().length > 0;
          if ($ok) $ok.prop('hidden', !hasAddr);
          if ($missing) $missing.toggle(!hasAddr);
          return;
        }
        var has = $lat.length && $lng.length && $lat.val() && $lng.val();
        if ($ok) $ok.prop('hidden', !has);
        if ($missing) $missing.toggle(!has);
      }

      function hideList() {
        if (!$list.length) return;
        $list.attr('hidden', 'hidden').empty();
        activeIdx = -1;
      }

      function showListLoading() {
        if (!$list.length) return;
        $list.empty().append(
          $('<li class="doctor-address-suggestions__item doctor-address-suggestions__item--loading" role="status"/>').text('Searching…')
        );
        $list.removeAttr('hidden');
      }

      function applyPlace(place, quiet) {
        $input.val(place.address || '');
        if (!addressOnly && $lat.length && $lng.length) {
          $lat.val(place.lat != null ? place.lat : '');
          $lng.val(place.lng != null ? place.lng : '');
        }
        updateSetBadge();
        hideList();
        if (!quiet) {
          toastr.success(addressOnly ? 'Address set from Google Maps.' : 'Location set from address.');
        }
      }

      function selectPrediction(pred, city) {
        var description = String(pred.description || '').trim();
        if (addressOnly) {
          applyPlace({ address: description }, true);
          return;
        }
        if (description) {
          $input.val(description);
        }
        if (resolveAbort) {
          resolveAbort.abort();
        }
        resolveAbort = typeof AbortController !== 'undefined' ? new AbortController() : null;
        var resolveSignal = resolveAbort ? resolveAbort.signal : undefined;
        fetch(apiUrl('public/address-resolve', { place_id: pred.place_id, city: city }), {
          headers: { Accept: 'application/json' },
          signal: resolveSignal
        })
          .then(function (r) { return r.json(); })
          .then(function (res) {
            if (res && res.success) {
              applyPlace({ address: res.address || description, lat: res.lat, lng: res.lng }, true);
            } else {
              toastr.error((res && res.message) ? res.message : 'Could not resolve address.');
            }
          })
          .catch(function (err) {
            if (err && err.name === 'AbortError') return;
            if (description) {
              applyPlace({ address: description }, true);
            } else {
              toastr.error('Could not resolve address.');
            }
          });
      }

      function fetchSuggestions(q) {
        var city = selectedCity();
        if (!city) {
          toastr.warning('Please select your city / location first.');
          return;
        }
        if (String(q).trim().length < 3) {
          hideList();
          return;
        }
        var reqId = ++suggestReqId;
        if (suggestAbort) {
          suggestAbort.abort();
        }
        suggestAbort = typeof AbortController !== 'undefined' ? new AbortController() : null;
        var suggestSignal = suggestAbort ? suggestAbort.signal : undefined;
        showListLoading();
        fetch(apiUrl('public/address-autocomplete', { q: q, city: city }), {
          headers: { Accept: 'application/json' },
          signal: suggestSignal
        })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (reqId !== suggestReqId) return;
            if (!data || !data.success) {
              hideList();
              if (data && data.message) toastr.error(data.message);
              return;
            }
            var preds = data.predictions || [];
            $list.empty();
            if (!preds.length) { hideList(); return; }
            preds.forEach(function (pred) {
              var $li = $('<li class="doctor-address-suggestions__item" role="option"/>').text(pred.description || '');
              $li.on('mousedown', function (e) { e.preventDefault(); });
              $li.on('click', function () { selectPrediction(pred, city); });
              $list.append($li);
            });
            $list.removeAttr('hidden');
          })
          .catch(function (err) {
            if (err && err.name === 'AbortError') return;
            hideList();
          });
      }

      $input.on('input', function () {
        if (!addressOnly && $lat.length && $lng.length) {
          $lat.val('');
          $lng.val('');
        }
        updateSetBadge();
        if (suggestTimer) clearTimeout(suggestTimer);
        var val = String($input.val() || '').trim();
        if (val.length < 3) {
          hideList();
          return;
        }
        suggestTimer = setTimeout(function () { fetchSuggestions(val); }, suggestDebounceMs);
      });

      $input.on('keydown', function (e) {
        if ($list.is('[hidden]')) return;
        var $items = $list.find('.doctor-address-suggestions__item');
        if (!$items.length) return;
        if (e.key === 'ArrowDown') {
          e.preventDefault();
          activeIdx = (activeIdx + 1) % $items.length;
          $items.removeClass('is-active').eq(activeIdx).addClass('is-active');
        } else if (e.key === 'ArrowUp') {
          e.preventDefault();
          activeIdx = activeIdx <= 0 ? $items.length - 1 : activeIdx - 1;
          $items.removeClass('is-active').eq(activeIdx).addClass('is-active');
        } else if (e.key === 'Enter' && activeIdx >= 0) {
          e.preventDefault();
          $items.eq(activeIdx).trigger('click');
        } else if (e.key === 'Escape') {
          hideList();
        }
      });

      $input.on('blur', function () { setTimeout(hideList, 200); });
      updateSetBadge();
    };
  </script>
  @endif
  <script>
    $(document).ready(function() {
      // Allow only numbers in mobile/contact inputs
      $('#contact_no, #mobile').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
      });

      @if($type === 'freelancer')
      @php
        $freelancerNurseMatchNeedles = array_map(
            'strtolower',
            array_filter(array_map('trim', (array) config('freelancer_profile.nurse_service_match', ['nurse'])))
        );
      @endphp
      var freelancerNurseNeedles = @json($freelancerNurseMatchNeedles);

      function isFreelancerNurseJob(title) {
        if (!title) return false;
        var t = String(title).toLowerCase().trim();
        return freelancerNurseNeedles.some(function(needle) {
          return needle && (t === needle || t.indexOf(needle) !== -1);
        });
      }

      function syncFreelancerNurseDeclaration() {
        var isNurse = isFreelancerNurseJob($('#job_title').val());
        var $wrap = $('#freelancerDeclarationNurseWrap');
        var $cb = $('#declaration_details_accurate');
        if (isNurse) {
          $wrap.show();
          $cb.prop('required', true);
        } else {
          $wrap.hide();
          $cb.prop('required', false).prop('checked', false);
        }
      }
      window.syncFreelancerNurseDeclaration = syncFreelancerNurseDeclaration;

      $('#job_title').on('change', syncFreelancerNurseDeclaration);
      syncFreelancerNurseDeclaration();
      @endif

      @if($type === 'customer' && !empty($registration_address_search_enabled))
      if (typeof window.attachRegistrationGoogleAddressSearch === 'function') {
        window.attachRegistrationGoogleAddressSearch({
          input: '#address',
          suggestions: '#customer_address_suggestions',
          citySelector: '#location',
          addressOnly: true,
          okBadge: '#customer_address_set_ok'
        });
        $('#location').on('change', function () {
          $('#address').val('');
          $('#customer_address_set_ok').prop('hidden', true);
          $('#customer_address_suggestions').attr('hidden', 'hidden').empty();
        });
      }
      @endif
      
      // Form submission
      $('#registerForm').on('submit', function(e) {
        e.preventDefault();
        @if(in_array($type, ['vendor', 'freelancer'], true))
        if (typeof window.validateProviderRegistrationSelection === 'function') {
          var providerErr = window.validateProviderRegistrationSelection();
          if (providerErr) {
            toastr.warning(providerErr);
            return;
          }
        }
        @endif
        @if($type === 'freelancer')
        if (!$('#location').val()) {
          toastr.warning('Please select Location first.');
          return;
        }
        if (!String($('#full_address').val() || '').trim()) {
          toastr.warning('Please enter your Full Address (or use Fetch by GPS).');
          $('#full_address').focus();
          return;
        }
        var bankMethod = ($('#bank_payment_method').val() || 'account').toString();
        if (bankMethod === 'upi') {
          if (!String($('#upi_id').val() || '').trim()) {
            toastr.warning('Please enter your UPI ID.');
            $('#upi_id').focus();
            return;
          }
        } else {
          if (!String($('#account_name').val() || '').trim()
            || !String($('#account_number').val() || '').trim()
            || !String($('#ifsc_code').val() || '').trim()) {
            toastr.warning('Please fill Account Name, Account Number and IFSC Code.');
            return;
          }
          if (!$('#bank_document').val()) {
            toastr.warning('Please upload Bank Document (Cancelled Cheque).');
            return;
          }
        }
        if ($('#freelancerDeclarationNurseWrap').is(':visible') && !$('#declaration_details_accurate').is(':checked')) {
          toastr.warning('Please confirm that your details and documents are accurate.');
          return;
        }
        if (!$('#declaration_understands_platform').is(':checked')) {
          toastr.warning('Please confirm that you understand how Carelix works.');
          return;
        }
        if (!$('#declaration_agrees_terms').is(':checked')) {
          toastr.warning('Please confirm that you agree to Carelix\'s terms and privacy policy.');
          return;
        }
        if (!window.freelancerMobileVerified) {
          toastr.warning('Please verify your Contact No with OTP before submitting.');
          if (!$('#freelancer_mobile_otp_wrap').is(':visible')) {
            $('#freelancerMobileSendOtpBtn').trigger('click');
          } else {
            $('#freelancer_mobile_otp').focus();
          }
          return;
        }
        @endif
        @if($type === 'vendor')
        if (!window.vendorMobileVerified) {
          toastr.warning('Please verify your Contact No with OTP before submitting.');
          if (!$('#vendor_mobile_otp_wrap').is(':visible')) {
            $('#vendorMobileSendOtpBtn').trigger('click');
          } else {
            $('#vendor_mobile_otp').focus();
          }
          return;
        }
        @endif
        @if($type === 'doctor')
        if ($('#current_same_as_permanent').is(':checked')) {
          $('#current_address').val($('#permanent_address').val());
        }
        if (!window.doctorMobileVerified) {
          toastr.warning('Please verify your mobile number with OTP before submitting.');
          if (!$('#doctor_mobile_otp_wrap').is(':visible')) {
            $('#doctorMobileSendOtpBtn').trigger('click');
          } else {
            $('#doctor_mobile_otp').focus();
          }
          return;
        }
        @endif
        var $btn = $('#submitBtn');
        $btn.prop('disabled', true).text('Creating Account...');
        
        @if($type === 'doctor')
        if ($('#doctor_city').length && !$('#doctor_city').val()) {
          toastr.warning('Please search and select your city.');
          $btn.prop('disabled', false).text('Create Account');
          return;
        }
        if (typeof window.syncDoctorProfileHidden === 'function') window.syncDoctorProfileHidden();
        if (typeof window.validateDoctorConsultationSelection === 'function') {
          var consultErr = window.validateDoctorConsultationSelection();
          if (consultErr) {
            toastr.warning(consultErr);
            $btn.prop('disabled', false).text('Create Account');
            return;
          }
        }
        if ($('input.mode-cb[value="home_visit"]').is(':checked')) {
          if (!$('#base_location_lat').val() || !$('#base_location_lng').val()) {
            toastr.warning('Please set home visit base location: type address and pick a Google suggestion.');
            $btn.prop('disabled', false).text('Create Account');
            return;
          }
        }
        if ($('input.mode-cb[value="clinic_visit"]').is(':checked')) {
          if (!$('#clinic_lat').val() || !$('#clinic_lng').val()) {
            toastr.warning('Please set clinic location: type address and pick a Google suggestion.');
            $btn.prop('disabled', false).text('Create Account');
            return;
          }
        }
        @endif
        var formData = new FormData(this);
        
        // Debug: Log form data (excluding files for console)
        console.log('Form submission - Type:', '{{ $type }}');
        for (var pair of formData.entries()) {
          if (pair[1] instanceof File) {
            console.log(pair[0] + ': File - ' + pair[1].name + ' (' + pair[1].size + ' bytes)');
          } else {
            console.log(pair[0] + ': ' + pair[1]);
          }
        }
        
        $.ajax({
          url: '{{ route("register.submit") }}',
          type: 'POST',
          data: formData,
          processData: false,
          contentType: false,
          success: function(response) {
            if (response.success) {
              toastr.success(response.message);
              setTimeout(function() {
                if (response.redirect) {
                  window.location.href = response.redirect;
                } else {
                  window.location.href = '{{ route("home") }}';
                }
              }, 600);
            } else {
              toastr.error(response.message || 'Registration failed');
              $btn.prop('disabled', false).text('Create Account');
            }
          },
          error: function(xhr) {
            var errorMsg = 'Registration failed';
            if (xhr.responseJSON) {
              if (xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
              } else if (xhr.responseJSON.errors) {
                var errors = Object.values(xhr.responseJSON.errors).flat();
                errorMsg = errors.join('<br>');
              }
            }
            toastr.error(errorMsg);
            $btn.prop('disabled', false).text('Create Account');
          }
        });
      });
    });
  </script>
  @if($type === 'vendor')
  @include('partials.registration-mobile-otp-script', [
    'otpPrefix' => 'vendor',
    'sendOtpRoute' => route('register.vendor.send-mobile-otp'),
    'verifyOtpRoute' => route('register.vendor.verify-mobile-otp'),
  ])
  @endif
  @if($type === 'freelancer')
  @include('partials.registration-mobile-otp-script', [
    'otpPrefix' => 'freelancer',
    'sendOtpRoute' => route('register.freelancer.send-mobile-otp'),
    'verifyOtpRoute' => route('register.freelancer.verify-mobile-otp'),
  ])
  @endif
  @if(in_array($type, ['vendor', 'freelancer'], true))
  @include('partials.provider-registration-service-script', [
    'registrationProviderType' => $type,
  ])
  @endif
  @if(!$locations->isEmpty())
  @include('includes.location-select-scripts')
  <script>
    $(function () {
      if (typeof initLocationSelects === 'function') {
        initLocationSelects('.right');
      }
    });
  </script>
  @endif
  @if($type === 'freelancer')
  <script>
  $(function () {
    var reverseUrl = @json(url('/api/public/reverse-geocode'));
    var addressSearchEnabled = @json(!empty($registration_address_search_enabled));

    function selectedLocationCity() {
      return ($('#location').val() || '').toString().trim();
    }

    function syncFullAddressVisibility() {
      var loc = selectedLocationCity();
      var $wrap = $('#freelancerFullAddressWrap');
      if (loc) {
        $wrap.show();
        $('#full_address').prop('required', true);
      } else {
        $wrap.hide();
        $('#full_address').prop('required', false).val('');
        $('#full_address_lat, #full_address_lng').val('');
        $('#fullAddressGpsStatus').text('');
        $('#freelancer_full_address_set_ok').prop('hidden', true);
        $('#freelancer_full_address_suggestions').attr('hidden', 'hidden').empty();
      }
    }

    $('#location').on('change select2:select select2:clear', function () {
      $('#full_address').val('');
      $('#full_address_lat, #full_address_lng').val('');
      $('#fullAddressGpsStatus').text('');
      $('#freelancer_full_address_set_ok').prop('hidden', true);
      $('#freelancer_full_address_suggestions').attr('hidden', 'hidden').empty();
      syncFullAddressVisibility();
    });
    syncFullAddressVisibility();

    if (addressSearchEnabled && typeof window.attachRegistrationGoogleAddressSearch === 'function') {
      window.attachRegistrationGoogleAddressSearch({
        input: '#full_address',
        lat: '#full_address_lat',
        lng: '#full_address_lng',
        suggestions: '#freelancer_full_address_suggestions',
        citySelector: '#location',
        okBadge: '#freelancer_full_address_set_ok'
      });
    }

    $('#fetchFullAddressByGpsBtn').on('click', function () {
      var $btn = $(this);
      var $status = $('#fullAddressGpsStatus');
      var city = selectedLocationCity();
      if (!city) {
        $status.text('Select Location (city) first.');
        return;
      }
      if (!navigator.geolocation) {
        $status.text('GPS is not supported in this browser.');
        return;
      }
      $btn.prop('disabled', true);
      $status.text('Fetching current location…');
      navigator.geolocation.getCurrentPosition(function (pos) {
        var lat = pos.coords.latitude;
        var lng = pos.coords.longitude;
        $('#full_address_lat').val(lat);
        $('#full_address_lng').val(lng);
        $.getJSON(reverseUrl, { lat: lat, lng: lng, city: city })
          .done(function (res) {
            if (res && res.success && res.address) {
              $('#full_address').val(res.address);
              if (res.lat != null) $('#full_address_lat').val(res.lat);
              if (res.lng != null) $('#full_address_lng').val(res.lng);
              $('#freelancer_full_address_set_ok').prop('hidden', false);
              $status.text('Address filled from GPS (' + city + ').');
            } else {
              $('#full_address_lat, #full_address_lng').val('');
              $status.text((res && res.message) || ('GPS is outside ' + city + '. Type an address in ' + city + '.'));
            }
          })
          .fail(function (xhr) {
            $('#full_address_lat, #full_address_lng').val('');
            var msg = (xhr.responseJSON && xhr.responseJSON.message)
              ? xhr.responseJSON.message
              : ('Could not resolve GPS address in ' + city + '. Type and pick a suggestion.');
            $status.text(msg);
          })
          .always(function () {
            $btn.prop('disabled', false);
          });
      }, function (err) {
        $btn.prop('disabled', false);
        var msg = 'Could not get GPS location.';
        if (err && err.code === 1) msg = 'Location permission denied. Allow location access or type address manually.';
        $status.text(msg);
      }, { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 });
    });

    function syncFreelancerBankPaymentMethod() {
      var method = ($('input[name="bank_payment_method_radio"]:checked').val() || 'account').toString();
      $('#bank_payment_method').val(method);
      $('#bankPayMethodAccountCard').toggleClass('is-active', method === 'account');
      $('#bankPayMethodUpiCard').toggleClass('is-active', method === 'upi');

      if (method === 'upi') {
        $('#freelancerBankAccountFields').hide();
        $('#freelancerBankUpiFields').show();
        $('#account_name, #account_number, #ifsc_code, #bank_document').prop('required', false).prop('disabled', true);
        $('#upi_id').prop('required', true).prop('disabled', false);
      } else {
        $('#freelancerBankUpiFields').hide();
        $('#freelancerBankAccountFields').show();
        $('#account_name, #account_number, #ifsc_code, #bank_document').prop('required', true).prop('disabled', false);
        $('#upi_id').prop('required', false).prop('disabled', true).val('');
      }
    }
    $(document).on('change', 'input[name="bank_payment_method_radio"]', syncFreelancerBankPaymentMethod);
    syncFreelancerBankPaymentMethod();
  });
  </script>
  @endif
  @if($type === 'doctor')
  <script>
    $(function() {
      var pricingAbort = null;
      var adminPricingBySubAndMode = {}; // {subIdOr0: {online: {website_price, doctor_max_price}}}
      var doctorPricingState = []; // array payload stored in hidden field

      function regI18n(key, fallback) {
        if (window.registrationI18n && typeof window.registrationI18n.t === 'function') {
          return window.registrationI18n.t(key, fallback);
        }
        return fallback || key;
      }

      function regApiUrl(path, params) {
        var base = '{{ rtrim(url("/api"), "/") }}';
        var url = base + '/' + String(path || '').replace(/^\/+/, '');
        var q = [];
        Object.keys(params || {}).forEach(function (k) {
          var v = params[k];
          if (v === undefined || v === null || v === '') return;
          q.push(encodeURIComponent(k) + '=' + encodeURIComponent(String(v)));
        });
        return q.length ? (url + '?' + q.join('&')) : url;
      }

      function selectedLocationId() {
        var $sel = $('#doctor_city');
        if (!$sel.length || !String($sel.val() || '').trim()) return null;
        var $opt = $sel.find('option:selected');
        if (!$opt.length) return null;
        var id = $opt.attr('data-location-id') || $opt.data('locationId') || $opt.data('location-id');
        return id ? parseInt(id, 10) : null;
      }

      function hasCitySelected() {
        return !!String($('#doctor_city').val() || '').trim() && !!selectedLocationId();
      }

      function hasServiceSelected() {
        return !!(typeof getSelectedServiceRow === 'function' ? getSelectedServiceRow() : null);
      }

      function pricingGateMessage() {
        if (!hasCitySelected()) {
          return regI18n('doctor.pricing.gate_city', 'First select <strong>City</strong> (Section 2), then choose consultation service / sub-service — prices will appear here.');
        }
        if (!hasServiceSelected()) {
          return regI18n('doctor.pricing.gate_service', 'First select <strong>Consultation service</strong>.');
        }
        var items = currentSelectedItemsForPricing();
        var svc = getSelectedServiceRow();
        if (svc && (svc.sub_services || []).length && !items.length) {
          return regI18n('doctor.pricing.gate_sub', 'Select at least one <strong>sub-service</strong> and choose its tags first.');
        }
        return null;
      }

      function fetchAdminPricing() {
        var locId = selectedLocationId();
        var svc = (typeof getSelectedServiceRow === 'function') ? getSelectedServiceRow() : null;
        if (!locId || !svc || !svc.id) {
          adminPricingBySubAndMode = {};
          return Promise.resolve(null);
        }

        if (pricingAbort) pricingAbort.abort();
        pricingAbort = typeof AbortController !== 'undefined' ? new AbortController() : null;
        var signal = pricingAbort ? pricingAbort.signal : undefined;

        return fetch(regApiUrl('public/doctor-registration/pricing', { location_id: locId, service_id: svc.id }), {
          headers: { Accept: 'application/json' },
          signal: signal
        })
          .then(function (r) { return r.json(); })
          .then(function (res) {
            if (res && res.success && res.data && res.data.prices) {
              adminPricingBySubAndMode = res.data.prices || {};
            } else {
              adminPricingBySubAndMode = {};
            }
            return adminPricingBySubAndMode;
          })
          .catch(function (err) {
            if (err && err.name === 'AbortError') return null;
            adminPricingBySubAndMode = {};
            return null;
          });
      }

      function currentSelectedItemsForPricing() {
        var svc = (typeof getSelectedServiceRow === 'function') ? getSelectedServiceRow() : null;
        if (!svc) return [];

        var subs = svc.sub_services || [];
        if (subs.length) {
          var keys = Object.keys(selectedSubServices || {});
          return keys.map(function (sid) {
            var entry = selectedSubServices[String(sid)] || {};
            return {
              sub_service_id: parseInt(String(sid), 10),
              sub_service_name: entry.name || 'Sub-service'
            };
          }).filter(function (x) { return x.sub_service_id > 0; });
        }

        return [{ sub_service_id: 0, sub_service_name: null }];
      }

      function modeEnabled(modeKey) {
        return $('input.mode-cb[value="' + modeKey + '"]').is(':checked');
      }

      function findPricingItem(subId) {
        for (var i = 0; i < doctorPricingState.length; i++) {
          if (parseInt(doctorPricingState[i].sub_service_id || 0, 10) === parseInt(subId || 0, 10)) return doctorPricingState[i];
        }
        return null;
      }

      function syncPricingStateFromInputs() {
        var svc = (typeof getSelectedServiceRow === 'function') ? getSelectedServiceRow() : null;
        if (!svc) {
          doctorPricingState = [];
          $('#consultation_pricing_json').val('[]');
          return;
        }

        var items = currentSelectedItemsForPricing();
        var modes = ['online', 'home_visit', 'clinic_visit'].filter(function (m) { return modeEnabled(m); });
        var next = [];

        items.forEach(function (it) {
          var existing = findPricingItem(it.sub_service_id) || {};
          var row = {
            service_id: parseInt(svc.id, 10),
            service_name: String(svc.name || ''),
            sub_service_id: parseInt(it.sub_service_id || 0, 10),
            sub_service_name: it.sub_service_name ? String(it.sub_service_name) : null,
            modes: {}
          };

          modes.forEach(function (modeKey) {
            var inputId = 'doc_price_' + modeKey + '_' + String(it.sub_service_id || 0);
            var $inp = $('#' + inputId);
            var val = $inp.length ? String($inp.val() || '').trim() : '';
            var admin = (adminPricingBySubAndMode[String(it.sub_service_id || 0)] || {})[modeKey] || null;
            var locked = admin && admin.doctor_max_price !== null && admin.doctor_max_price !== '';
            var doctorPrice = locked ? admin.doctor_max_price : (val !== '' ? parseFloat(val) : null);

            row.modes[modeKey] = {
              doctor_price: doctorPrice,
              website_price: admin ? admin.website_price : null,
              doctor_max_price: admin ? admin.doctor_max_price : null,
              locked: !!locked
            };
          });

          Object.keys(existing.modes || {}).forEach(function (modeKey) {
            if (row.modes[modeKey]) return;
            row.modes[modeKey] = existing.modes[modeKey];
          });

          next.push(row);
        });

        doctorPricingState = next;
        $('#consultation_pricing_json').val(JSON.stringify(doctorPricingState));

        // Best-effort legacy fields (keep them non-empty for integrations that still read them).
        var first = doctorPricingState[0] || null;
        if (first && first.modes) {
          if (first.modes.online && first.modes.online.doctor_price !== null && first.modes.online.doctor_price !== undefined) {
            $('#online_charges').val(first.modes.online.doctor_price);
          }
          if (first.modes.home_visit && first.modes.home_visit.doctor_price !== null && first.modes.home_visit.doctor_price !== undefined) {
            $('#home_visit_charges').val(first.modes.home_visit.doctor_price);
          }
          if (first.modes.clinic_visit && first.modes.clinic_visit.doctor_price !== null && first.modes.clinic_visit.doctor_price !== undefined) {
            $('#clinic_consultation_charges').val(first.modes.clinic_visit.doctor_price);
          }
        }
      }

      function renderPricingForMode(modeKey) {
        var $wrap = $('#pricing_' + modeKey + '_wrap').empty();
        var gate = pricingGateMessage();
        if (gate) {
          $wrap.append($('<p class="doctor-pricing-hint doctor-pricing-hint--warn"/>').html(gate));
          return;
        }

        var items = currentSelectedItemsForPricing();
        var modeLabel = {
          online: regI18n('doctor.pricing.mode_online', 'Online'),
          home_visit: regI18n('doctor.pricing.mode_home', 'Home visit'),
          clinic_visit: regI18n('doctor.pricing.mode_clinic', 'Clinic visit')
        }[modeKey] || modeKey;

        var anyNeedsDoctorPrice = items.some(function (it) {
          var subKey = String(it.sub_service_id || 0);
          var admin = (adminPricingBySubAndMode[subKey] || {})[modeKey] || null;
          return !(admin && admin.doctor_max_price !== null && admin.doctor_max_price !== '');
        });

        if (anyNeedsDoctorPrice) {
          $wrap.append($('<p class="doctor-pricing-hint"/>').text(regI18n('doctor.pricing.hint_enter', 'Where admin has not set a price, enter your consultation charge.')));
        } else {
          $wrap.append($('<p class="doctor-pricing-hint"/>').text(regI18n('doctor.pricing.hint_admin_set', 'Admin has set all prices for this mode — you do not need to enter an amount.')));
        }

        var $table = $('<table class="doctor-pricing-table"/>');
        var headHtml = '<tr><th>' + regI18n('doctor.pricing.th_service', 'Service / Sub-service') + '</th><th>' + regI18n('doctor.pricing.th_max', 'Doctor max price (₹)') + '</th>';
        if (anyNeedsDoctorPrice) {
          headHtml += '<th>' + regI18n('doctor.pricing.th_charges', 'Your charges (₹)') + '</th>';
        }
        headHtml += '</tr>';
        var $thead = $('<thead/>').html(headHtml);
        var $tbody = $('<tbody/>');

        items.forEach(function (it) {
          var subKey = String(it.sub_service_id || 0);
          var admin = (adminPricingBySubAndMode[subKey] || {})[modeKey] || null;
          var locked = admin && admin.doctor_max_price !== null && admin.doctor_max_price !== '';
          var displayName = it.sub_service_id ? String(it.sub_service_name || 'Sub-service') : String((getSelectedServiceRow() || {}).name || 'Service');

          var existing = findPricingItem(it.sub_service_id) || {};
          var existingMode = (existing.modes || {})[modeKey] || {};
          var val = existingMode && existingMode.doctor_price !== undefined && existingMode.doctor_price !== null ? existingMode.doctor_price : '';
          var inputId = 'doc_price_' + modeKey + '_' + subKey;

          var maxTxt = locked ? ('₹' + admin.doctor_max_price) : '—';

          var $tr = $('<tr/>');
          $tr.append($('<td/>').html('<strong>' + $('<div/>').text(displayName).html() + '</strong><br><span class="small text-muted">' + modeLabel + '</span>'));
          var $maxTd = $('<td/>');
          if (locked) {
            $maxTd.addClass('doctor-pricing-locked').html(maxTxt + ' <span class="doctor-pricing-badge">' + regI18n('doctor.pricing.badge_admin', 'Admin set') + '</span>');
          } else {
            $maxTd.text('—');
          }
          $tr.append($maxTd);

          if (locked) {
            $('<input type="hidden" class="mode-price-input"/>')
              .attr('id', inputId)
              .attr('data-mode', modeKey)
              .attr('data-sub-id', subKey)
              .val(admin.doctor_max_price)
              .appendTo($tr);
          }

          if (anyNeedsDoctorPrice) {
            var $chargeTd = $('<td/>');
            if (!locked) {
              var $inp = $('<input type="number" min="0" step="1" class="form-control form-control-sm mode-price-input"/>')
                .attr('id', inputId)
                .attr('data-mode', modeKey)
                .attr('data-sub-id', subKey)
                .attr('placeholder', regI18n('doctor.pricing.placeholder_amount', 'Enter amount'))
                .val(val);
              $chargeTd.append($inp);
            } else {
              $chargeTd.text('—');
            }
            $tr.append($chargeTd);
          }
          $tbody.append($tr);
        });

        $table.append($thead).append($tbody);
        $wrap.append($table);
      }

      function renderAllPricing() {
        syncPricingStateFromInputs();
        ['online', 'home_visit', 'clinic_visit'].forEach(function (modeKey) {
          if (modeEnabled(modeKey)) {
            renderPricingForMode(modeKey);
          } else {
            $('#pricing_' + modeKey + '_wrap').empty();
          }
        });
        syncPricingStateFromInputs();
      }

      function toggleDoctorModes(skipGateCheck) {
        var online = $('input.mode-cb[value="online"]').is(':checked');
        var home = $('input.mode-cb[value="home_visit"]').is(':checked');
        var clinic = $('input.mode-cb[value="clinic_visit"]').is(':checked');

        if (!skipGateCheck && (online || home || clinic) && !hasCitySelected()) {
          $('input.mode-cb').prop('checked', false);
          online = home = clinic = false;
          if (typeof toastr !== 'undefined') {
            toastr.warning(regI18n('doctor.toast.select_city_modes', 'Select your city first (Section 2), then choose consultation mode.'));
          }
        }

        $('.doctor-mode-item[data-mode="online"]').toggleClass('is-expanded', online);
        $('.doctor-mode-item[data-mode="home_visit"]').toggleClass('is-expanded', home);
        $('.doctor-mode-item[data-mode="clinic_visit"]').toggleClass('is-expanded', clinic);
        $('#block_online').toggleClass('visible', online);
        $('#block_home_visit').toggleClass('visible', home);
        $('#block_clinic_visit').toggleClass('visible', clinic);
        $('.mode-input-online').prop('disabled', !online);
        $('.mode-input-home').prop('disabled', !home);
        $('.mode-input-clinic').prop('disabled', !clinic);
        renderAllPricing();
      }
      $('.mode-cb').on('change', function () {
        if ($(this).is(':checked') && !hasCitySelected()) {
          $(this).prop('checked', false);
          if (typeof toastr !== 'undefined') {
            toastr.warning(regI18n('doctor.toast.select_city', 'Select your city first (Section 2).'));
          }
          return;
        }
        toggleDoctorModes(true);
      });
      toggleDoctorModes(true);

      window.addEventListener('registrationLanguageChanged', function () {
        renumberProfileCards('edu_rows', 'edu');
        renumberProfileCards('exp_rows', 'exp');
        if (window.registrationI18n && typeof window.registrationI18n.applyDoctorProfileCards === 'function') {
          window.registrationI18n.applyDoctorProfileCards();
        }
        if (typeof renderConsultationUi === 'function') {
          renderConsultationUi();
        }
        renderAllPricing();
      });

      $(document).on('input change', '.mode-price-input', function () {
        syncPricingStateFromInputs();
      });

      $('#current_same_as_permanent').on('change', function() {
        if ($(this).is(':checked')) {
          $('#current_address_wrap').hide();
          $('#current_address').prop('required', false);
        } else {
          $('#current_address_wrap').show();
          $('#current_address').prop('required', true);
        }
      }).trigger('change');

      window.doctorMobileVerified = false;
      var doctorOtpCsrf = $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val();

      function resetDoctorMobileVerification() {
        window.doctorMobileVerified = false;
        $('#doctor_mobile_verified_flag').val('0');
        $('#contact_no').prop('readonly', false).removeClass('doctor-mobile-verified-lock');
        $('#doctor_mobile_otp_wrap').hide();
        $('#doctor_mobile_otp').val('');
        $('#doctor_mobile_verified_badge').hide();
        $('#doctorMobileSendOtpBtn').show().prop('disabled', false).text(regI18n('otp.send', 'Send OTP'));
        $('#doctorMobileVerifyOtpBtn').prop('disabled', false).text(regI18n('otp.verify', 'Verify'));
      }

      function setDoctorMobileVerifiedUi() {
        window.doctorMobileVerified = true;
        $('#doctor_mobile_verified_flag').val('1');
        $('#contact_no').prop('readonly', true).addClass('doctor-mobile-verified-lock');
        $('#doctor_mobile_verified_badge').show();
        $('#doctorMobileSendOtpBtn').hide();
        $('#doctorMobileVerifyOtpBtn').prop('disabled', true).text(regI18n('otp.verified_btn', 'Verified'));
      }

      $('#contact_no').on('input', function() {
        if (window.doctorMobileVerified || $('#doctor_mobile_otp_wrap').is(':visible')) {
          resetDoctorMobileVerification();
        }
      });

      $('#doctorMobileSendOtpBtn').on('click', function() {
        var mobile = String($('#contact_no').val() || '').replace(/\D/g, '');
        if (mobile.length !== 10) {
          toastr.warning('Enter a valid 10-digit mobile number first.');
          return;
        }
        var $btn = $(this);
        $btn.prop('disabled', true).text(regI18n('otp.sending', 'Sending…'));
        $.ajax({
          url: '{{ route("register.doctor.send-mobile-otp") }}',
          type: 'POST',
          data: { mobile: mobile, _token: doctorOtpCsrf },
          success: function(res) {
            if (res.success) {
              toastr.success(res.message || 'OTP sent.');
              $('#doctor_mobile_otp_wrap').slideDown();
              $('#doctor_mobile_otp').focus();
              $btn.text(regI18n('otp.resend', 'Resend OTP')).prop('disabled', false);
              if (res.meta && res.meta.otp) {
                console.log('Doctor reg OTP (debug):', res.meta);
                $('#doctor_mobile_otp').val(res.meta.otp);
              }
            } else {
              toastr.error(res.message || 'Could not send OTP.');
              $btn.prop('disabled', false).text(regI18n('otp.send', 'Send OTP'));
            }
          },
          error: function(xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Could not send OTP.';
            toastr.error(msg);
            $btn.prop('disabled', false).text(regI18n('otp.send', 'Send OTP'));
          }
        });
      });

      $('#doctorMobileVerifyOtpBtn').on('click', function() {
        var mobile = String($('#contact_no').val() || '').replace(/\D/g, '');
        var otp = String($('#doctor_mobile_otp').val() || '').replace(/\D/g, '');
        if (mobile.length !== 10) {
          toastr.warning('Enter a valid mobile number.');
          return;
        }
        if (otp.length !== 6) {
          toastr.warning('Enter the 6-digit OTP.');
          return;
        }
        var $btn = $(this);
        $btn.prop('disabled', true).text(regI18n('otp.verifying', 'Verifying…'));
        $.ajax({
          url: '{{ route("register.doctor.verify-mobile-otp") }}',
          type: 'POST',
          data: { mobile: mobile, otp: otp, _token: doctorOtpCsrf },
          success: function(res) {
            if (res.success) {
              toastr.success(res.message || 'Mobile verified.');
              setDoctorMobileVerifiedUi();
            } else {
              toastr.error(res.message || 'Invalid OTP.');
              $btn.prop('disabled', false).text(regI18n('otp.verify', 'Verify'));
            }
          },
          error: function(xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Verification failed.';
            toastr.error(msg);
            $btn.prop('disabled', false).text(regI18n('otp.verify', 'Verify'));
          }
        });
      });

      $('#doctor_mobile_otp').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
      });

      @if(!empty($registration_address_search_enabled))
      if (typeof window.attachRegistrationGoogleAddressSearch === 'function') {
      window.attachRegistrationGoogleAddressSearch({
        input: '#base_location_address',
        lat: '#base_location_lat',
        lng: '#base_location_lng',
        suggestions: '#base_location_suggestions',
        okBadge: '#base_location_set_ok',
        missingHint: '#base_location_set_missing',
        citySelector: '#doctor_city'
      });
      window.attachRegistrationGoogleAddressSearch({
        input: '#clinic_address',
        lat: '#clinic_lat',
        lng: '#clinic_lng',
        suggestions: '#clinic_address_suggestions',
        okBadge: '#clinic_address_set_ok',
        missingHint: '#clinic_address_set_missing',
        citySelector: '#doctor_city'
      });
      window.attachRegistrationGoogleAddressSearch({
        input: '#permanent_address',
        suggestions: '#permanent_address_suggestions',
        okBadge: '#permanent_address_set_ok',
        citySelector: '#doctor_city',
        addressOnly: true
      });
      window.attachRegistrationGoogleAddressSearch({
        input: '#current_address',
        suggestions: '#current_address_suggestions',
        okBadge: '#current_address_set_ok',
        citySelector: '#doctor_city',
        addressOnly: true
      });
      $('#doctor_city').on('change', function () {
        $('#permanent_address, #current_address').val('');
        $('#permanent_address_set_ok, #current_address_set_ok').prop('hidden', true);
        $('#permanent_address_suggestions, #current_address_suggestions').attr('hidden', 'hidden').empty();
      });
      }
      @endif

      function renumberProfileCards(containerId, kind) {
        var titleKey = kind === 'edu' ? 'doctor.edu.card_title' : 'doctor.exp.card_title';
        var fallback = kind === 'edu' ? 'Education #:n' : 'Experience #:n';
        $('#' + containerId + ' .doctor-profile-card').each(function (idx) {
          var n = idx + 1;
          var title = regI18n(titleKey, fallback).replace(/:n/g, String(n));
          $(this).find('.doctor-profile-card__badge').text(String(n));
          $(this).find('.doctor-profile-card__title span:last').text(title);
        });
      }

      function addEduRow(v) {
        v = v || { degree: '', institution: '', year_completed: '' };
        var n = $('#edu_rows .doctor-profile-card').length + 1;
        var $r = $('<div class="doctor-profile-card" data-kind="edu"/>');
        $r.html(
          '<div class="doctor-profile-card__head">' +
            '<div class="doctor-profile-card__title">' +
              '<span class="doctor-profile-card__badge">' + n + '</span>' +
              '<i class="fas fa-graduation-cap"></i>' +
              '<span>Education #' + n + '</span>' +
            '</div>' +
            '<button type="button" class="doctor-profile-card__remove">Remove</button>' +
          '</div>' +
          '<div class="doctor-profile-card__body">' +
            '<div class="doctor-profile-grid">' +
              '<div class="doctor-profile-field doctor-profile-field--full">' +
                '<label>Degree / qualification <span class="required">*</span></label>' +
                '<input type="text" class="edu-degree" placeholder="e.g. MBBS, MD, BPT" maxlength="500">' +
              '</div>' +
              '<div class="doctor-profile-field doctor-profile-field--full">' +
                '<label>Institution / university <span class="required">*</span></label>' +
                '<input type="text" class="edu-inst" placeholder="e.g. AIIMS Delhi" maxlength="500">' +
              '</div>' +
              '<div class="doctor-profile-field">' +
                '<label>Year completed <span class="optional">(optional)</span></label>' +
                '<input type="text" class="edu-year" placeholder="e.g. 2018" maxlength="32" inputmode="numeric">' +
              '</div>' +
            '</div>' +
          '</div>'
        );
        $r.find('.doctor-profile-card__remove').on('click', function () {
          if ($('#edu_rows .doctor-profile-card').length <= 1) {
            if (typeof toastr !== 'undefined') toastr.warning(regI18n('doctor.edu.min_one', 'At least one education entry is required.'));
            return;
          }
          $r.remove();
          renumberProfileCards('edu_rows', 'edu');
        });
        $r.find('.edu-degree').val(v.degree || '');
        $r.find('.edu-inst').val(v.institution || '');
        $r.find('.edu-year').val(v.year_completed || '');
        $('#edu_rows').append($r);
        renumberProfileCards('edu_rows', 'edu');
        if (window.registrationI18n && typeof window.registrationI18n.applyDoctorProfileCards === 'function') {
          window.registrationI18n.applyDoctorProfileCards();
        }
      }

      function addExpRow(v) {
        v = v || { title: '', organization: '', from_year: '', to_year: '', details: '' };
        var n = $('#exp_rows .doctor-profile-card').length + 1;
        var $r = $('<div class="doctor-profile-card" data-kind="exp"/>');
        $r.html(
          '<div class="doctor-profile-card__head">' +
            '<div class="doctor-profile-card__title">' +
              '<span class="doctor-profile-card__badge">' + n + '</span>' +
              '<i class="fas fa-briefcase-medical"></i>' +
              '<span>Experience #' + n + '</span>' +
            '</div>' +
            '<button type="button" class="doctor-profile-card__remove">Remove</button>' +
          '</div>' +
          '<div class="doctor-profile-card__body">' +
            '<div class="doctor-profile-grid doctor-profile-grid--2">' +
              '<div class="doctor-profile-field doctor-profile-field--full">' +
                '<label>Role / designation <span class="required">*</span></label>' +
                '<input type="text" class="exp-title" placeholder="e.g. Senior Consultant" maxlength="500">' +
              '</div>' +
              '<div class="doctor-profile-field doctor-profile-field--full">' +
                '<label>Organization / hospital <span class="required">*</span></label>' +
                '<input type="text" class="exp-org" placeholder="e.g. Apollo Hospital" maxlength="500">' +
              '</div>' +
              '<div class="doctor-profile-field">' +
                '<label>From (year) <span class="required">*</span></label>' +
                '<input type="text" class="exp-from" placeholder="e.g. 2019" maxlength="32" inputmode="numeric">' +
              '</div>' +
              '<div class="doctor-profile-field">' +
                '<label>To (year) <span class="optional">(blank = present)</span></label>' +
                '<input type="text" class="exp-to" placeholder="e.g. 2024" maxlength="32" inputmode="numeric">' +
              '</div>' +
              '<div class="doctor-profile-field doctor-profile-field--full">' +
                '<label>Additional details <span class="optional">(optional)</span></label>' +
                '<textarea class="exp-details" rows="3" placeholder="Key responsibilities, department, achievements…" maxlength="2000"></textarea>' +
              '</div>' +
            '</div>' +
          '</div>'
        );
        $r.find('.doctor-profile-card__remove').on('click', function () {
          if ($('#exp_rows .doctor-profile-card').length <= 1) {
            if (typeof toastr !== 'undefined') toastr.warning(regI18n('doctor.exp.min_one', 'At least one experience entry is required.'));
            return;
          }
          $r.remove();
          renumberProfileCards('exp_rows', 'exp');
        });
        $r.find('.exp-title').val(v.title || '');
        $r.find('.exp-org').val(v.organization || '');
        $r.find('.exp-from').val(v.from_year || '');
        $r.find('.exp-to').val(v.to_year || '');
        $r.find('.exp-details').val(v.details || '');
        $('#exp_rows').append($r);
        renumberProfileCards('exp_rows', 'exp');
        if (window.registrationI18n && typeof window.registrationI18n.applyDoctorProfileCards === 'function') {
          window.registrationI18n.applyDoctorProfileCards();
        }
      }

      $('.doctor-repeater-add').on('click', function () {
        var k = $(this).data('add');
        if (k === 'edu') addEduRow();
        if (k === 'exp') addExpRow();
      });
      addEduRow();
      addExpRow();

      var consultationServicesData = [];
      var selectedSubServices = {};

      (function loadConsultationServicesData() {
        var el = document.getElementById('doctor_consultation_services_data');
        if (!el) return;
        try {
          consultationServicesData = JSON.parse(el.textContent || '[]');
        } catch (e) {
          consultationServicesData = [];
        }
      })();

      function getSelectedServiceRow() {
        var name = ($('#doctor_consultation_service').val() || '').trim();
        if (!name) return null;
        for (var i = 0; i < consultationServicesData.length; i++) {
          if (consultationServicesData[i].name === name) return consultationServicesData[i];
        }
        return null;
      }

      function buildTagGrid(tags, checkboxClass, subId) {
        var $grid = $('<div class="doctor-tags-grid" role="group"/>');
        (tags || []).forEach(function (tagName) {
          var $lab = $('<label class="doctor-tag-pill"/>');
          var $cb = $('<input type="checkbox">').addClass(checkboxClass).attr('value', tagName);
          if (subId) $cb.attr('data-sub-id', String(subId));
          $lab.append($cb);
          var $inner = $('<span class="doctor-tag-pill-inner"/>');
          $inner.append($('<span class="doctor-tag-text"/>').text(tagName));
          $inner.append('<span class="doctor-tag-icon" aria-hidden="true"></span>');
          $lab.append($inner);
          $grid.append($lab);
        });
        return $grid;
      }

      function renderParentTags(svc) {
        var $w = $('#spec_wrap').empty();
        var tags = svc && svc.tags ? svc.tags : [];
        if (!tags.length) {
          $w.append($('<p class="doctor-help"/>').text(regI18n('doctor.tags.no_tags', 'No tags have been set for this service yet.')));
          return;
        }
        $w.append(buildTagGrid(tags, 'spec-cb'));
      }

      function refreshSubServiceDropdown(svc) {
        var $sel = $('#sub_service_add_select').empty().append($('<option value=""/>').text(regI18n('doctor.sub_service.placeholder', 'Select sub-service to add')));
        if (!svc || !svc.sub_services) return;
        svc.sub_services.forEach(function (sub) {
          if (selectedSubServices[String(sub.id)]) return;
          $sel.append($('<option/>').val(String(sub.id)).text(sub.name));
        });
      }

      function syncSubServiceTagsFromDom() {
        Object.keys(selectedSubServices).forEach(function (sid) {
          var $cbs = $('.sub-spec-cb[data-sub-id="' + sid + '"]');
          if (!$cbs.length) {
            return;
          }
          selectedSubServices[sid].tags = $cbs.filter(':checked').map(function () {
            return String($(this).val() || '').trim();
          }).get().filter(function (t) { return t !== ''; });
        });
      }

      function renderSubServiceTagBlock(sub) {
        var sid = String(sub.id);
        var entry = selectedSubServices[sid] || {};
        var savedTags = Array.isArray(entry.tags) ? entry.tags : [];
        var $block = $('<div class="doctor-sub-service-block"/>').attr('data-sub-id', sid);
        var $head = $('<div class="doctor-sub-service-block-head"/>');
        $head.append($('<strong/>').text(sub.name));
        $head.append($('<button type="button" class="doctor-sub-remove-btn"/>').text(regI18n('doctor.sub_service.remove', 'Remove')).on('click', function () {
          syncSubServiceTagsFromDom();
          delete selectedSubServices[sid];
          renderConsultationUi();
        }));
        $block.append($head);
        $block.append($('<p class="doctor-help mb-2"/>').text(regI18n('doctor.sub_service.tags_select', 'Select tags for this sub-service:')));
        var tags = sub.tags || [];
        if (!tags.length) {
          $block.append($('<p class="doctor-help mb-0"/>').text(regI18n('doctor.sub_service.no_tags_admin', 'No tags have been set for this sub-service yet.')));
        } else {
          var $grid = buildTagGrid(tags, 'sub-spec-cb', sid);
          $grid.find('.sub-spec-cb').each(function () {
            var val = String($(this).val() || '').trim();
            if (savedTags.indexOf(val) !== -1) {
              $(this).prop('checked', true);
            }
          });
          $block.append($grid);
        }
        return $block;
      }

      function renderSubServiceSummary() {
        var keys = Object.keys(selectedSubServices);
        var $wrap = $('#sub_service_summary_wrap');
        var $list = $('#sub_service_summary_list').empty();
        if (!keys.length) {
          $wrap.hide();
          return;
        }
        $wrap.show();
        keys.forEach(function (sid) {
          var entry = selectedSubServices[sid];
          var tags = Array.isArray(entry.tags) ? entry.tags.slice() : [];
          var $item = $('<div class="doctor-sub-summary-item"/>');
          $item.append($('<span class="sub-name"/>').text(entry.name));
          if (!tags.length) {
            $item.append($('<span class="text-muted small"/>').text(regI18n('doctor.sub_summary.no_tags_yet', ' — no tags selected yet')));
          } else {
            var $chips = $('<span/>');
            tags.forEach(function (t) {
              $chips.append($('<span class="doctor-summary-tag-chip"/>').text(t));
            });
            $item.append($chips);
          }
          $list.append($item);
        });
      }

      function renderConsultationUi() {
        var svc = getSelectedServiceRow();
        selectedSubServices = selectedSubServices || {};
        syncSubServiceTagsFromDom();
        $('#sub_service_tags_stack').empty();
        $('#sub_service_picker_wrap').hide();
        $('#parent_tags_wrap').hide();

        if (!svc) {
          $('#sub_service_summary_wrap').hide();
          return;
        }

        var subs = svc.sub_services || [];
        if (subs.length) {
          $('#sub_service_picker_wrap').show();
          refreshSubServiceDropdown(svc);
          Object.keys(selectedSubServices).forEach(function (sid) {
            var entry = selectedSubServices[sid];
            var sub = subs.find(function (s) { return String(s.id) === sid; });
            if (!sub) {
              delete selectedSubServices[sid];
              return;
            }
            $('#sub_service_tags_stack').append(renderSubServiceTagBlock(sub));
          });
          renderSubServiceSummary();
        } else {
          selectedSubServices = {};
          $('#parent_tags_wrap').show();
          renderParentTags(svc);
          $('#sub_service_summary_wrap').hide();
        }
      }

      $('#doctor_consultation_service').on('change', function () {
        selectedSubServices = {};
        renderConsultationUi();
        doctorPricingState = [];
        adminPricingBySubAndMode = {};
        fetchAdminPricing().then(function () { renderAllPricing(); });
      });

      $('#sub_service_add_select').on('change', function () {
        var sid = $(this).val();
        if (!sid) return;
        var svc = getSelectedServiceRow();
        if (!svc || !svc.sub_services) return;
        var sub = svc.sub_services.find(function (s) { return String(s.id) === String(sid); });
        if (!sub) return;
        syncSubServiceTagsFromDom();
        var subKey = String(sub.id);
        if (!selectedSubServices[subKey]) {
          selectedSubServices[subKey] = { id: sub.id, name: sub.name, tags: [] };
        }
        $(this).val('');
        renderConsultationUi();
        fetchAdminPricing().then(function () { renderAllPricing(); });
      });

      $(document).on('change', '.sub-spec-cb', function () {
        var sid = String($(this).attr('data-sub-id') || '');
        if (sid && selectedSubServices[sid]) {
          selectedSubServices[sid].tags = $('.sub-spec-cb[data-sub-id="' + sid + '"]:checked').map(function () {
            return String($(this).val() || '').trim();
          }).get().filter(function (t) { return t !== ''; });
        }
        renderSubServiceSummary();
        renderAllPricing();
      });

      renderConsultationUi();
      fetchAdminPricing().then(function () { renderAllPricing(); });

      $('#doctor_city').on('change', function () {
        fetchAdminPricing().then(function () { renderAllPricing(); });
      });

      window.validateDoctorConsultationSelection = function () {
        var gate = pricingGateMessage();
        if (gate) {
          return gate.replace(/<[^>]+>/g, '');
        }

        var svc = getSelectedServiceRow();
        if (!svc) return 'Please select consultation service.';
        var subs = svc.sub_services || [];
        if (subs.length) {
          var keys = Object.keys(selectedSubServices);
          if (!keys.length) return 'Kam se kam ek sub-service select karein.';
          syncSubServiceTagsFromDom();
          for (var i = 0; i < keys.length; i++) {
            var sid = keys[i];
            var entry = selectedSubServices[sid];
            var tagCount = Array.isArray(entry.tags) ? entry.tags.length : 0;
            var sub = subs.find(function (s) { return String(s.id) === sid; });
            var allowed = sub && sub.tags ? sub.tags.length : 0;
            if (allowed && !tagCount) {
              return 'Sub-service "' + entry.name + '" ke liye kam se kam ek tag select karein.';
            }
          }
        } else if ((svc.tags || []).length && !$('.spec-cb:checked').length) {
          return 'Apni consultation service ke liye kam se kam ek tag select karein.';
        }

        syncPricingStateFromInputs();
        var items = currentSelectedItemsForPricing();
        var modes = ['online', 'home_visit', 'clinic_visit'].filter(function (m) { return modeEnabled(m); });
        if (!modes.length) {
          return 'Kam se kam ek consultation mode select karein.';
        }
        for (var mi = 0; mi < modes.length; mi++) {
          for (var ii = 0; ii < items.length; ii++) {
            var subId = String(items[ii].sub_service_id || 0);
            var inputId = 'doc_price_' + modes[mi] + '_' + subId;
            var $inp = $('#' + inputId);
            if (!$inp.length) continue;
            var val = String($inp.val() || '').trim();
            if (val === '') {
              var label = { online: 'Online', home_visit: 'Home visit', clinic_visit: 'Clinic visit' }[modes[mi]] || modes[mi];
              return label + ' charges enter karein — ' + (items[ii].sub_service_name || 'Service') + '.';
            }
          }
        }
        return null;
      };

      $('#btn_generate_about').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var name = ($('#name').val() || '').trim();
        var job = ($('#doctor_consultation_service').val() || '').trim();
        var aboutDetails = ($('#about_text').val() || '').trim();
        if (!name || !job) {
          toastr.warning('Enter your name and select consultation service first.');
          return false;
        }
        if (aboutDetails.length < 20) {
          toastr.warning('Pehle About you box mein apne baare mein kam se kam 20 characters ki details likhein (qualification, experience, etc.), phir Generate with AI dabayein.');
          return false;
        }
        var langs = $('input[name="fluent_languages[]"]:checked').map(function() { return $(this).val(); }).get();
        var eduDeg = ($('.edu-degree').first().val() || '').trim();
        var eduInst = ($('.edu-inst').first().val() || '').trim();
        var eduFirst = eduDeg && eduInst ? (eduDeg + ' @ ' + eduInst) : (eduDeg || eduInst);
        var expTitle = ($('.exp-title').first().val() || '').trim();
        var expOrg = ($('.exp-org').first().val() || '').trim();
        var expFirst = expTitle && expOrg ? (expTitle + ' @ ' + expOrg) : (expTitle || expOrg);
        var $btn = $('#btn_generate_about');
        $btn.prop('disabled', true).text('Generating…');
        $.ajax({
          url: '{{ route('register.doctor.generate-about') }}',
          method: 'POST',
          type: 'POST',
          headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'Content-Type': 'application/json'
          },
          data: JSON.stringify({
            name: name,
            job_title: job,
            about_details: aboutDetails,
            fluent_languages: langs,
            education_summary: eduFirst,
            experience_summary: expFirst
          }),
          success: function(res) {
            if (res.success && res.about_text) {
              $('#about_text').val(res.about_text);
              toastr.success('About you generated — please review and edit if needed.');
            } else {
              toastr.error(res.message || 'Could not generate');
            }
          },
          error: function(xhr) {
            var m = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Request failed';
            toastr.error(m);
          },
          complete: function() {
            $btn.prop('disabled', false).text('Generate with AI');
          }
        });
        return false;
      });

      $('#selfie').on('change', function () {
        if (this.files && this.files.length) {
          $('#selfie-upload-notice').slideDown(150);
        } else {
          $('#selfie-upload-notice').hide();
        }
      });

      window.syncDoctorProfileHidden = function() {
        var edu = [];
        $('#edu_rows .doctor-profile-card').each(function() {
          var $r = $(this);
          edu.push({
            degree: ($r.find('.edu-degree').val() || '').trim(),
            institution: ($r.find('.edu-inst').val() || '').trim(),
            year_completed: ($r.find('.edu-year').val() || '').trim()
          });
        });
        var exp = [];
        $('#exp_rows .doctor-profile-card').each(function() {
          var $r = $(this);
          exp.push({
            title: ($r.find('.exp-title').val() || '').trim(),
            organization: ($r.find('.exp-org').val() || '').trim(),
            from_year: ($r.find('.exp-from').val() || '').trim(),
            to_year: ($r.find('.exp-to').val() || '').trim(),
            details: ($r.find('.exp-details').val() || '').trim()
          });
        });
        syncSubServiceTagsFromDom();
        var subPayload = [];
        Object.keys(selectedSubServices).forEach(function (sid) {
          var entry = selectedSubServices[sid];
          var tags = Array.isArray(entry.tags) ? entry.tags.slice() : [];
          subPayload.push({
            sub_service_id: parseInt(sid, 10),
            sub_service_name: entry.name,
            tags: tags
          });
        });
        var specs = $('.spec-cb:checked').map(function () {
          return String($(this).val() || '').trim();
        }).get().filter(function (t) { return t !== ''; });
        if (subPayload.length) {
          subPayload.forEach(function (row) {
            row.tags.forEach(function (t) {
              if (specs.indexOf(t) === -1) specs.push(t);
            });
          });
        }
        $('#education_history_json').val(JSON.stringify(edu));
        $('#experience_history_json').val(JSON.stringify(exp));
        $('#specializations_json').val(JSON.stringify(specs));
        $('#consultation_sub_services_json').val(JSON.stringify(subPayload));
        syncPricingStateFromInputs();
      };
    });
  </script>
  @endif
</body>
</html>


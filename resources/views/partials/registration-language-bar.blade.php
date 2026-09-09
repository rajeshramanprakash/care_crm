@php
    $registrationLanguages = $registration_languages ?? collect();
@endphp
@if($registrationLanguages->count() > 0)
<style>
    .reg-lang-bar {
        width: 100%;
        max-width: 450px;
        margin: 0 auto 1.25rem auto;
        padding: 0.85rem 1rem;
        background: linear-gradient(135deg, #fff7ed 0%, #fff 100%);
        border: 1px solid #fed7aa;
        border-radius: 12px;
        box-shadow: 0 4px 14px rgba(234, 138, 43, 0.08);
    }
    .reg-lang-bar label {
        font-weight: 700;
        color: #9a3412;
        font-size: 0.88rem;
        margin-bottom: 0.35rem;
        display: block;
    }
    .reg-lang-bar select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #fdba74;
        border-radius: 8px;
        font-size: 0.95rem;
        background: #fff;
        color: #1a2340;
    }
    .reg-lang-bar select:focus {
        border-color: #ea8a2b;
        outline: none;
        box-shadow: 0 0 0 2px rgba(234, 138, 43, 0.2);
    }
    .register-form.doctor-register-form ~ .reg-lang-bar,
    .register-form.vendor-register-form ~ .reg-lang-bar,
    .register-form.freelancer-register-form ~ .reg-lang-bar {
        max-width: 520px;
    }
    .register-form.doctor-register-form + .reg-lang-bar,
    .reg-lang-bar--wide { max-width: 560px; }
</style>
<div class="reg-lang-bar @if(isset($type) && $type === 'doctor') reg-lang-bar--wide @endif" id="registrationLanguageBar">
    <label for="registrationLanguageSelect" data-i18n="lang.select">Language</label>
    <select id="registrationLanguageSelect" aria-label="Select form language">
        <option value="" data-i18n="lang.select_placeholder">Select language</option>
        @foreach($registrationLanguages as $lang)
            <option value="{{ $lang->code }}">{{ $lang->displayLabel() }}</option>
        @endforeach
    </select>
</div>
@endif

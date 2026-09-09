/**
 * Registration form language switcher (admin-configured languages only).
 */
(function (window, document) {
    'use strict';

    var config = window.registrationI18nConfig || {};
    var translationsUrl = config.translationsUrl || '';
    var storageKey = 'carelix_registration_lang';
    var currentDict = {};
    var registrationType = config.registrationType || '';

    var labelFieldMap = {
        customer_name: 'label.customer_name',
        contact_no: 'label.contact_no',
        name: 'label.name',
        age: 'label.age',
        gender: 'label.gender',
        location: 'label.location',
        address: 'label.address',
        total_experience: 'label.total_experience',
        job_title: 'label.job_title',
        shift: 'label.shift',
        aadhar_card: 'label.aadhar',
        pan_card: 'label.pan',
        qualification_certificate: 'label.qualification',
        account_name: 'label.account_name',
        account_number: 'label.account_number',
        ifsc_code: 'label.ifsc',
        upi_id: 'label.upi',
        bank_document: 'label.bank_document',
        doctor_email: 'label.email',
        doctor_mobile_otp: 'otp.enter',
        doctor_gender: 'label.gender',
        selfie: 'label.selfie',
        doctor_city: 'label.city',
        doctor_consultation_service: 'label.consultation_service',
        about_text: 'label.about',
        permanent_address: 'doctor.address.permanent',
        current_address: 'doctor.address.current',
        bank_name: 'doctor.bank.name',
        qualification_certificate: 'doctor.doc.degree',
        coverage_radius_km: 'doctor.mode.coverage',
        base_location_address: 'doctor.mode.base_location',
        clinic_name: 'doctor.mode.clinic_name',
        clinic_address: 'doctor.mode.clinic_address'
    };

    var placeholderMap = {
        total_experience: 'placeholder.experience',
        name: 'placeholder.full_name',
        contact_no: 'placeholder.mobile',
        doctor_mobile_otp: 'otp.placeholder',
        doctor_email: 'placeholder.email',
        about_text: 'doctor.about.placeholder',
        permanent_address: 'doctor.address.permanent_ph',
        current_address: 'doctor.address.current_ph',
        account_name: 'doctor.bank.holder_ph',
        bank_name: 'doctor.bank.name_ph',
        ifsc_code: 'doctor.bank.ifsc_ph',
        coverage_radius_km: 'doctor.mode.coverage_ph',
        base_location_address: 'doctor.mode.base_ph',
        clinic_name: 'doctor.mode.clinic_name_ph',
        clinic_address: 'doctor.mode.clinic_ph'
    };

    function t(key, fallback) {
        if (currentDict[key]) {
            return currentDict[key];
        }
        return fallback || key;
    }

    function replaceTypeToken(text) {
        if (!text) {
            return text;
        }
        if (text.indexOf(':type') !== -1) {
            var typeLabel = (config.registrationType || '').charAt(0).toUpperCase() + (config.registrationType || '').slice(1);
            text = text.replace(/:type/g, typeLabel);
        }
        return text;
    }

    function replaceNTokens(text, n) {
        text = replaceTypeToken(text);
        if (text && text.indexOf(':n') !== -1 && n !== undefined && n !== null) {
            text = text.replace(/:n/g, String(n));
        }
        return text;
    }

    function setLabelText(label, text, keepRequired) {
        var req = label.querySelector('.required');
        var suffix = keepRequired !== false && req ? ' <span class="required">*</span>' : '';
        var fileHint = label.textContent.indexOf('(Image/PDF)') !== -1 || label.textContent.indexOf('(Optional)') !== -1;
        if (fileHint && text.indexOf('(') === -1) {
            var isOptional = label.textContent.indexOf('Optional') !== -1;
            text += isOptional ? ' (Optional) ' + t('label.file_image_pdf', '(Image/PDF)') : ' ' + t('label.file_image_pdf', '(Image/PDF)');
        }
        label.innerHTML = text + suffix;
    }

    function applyDataI18n() {
        document.querySelectorAll('[data-i18n]').forEach(function (el) {
            var key = el.getAttribute('data-i18n');
            if (!key || !currentDict[key]) {
                return;
            }
            var val = replaceTypeToken(currentDict[key]);
            if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') {
                return;
            }
            if (el.tagName === 'OPTION') {
                el.textContent = val;
                return;
            }
            el.textContent = val;
        });

        document.querySelectorAll('[data-i18n-placeholder]').forEach(function (el) {
            var key = el.getAttribute('data-i18n-placeholder');
            if (key && currentDict[key]) {
                el.setAttribute('placeholder', replaceTypeToken(currentDict[key]));
            }
        });

        document.querySelectorAll('[data-i18n-html]').forEach(function (el) {
            var key = el.getAttribute('data-i18n-html');
            if (key && currentDict[key]) {
                el.innerHTML = replaceTypeToken(currentDict[key]);
            }
        });
    }

    function applyLabelFields() {
        Object.keys(labelFieldMap).forEach(function (fieldId) {
            var key = labelFieldMap[fieldId];
            if (fieldId === 'name' && registrationType === 'doctor') {
                key = 'label.full_name';
            }
            if (fieldId === 'contact_no' && registrationType === 'doctor') {
                key = 'label.mobile_number';
            }
            var label = document.querySelector('label[for="' + fieldId + '"]');
            if (!label || !currentDict[key] || label.querySelector('[data-i18n]')) {
                return;
            }
            setLabelText(label, currentDict[key]);
        });

        Object.keys(placeholderMap).forEach(function (fieldId) {
            var key = placeholderMap[fieldId];
            var input = document.getElementById(fieldId);
            if (input && currentDict[key]) {
                input.setAttribute('placeholder', currentDict[key]);
            }
        });
    }

    function applySelectOptions() {
        var gender = document.getElementById('gender') || document.getElementById('doctor_gender');
        if (gender && gender.options.length) {
            if (gender.options[0]) gender.options[0].text = t('option.select_gender', gender.options[0].text);
            if (gender.options[1]) gender.options[1].text = t('option.male', 'Male');
            if (gender.options[2]) gender.options[2].text = t('option.female', 'Female');
            if (gender.options[3]) gender.options[3].text = t('option.other', 'Other');
        }

        var doctorCity = document.getElementById('doctor_city');
        if (doctorCity && doctorCity.options.length && doctorCity.options[0]) {
            doctorCity.options[0].text = t('doctor.city.placeholder', doctorCity.options[0].text);
        }

        var doctorSvc = document.getElementById('doctor_consultation_service');
        if (doctorSvc && doctorSvc.options.length && doctorSvc.options[0]) {
            doctorSvc.options[0].text = t('doctor.consultation.placeholder', doctorSvc.options[0].text);
        }

        var subAdd = document.getElementById('sub_service_add_select');
        if (subAdd && subAdd.options.length && subAdd.options[0]) {
            subAdd.options[0].text = t('doctor.sub_service.placeholder', subAdd.options[0].text);
        }

        var location = document.getElementById('location');
        if (location && location.options.length && location.options[0]) {
            location.options[0].text = t('option.select_location', location.options[0].text);
        }

        var shift = document.getElementById('shift');
        if (shift && shift.options.length >= 5) {
            shift.options[0].text = t('option.shift.select', shift.options[0].text);
            shift.options[1].text = t('option.shift.12', '12 Hours');
            shift.options[2].text = t('option.shift.24', '24 Hours');
            shift.options[3].text = t('option.shift.both', 'Both');
            shift.options[4].text = t('option.shift.onetime', 'One-time');
        }

        var submitBtn = document.querySelector('#registerForm button[type="submit"]');
        if (submitBtn) {
            submitBtn.textContent = t('btn.submit', submitBtn.textContent);
        }

        var jobTitle = document.getElementById('job_title');
        if (jobTitle && jobTitle.options.length && jobTitle.options[0] && currentDict['hint.select_location_first']) {
            if (jobTitle.disabled || jobTitle.options[0].value === '') {
                jobTitle.options[0].text = currentDict['hint.select_location_first'];
            }
        }
    }

    function applyDoctorProfileCards() {
        var eduKey = currentDict['doctor.edu.card_title'];
        var expKey = currentDict['doctor.exp.card_title'];
        if (!eduKey && !expKey) {
            return;
        }

        document.querySelectorAll('#edu_rows .doctor-profile-card').forEach(function (card, idx) {
            var n = idx + 1;
            if (eduKey) {
                var titleSpan = card.querySelector('.doctor-profile-card__title span:last-child');
                if (titleSpan) {
                    titleSpan.textContent = replaceNTokens(eduKey, n);
                }
            }
            applyDoctorCardLabels(card, 'edu');
        });

        document.querySelectorAll('#exp_rows .doctor-profile-card').forEach(function (card, idx) {
            var n = idx + 1;
            if (expKey) {
                var titleSpan = card.querySelector('.doctor-profile-card__title span:last-child');
                if (titleSpan) {
                    titleSpan.textContent = replaceNTokens(expKey, n);
                }
            }
            applyDoctorCardLabels(card, 'exp');
        });
    }

    function applyDoctorCardLabels(card, kind) {
        var prefix = kind === 'edu' ? 'doctor.edu.' : 'doctor.exp.';
        var map = kind === 'edu'
            ? [
                ['.edu-degree', prefix + 'degree', prefix + 'degree_ph'],
                ['.edu-inst', prefix + 'institution', prefix + 'inst_ph'],
                ['.edu-year', prefix + 'year', prefix + 'year_ph']
            ]
            : [
                ['.exp-title', prefix + 'role', prefix + 'role_ph'],
                ['.exp-org', prefix + 'organization', prefix + 'org_ph'],
                ['.exp-from', prefix + 'from', prefix + 'from_ph'],
                ['.exp-to', prefix + 'to', prefix + 'to_ph'],
                ['.exp-details', prefix + 'details', prefix + 'details_ph']
            ];

        map.forEach(function (row) {
            var input = card.querySelector(row[0]);
            if (!input) return;
            var label = input.closest('.doctor-profile-field');
            if (!label) return;
            var labelEl = label.querySelector('label');
            if (labelEl && currentDict[row[1]]) {
                var optKey = kind === 'edu' && row[1].indexOf('year') !== -1
                    ? 'doctor.edu.year_optional'
                    : (kind === 'exp' && row[1] === 'doctor.exp.to' ? 'doctor.exp.to_optional' : (kind === 'exp' && row[1] === 'doctor.exp.details' ? 'doctor.edu.year_optional' : null));
                var req = labelEl.querySelector('.required');
                var opt = labelEl.querySelector('.optional');
                var labelText = currentDict[row[1]];
                if (req) {
                    labelEl.innerHTML = labelText + ' <span class="required">*</span>';
                } else if (opt && optKey && currentDict[optKey]) {
                    labelEl.innerHTML = labelText + ' <span class="optional">' + currentDict[optKey] + '</span>';
                } else {
                    labelEl.textContent = labelText;
                }
            }
            if (currentDict[row[2]]) {
                input.setAttribute('placeholder', currentDict[row[2]]);
            }
        });

        var removeBtn = card.querySelector('.doctor-profile-card__remove');
        if (removeBtn && currentDict[kind === 'edu' ? 'doctor.edu.remove' : 'doctor.exp.remove']) {
            removeBtn.textContent = currentDict[kind === 'edu' ? 'doctor.edu.remove' : 'doctor.exp.remove'];
        }
    }

    function applyDoctorOtpButtons() {
        var sendBtn = document.getElementById('doctorMobileSendOtpBtn');
        if (sendBtn && sendBtn.offsetParent !== null && !sendBtn.disabled) {
            var txt = sendBtn.textContent || '';
            if (txt.indexOf('…') === -1 && txt.toLowerCase().indexOf('send') !== -1 && txt.toLowerCase().indexOf('resend') === -1) {
                sendBtn.textContent = t('otp.send', sendBtn.textContent);
            } else if (txt.toLowerCase().indexOf('resend') !== -1) {
                sendBtn.textContent = t('otp.resend', sendBtn.textContent);
            }
        }
        var verifyBtn = document.getElementById('doctorMobileVerifyOtpBtn');
        if (verifyBtn && !verifyBtn.disabled && verifyBtn.textContent) {
            var vtxt = verifyBtn.textContent;
            if (vtxt.indexOf('…') === -1 && vtxt.toLowerCase().indexOf('verif') !== -1) {
                verifyBtn.textContent = t('otp.verify', verifyBtn.textContent);
            }
        }
    }

    function applyDoctorRegistration() {
        if (config.registrationType !== 'doctor') {
            return;
        }
        applyDoctorProfileCards();
        applyDoctorOtpButtons();
    }

    function applyPickerPage() {
        var map = {
            'picker.type.customer': '.type-card[data-type="customer"] h4',
            'picker.type.customer_desc': '.type-card[data-type="customer"] p',
            'picker.type.vendor': '.type-card[data-type="vendor"] h4',
            'picker.type.vendor_desc': '.type-card[data-type="vendor"] p',
            'picker.type.freelancer': '.type-card[data-type="freelancer"] h4',
            'picker.type.freelancer_desc': '.type-card[data-type="freelancer"] p',
            'picker.type.doctor': '.type-card[data-type="doctor"] h4',
            'picker.type.doctor_desc': '.type-card[data-type="doctor"] p'
        };
        Object.keys(map).forEach(function (key) {
            var el = document.querySelector(map[key]);
            if (el && currentDict[key]) {
                el.textContent = currentDict[key];
            }
        });
    }

    function applyAll() {
        applyDataI18n();
        applyLabelFields();
        applySelectOptions();
        applyPickerPage();
        applyDoctorRegistration();
        document.documentElement.lang = config.currentCode || 'en';
    }

    function loadLanguage(code) {
        if (!code || !translationsUrl) {
            return Promise.resolve();
        }
        var url = translationsUrl.replace('__CODE__', encodeURIComponent(code));
        return fetch(url, { headers: { Accept: 'application/json' } })
            .then(function (r) {
                if (!r.ok) throw new Error('Failed to load translations');
                return r.json();
            })
            .then(function (data) {
                currentDict = data.translations || {};
                config.currentCode = data.code || code;
                try {
                    localStorage.setItem(storageKey, code);
                } catch (e) {}
                applyAll();
                window.dispatchEvent(new CustomEvent('registrationLanguageChanged', { detail: { code: code, translations: currentDict } }));
            })
            .catch(function (err) {
                console.error('Registration i18n:', err);
            });
    }

    function init() {
        var select = document.getElementById('registrationLanguageSelect');
        if (!select) {
            return;
        }

        var saved = '';
        try {
            saved = localStorage.getItem(storageKey) || '';
        } catch (e) {}

        if (saved) {
            var has = Array.prototype.some.call(select.options, function (o) { return o.value === saved; });
            if (has) {
                select.value = saved;
                loadLanguage(saved);
            }
        }

        select.addEventListener('change', function () {
            var code = select.value;
            if (!code) {
                currentDict = {};
                applyAll();
                try { localStorage.removeItem(storageKey); } catch (e) {}
                return;
            }
            loadLanguage(code);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.registrationI18n = {
        t: t,
        applyAll: applyAll,
        loadLanguage: loadLanguage,
        getDict: function () { return currentDict; },
        replaceNTokens: replaceNTokens,
        applyDoctorProfileCards: applyDoctorProfileCards
    };
})(window, document);

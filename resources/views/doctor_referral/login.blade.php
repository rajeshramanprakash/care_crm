<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Doctor Referral Login - Carelix</title>
    <link rel="shortcut icon" href="{{ asset('favicon.jpg') }}" type="image/x-icon">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:400,600,700&display=swap">
    <link rel="stylesheet" href="{{ asset('plugins/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/css/adminlte.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/toastr/toastr.min.css') }}">
    @include('partials.carelix-partner-login-styles')
    <style>
        .drp-login-badge {
            display: inline-block;
            margin-top: 14px;
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.35);
            font-size: 0.82rem;
            font-weight: 700;
        }
        .drp-login-hint {
            font-size: 0.92rem;
            color: #64748b;
            margin-bottom: 1rem;
            line-height: 1.45;
        }
    </style>
</head>
<body>
<div class="contain">
    <div class="left">
        <div class="logo"><img src="{{ asset('images/carelix-logo.png') }}" alt="Carelix"></div>
        <h2 style="margin-top: 24px; text-align: center;">Doctor Referral Portal</h2>
        <span class="drp-login-badge"><i class="fas fa-user-md mr-1"></i> Referral partner login</span>
        <p style="text-align: center; max-width: 360px; line-height: 1.6; opacity: 0.95; margin-top: 16px;">
            Track assigned doctors, consultation leads, booking amounts, and your commission earnings.
        </p>
    </div>
    <div class="right">
        <div class="login-box">
            <div class="login-title">Doctor Referral Login</div>
            <p class="drp-login-hint">Enter the mobile number registered by Carelix admin. OTP will be sent on WhatsApp.</p>

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            @if(session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            <form id="drpLoginForm" method="POST">
                @csrf
                <input type="hidden" name="login_type" id="login_type" value="doctor_referral">
                <div class="form-group">
                    <label for="mobile">Mobile number</label>
                    <input type="tel" name="mobile" id="mobile" class="form-control" placeholder="10-digit mobile" required maxlength="14" autocomplete="tel">
                </div>
                <div class="form-group" id="otpSection" style="display: none;">
                    <label for="otp">OTP</label>
                    <input type="text" name="otp" id="otp" class="form-control" placeholder="6-digit OTP" maxlength="6" inputmode="numeric">
                </div>
                <button type="button" id="sendOtpBtn" class="btn btn-primary btn-block btn-login">Send OTP</button>
                <button type="submit" id="loginBtn" class="btn btn-primary btn-block btn-login" style="display: none;">Login</button>
            </form>
        </div>
    </div>
</div>
<script src="{{ asset('plugins/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('plugins/toastr/toastr.min.js') }}"></script>
<script>
$(function () {
    function normalizeMobile(input) {
        var digits = String(input || '').replace(/\D/g, '');
        return digits.length >= 10 ? digits.slice(-10) : null;
    }

    $('#mobile').on('input', function () {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 14);
    });
    $('#otp').on('input', function () {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
    });

    $('#sendOtpBtn').on('click', function () {
        var mobile = normalizeMobile($('#mobile').val());
        if (!mobile) {
            toastr.error('Please enter a valid 10-digit mobile number');
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).text('Sending OTP...');

        $.ajax({
            url: '{{ route("send.otp") }}',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}', mobile: mobile },
            success: function (res) {
                if (!res.success) {
                    toastr.error(res.message || 'Failed to send OTP');
                    $btn.prop('disabled', false).text('Send OTP');
                    return;
                }
                if (res.login_type !== 'doctor_referral') {
                    toastr.error('This mobile is not registered as a doctor referral user.');
                    $btn.prop('disabled', false).text('Send OTP');
                    return;
                }
                toastr.success('OTP sent to your WhatsApp number');
                $('#otpSection').slideDown();
                $('#otp').prop('required', true);
                $('#loginBtn').show();
                $btn.hide();
                $('#mobile').prop('readonly', true);
            },
            error: function (xhr) {
                toastr.error((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to send OTP');
                $btn.prop('disabled', false).text('Send OTP');
            }
        });
    });

    $('#drpLoginForm').on('submit', function (e) {
        e.preventDefault();
        if (!$('#otpSection').is(':visible')) {
            $('#sendOtpBtn').click();
            return;
        }

        var mobile = normalizeMobile($('#mobile').val());
        var otp = $('#otp').val().trim();
        if (!mobile || !otp || otp.length !== 6) {
            toastr.error('Please enter a valid 6-digit OTP');
            return;
        }

        var $btn = $('#loginBtn');
        $btn.prop('disabled', true).text('Logging in...');

        $.ajax({
            url: '{{ route("login") }}',
            type: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            data: {
                _token: '{{ csrf_token() }}',
                mobile: mobile,
                otp: otp,
                login_type: 'doctor_referral'
            },
            success: function (res) {
                if (res.redirect) {
                    window.location.href = res.redirect;
                    return;
                }
                window.location.reload();
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Login failed';
                toastr.error(msg);
                $btn.prop('disabled', false).text('Login');
            }
        });
    });
});
</script>
</body>
</html>

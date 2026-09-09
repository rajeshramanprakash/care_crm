<script>
$(function() {
  var prefix = @json($otpPrefix);
  var verifiedKey = prefix + 'MobileVerified';
  window[verifiedKey] = false;
  var otpCsrf = $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val();
  var $sendBtn = $('#' + prefix + 'MobileSendOtpBtn');
  var $verifyBtn = $('#' + prefix + 'MobileVerifyOtpBtn');

  function resetVerification() {
    window[verifiedKey] = false;
    $('#' + prefix + '_mobile_verified_flag').val('0');
    $('#contact_no').prop('readonly', false).removeClass('reg-mobile-verified-lock');
    $('#' + prefix + '_mobile_otp_wrap').hide();
    $('#' + prefix + '_mobile_otp').val('');
    $('#' + prefix + '_mobile_verified_badge').hide();
    $sendBtn.show().prop('disabled', false).text('Send OTP');
    $verifyBtn.prop('disabled', false).text('Verify');
  }

  function setVerifiedUi() {
    window[verifiedKey] = true;
    $('#' + prefix + '_mobile_verified_flag').val('1');
    $('#contact_no').prop('readonly', true).addClass('reg-mobile-verified-lock');
    $('#' + prefix + '_mobile_verified_badge').show();
    $sendBtn.hide();
    $verifyBtn.prop('disabled', true).text('Verified');
  }

  $('#contact_no').on('input', function() {
    if (window[verifiedKey] || $('#' + prefix + '_mobile_otp_wrap').is(':visible')) {
      resetVerification();
    }
  });

  $sendBtn.on('click', function() {
    var mobile = String($('#contact_no').val() || '').replace(/\D/g, '');
    if (mobile.length !== 10) {
      toastr.warning('Enter a valid 10-digit mobile number first.');
      return;
    }
    var $btn = $(this);
    $btn.prop('disabled', true).text('Sending…');
    $.ajax({
      url: @json($sendOtpRoute),
      type: 'POST',
      data: { mobile: mobile, _token: otpCsrf },
      success: function(res) {
        if (res.success) {
          toastr.success(res.message || 'OTP sent.');
          $('#' + prefix + '_mobile_otp_wrap').slideDown();
          $('#' + prefix + '_mobile_otp').focus();
          $btn.text('Resend OTP').prop('disabled', false);
          if (res.meta && res.meta.otp) {
            console.log(prefix + ' reg OTP (debug):', res.meta);
          }
        } else {
          toastr.error(res.message || 'Could not send OTP.');
          $btn.prop('disabled', false).text('Send OTP');
        }
      },
      error: function(xhr) {
        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Could not send OTP.';
        toastr.error(msg);
        $btn.prop('disabled', false).text('Send OTP');
      }
    });
  });

  $verifyBtn.on('click', function() {
    var mobile = String($('#contact_no').val() || '').replace(/\D/g, '');
    var otp = String($('#' + prefix + '_mobile_otp').val() || '').replace(/\D/g, '');
    if (mobile.length !== 10) {
      toastr.warning('Enter a valid mobile number.');
      return;
    }
    if (otp.length !== 6) {
      toastr.warning('Enter the 6-digit OTP.');
      return;
    }
    var $btn = $(this);
    $btn.prop('disabled', true).text('Verifying…');
    $.ajax({
      url: @json($verifyOtpRoute),
      type: 'POST',
      data: { mobile: mobile, otp: otp, _token: otpCsrf },
      success: function(res) {
        if (res.success) {
          toastr.success(res.message || 'Mobile verified.');
          setVerifiedUi();
        } else {
          toastr.error(res.message || 'Invalid OTP.');
          $btn.prop('disabled', false).text('Verify');
        }
      },
      error: function(xhr) {
        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Verification failed.';
        toastr.error(msg);
        $btn.prop('disabled', false).text('Verify');
      }
    });
  });

  $('#' + prefix + '_mobile_otp').on('input', function() {
    this.value = this.value.replace(/[^0-9]/g, '');
  });
});
</script>

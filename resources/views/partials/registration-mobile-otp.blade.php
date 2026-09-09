{{-- Contact No OTP block: $otpPrefix (doctor|vendor|freelancer), $sendOtpRoute, $verifyOtpRoute --}}
<div class="reg-mobile-verify-row">
  <input type="tel" name="contact_no" id="contact_no" maxlength="10" pattern="[0-9]{10}" required placeholder="10-digit mobile number" inputmode="numeric" autocomplete="tel">
  <button type="button" class="reg-otp-btn" id="{{ $otpPrefix }}MobileSendOtpBtn" data-i18n="otp.send">Send OTP</button>
</div>
<input type="hidden" id="{{ $otpPrefix }}_mobile_verified_flag" name="mobile_verified" value="0">
<div id="{{ $otpPrefix }}_mobile_otp_wrap" class="reg-mobile-otp-wrap" style="display:none;">
  <label for="{{ $otpPrefix }}_mobile_otp" data-i18n="otp.enter">Enter OTP</label>
  <div class="reg-mobile-verify-row">
    <input type="text" id="{{ $otpPrefix }}_mobile_otp" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" data-i18n-placeholder="otp.placeholder" placeholder="6-digit OTP" autocomplete="one-time-code">
    <button type="button" class="reg-otp-btn reg-otp-btn--verify" id="{{ $otpPrefix }}MobileVerifyOtpBtn" data-i18n="otp.verify">Verify</button>
  </div>
  <span class="reg-mobile-verified-badge" id="{{ $otpPrefix }}_mobile_verified_badge" style="display:none;"><i class="fas fa-check-circle"></i> <span data-i18n="otp.verified">Mobile verified</span></span>
</div>

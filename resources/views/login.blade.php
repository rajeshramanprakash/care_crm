<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Carelix Healthcare Login</title>
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
    .testimonial-slider { max-width: 400px; min-height: 180px; margin-bottom: 40px; position: relative; }
    .testimonial {
      display: none;
      animation: fadein 0.5s;
    }
    .testimonial.active { display: block; }
    @keyframes fadein {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    .testimonial p { font-size: 1.1rem; margin-bottom: 10px; }
    .testimonial .author { font-weight: bold; margin-bottom: 2px; }
    .testimonial .role { font-size: 0.95rem; color: #ffe0b2; }
    .dots { margin: 20px 0; text-align: center; }
    .dots span {
      display: inline-block; width: 8px; height: 8px; margin: 0 4px;
      background: #fff; border-radius: 50%; opacity: 0.5; cursor: pointer;
      transition: opacity 0.2s;
    }
    .dots span.active { opacity: 1; background: #fff; }
    .welcome { max-width: 400px; font-size: 1.15rem; margin-top: 30px; line-height: 1.5; }
    .right { flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 40px 30px; background: #fff; }
    .logo { margin-bottom: 20px; text-align: center; }
    .logo img { max-width: 200px; margin-bottom: 0px; }
    .login-title { font-size: 1.4rem; font-weight: 700; margin-bottom: 10px; color: #222; }
    .login-form { width: 100%; max-width: 350px; margin: 0 auto; }
    .login-form label { font-weight: 600; margin-bottom: 5px; display: block; color: #222; }
    .login-form input { width: 100%; padding: 10px 12px; margin-bottom: 18px; border: 1px solid #ccc; border-radius: 4px; font-size: 1rem; }
    .login-form .row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; }
    .login-form .forgot { font-size: 0.95rem; color: #ea8a2b; text-decoration: none; }
    .login-form button { width: 100%; background: #ea8a2b; color: #fff; border: none; padding: 12px; font-size: 1.1rem; border-radius: 4px; font-weight: 600; cursor: pointer; margin-bottom: 10px; transition: background 0.2s; }
    .login-form button:hover { background: #d97a1a; }
    .login-form .signup { text-align: center; margin-top: 18px; font-size: 1rem; }
    .login-form .signup a { color: #ea8a2b; text-decoration: none; font-weight: 600; }
    @media (max-width: 900px) {
      .contain { flex-direction: column; }
      .left, .right { flex: unset; width: 100%; min-height: 350px; }
      .left { padding: 30px 10px; }
      .right { padding: 30px 10px; }
    }
  </style>
</head>
<body>
  <div class="contain">
    <div class="left">
      <div class="testimonial-slider" id="testimonial-slider">
        <div class="testimonial active">
          <p>"Cannot say enough good things about Carelix Home HealthCare! From the moment we reached out, their team was incredibly responsive and understanding. They arranged for a doctor to visit and provided us with a caring nurse and GDA who were both professional and kind. The personalised care plan made all the difference, and my family member felt safe and comfortable in their hands."</p>
          <div class="author">Harjit Thakur</div>
          <div class="role">Our Customer</div>
        </div>
        <div class="testimonial">
          <p>"Carelix ki service bahut hi acchi hai. Staff ne mere father ka bahut dhyan rakha. Main sabko recommend karunga."</p>
          <div class="author">Ramesh Kumar</div>
          <div class="role">Our Customer</div>
        </div>
        <div class="testimonial">
          <p>"Very professional and caring team. Timely response and excellent support throughout the treatment."</p>
          <div class="author">Priya Sharma</div>
          <div class="role">Our Customer</div>
        </div>
        <div class="testimonial">
          <p>"Affordable and reliable home healthcare. Thank you Carelix for your support in tough times."</p>
          <div class="author">Sunita Verma</div>
          <div class="role">Our Customer</div>
        </div>
      </div>
      <div class="dots" id="testimonial-dots">
        <span class="active"></span>
        <span></span>
        <span></span>
        <span></span>
      </div>
      <div class="welcome">
        Welcome to Carelix Home HealthCare, your reliable partner in providing top-quality healthcare services right at your doorstep. At Carelix, we believe that healthcare should be accessible, personalized, and delivered with compassion.
      </div>
    </div>
    <div class="right">
      <div class="logo">
        <img src="{{ asset('images/carelix-logo.png') }}" alt="Carelix Healthcare Logo">
      </div>
      <div class="login-title">Log In to your account</div>
      <div style="margin-bottom: 10px; color: #666; font-size: 1rem;">To continue where you left off, please enter your mobile number.</div>
      <form class="login-form" id="loginForm" method="POST">
        @csrf
        <label for="mobile">Mobile Number</label>
        <input type="tel" name="mobile" id="mobile" placeholder="Enter your mobile number" required maxlength="14" pattern="[0-9]{10,14}">
        <div id="otpSection" style="display: none;">
          <label for="otp">OTP</label>
          <input type="text" name="otp" id="otp" placeholder="Enter 6-digit OTP" maxlength="6" pattern="[0-9]{6}">
        </div>
        <button type="button" id="sendOtpBtn" style="width: 100%; background: #ea8a2b; color: #fff; border: none; padding: 12px; font-size: 1.1rem; border-radius: 4px; font-weight: 600; cursor: pointer; margin-bottom: 10px; transition: background 0.2s;" onmouseover="this.style.background='#d97a1a'" onmouseout="this.style.background='#ea8a2b'">Send OTP</button>
        <button type="submit" id="loginBtn" style="display: none; width: 100%; background: #ea8a2b; color: #fff; border: none; padding: 12px; font-size: 1.1rem; border-radius: 4px; font-weight: 600; cursor: pointer; margin-bottom: 10px; transition: background 0.2s;" onmouseover="this.style.background='#d97a1a'" onmouseout="this.style.background='#ea8a2b'">Login</button>
        <div class="signup">
          New to Carelix? <a href="{{ route('register') }}">Create Account</a>
        </div>
      </form>
      {{-- Laravel error messages --}}
      @if (session('error'))
        <div style="color: red; margin-top: 10px;">{{ session('error') }}</div>
      @endif
      @if ($errors->any())
        <div style="color: red; margin-top: 10px;">
          @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
          @endforeach
        </div>
      @endif
    </div>
  </div>
  <script src="{{ asset('plugins/jquery/jquery.min.js') }}"></script>
  <script src="{{ asset('adminlte/js/adminlte.js') }}"></script>
  <script src="{{ asset('plugins/toastr/toastr.min.js') }}"></script>
  <script>
    // Testimonial slider logic
    const testimonials = document.querySelectorAll('#testimonial-slider .testimonial');
    const dots = document.querySelectorAll('#testimonial-dots span');
    let current = 0;
    function showTestimonial(idx) {
      testimonials.forEach((t, i) => {
        t.classList.toggle('active', i === idx);
        dots[i].classList.toggle('active', i === idx);
      });
      current = idx;
    }
    dots.forEach((dot, idx) => {
      dot.addEventListener('click', () => showTestimonial(idx));
    });
    setInterval(() => {
      let next = (current + 1) % testimonials.length;
      showTestimonial(next);
    }, 4000);

    // Toastr error
    $(document).ready(function() {
      @if (session('error'))
        toastr.error("{{ session('error') }}");
      @endif

      function normalizeMobileToTenDigits(input) {
        var digits = String(input || '').replace(/\D/g, '');
        if (!digits || digits.length < 10) {
          return null;
        }
        return digits.slice(-10);
      }

      // Send OTP functionality
      $('#sendOtpBtn').on('click', function() {
        var rawMobile = $('#mobile').val().trim();
        var mobile = normalizeMobileToTenDigits(rawMobile);

        if (!mobile) {
          toastr.error('Please enter at least 10 digits in mobile number');
          return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).text('Sending OTP...');

        $.ajax({
          url: '{{ route("send.otp") }}',
          type: 'POST',
          data: {
            _token: '{{ csrf_token() }}',
            mobile: mobile
          },
          success: function(response) {
            if (response.success) {
              toastr.success('OTP sent to your WhatsApp number');
              $('#otpSection').slideDown();
              $('#otp').prop('required', true); // Add required attribute when showing OTP field
              $('#loginBtn').show();
              $('#sendOtpBtn').hide();
              $('#mobile').prop('readonly', true);
              // Store detected login type for later use
              if (response.login_type) {
                $('#loginForm').append('<input type="hidden" name="login_type" id="login_type" value="' + response.login_type + '">');
              }
            } else {
              toastr.error(response.message || 'Failed to send OTP');
              $btn.prop('disabled', false).text('Send OTP');
            }
          },
          error: function(xhr) {
            var errorMsg = 'Failed to send OTP';
            if (xhr.responseJSON && xhr.responseJSON.message) {
              errorMsg = xhr.responseJSON.message;
            }
            toastr.error(errorMsg);
            $btn.prop('disabled', false).text('Send OTP');
          }
        });
      });

      // Form submission
      $('#loginForm').on('submit', function(e) {
        e.preventDefault();
        
        var mobile = $('#mobile').val().trim();
        mobile = normalizeMobileToTenDigits(mobile);
        var otp = $('#otp').val().trim();
        var loginType = $('#login_type').val();
        var otpSectionVisible = $('#otpSection').is(':visible');

        // Validate mobile number
        if (!mobile) {
          toastr.error('Please enter at least 10 digits in mobile number');
          return;
        }

        // Only validate OTP if OTP section is visible
        if (otpSectionVisible) {
          if (!otp || otp.length !== 6 || !/^[0-9]{6}$/.test(otp)) {
            toastr.error('Please enter a valid 6-digit OTP');
          return;
        }

        if (!loginType) {
          toastr.error('Login type not detected. Please try sending OTP again.');
            return;
          }
        } else {
          // If OTP section is not visible, trigger send OTP instead
          $('#sendOtpBtn').click();
          return;
        }

        var $btn = $('#loginBtn');
        $btn.prop('disabled', true).text('Logging in...');

        $.ajax({
          url: '{{ route("login") }}',
          type: 'POST',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
          },
          data: {
            _token: '{{ csrf_token() }}',
            mobile: mobile,
            otp: otp,
            login_type: loginType
          },
          success: function(response) {
            if (response.success && response.redirect) {
              window.location.href = response.redirect;
            } else if (response.redirect) {
              window.location.href = response.redirect;
            } else {
              window.location.reload();
            }
          },
          error: function(xhr) {
            var errorMsg = 'Login failed';
            if (xhr.responseJSON && xhr.responseJSON.message) {
              errorMsg = xhr.responseJSON.message;
            } else if (xhr.status === 302 && xhr.responseJSON && xhr.responseJSON.redirect) {
              window.location.href = xhr.responseJSON.redirect;
              return;
            } else if (xhr.responseJSON && xhr.responseJSON.errors) {
              // Handle validation errors
              var firstError = Object.values(xhr.responseJSON.errors)[0];
              if (Array.isArray(firstError)) {
                errorMsg = firstError[0];
              } else {
                errorMsg = firstError;
              }
            }
            toastr.error(errorMsg);
            $btn.prop('disabled', false).text('Login');
          }
        });
      });

      // Allow only numbers in mobile input
      $('#mobile').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 14);
      });

      // Allow only numbers in OTP input
      $('#otp').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
      });
    });
  </script>
</body>
</html>

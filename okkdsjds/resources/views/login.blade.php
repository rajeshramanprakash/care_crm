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
      <div style="margin-bottom: 10px; color: #666; font-size: 1rem;">To continue where you left off, please enter your details.</div>
      <form class="login-form" action="{{ url('login') }}" method="POST">
        @csrf
        <label for="email">Email</label>
        <input type="email" name="email" id="email" placeholder="Your Email" required>
        <div class="row">
          <label for="password" style="margin-bottom: 0;">Password</label>
        </div>
        <input type="password" name="password" id="password" placeholder="Your Password" required>
        <button type="submit">Login</button>
        <div class="signup">
          New to Carelix CRM? <a href="{{ url('register') }}">Sign On</a>
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
    });
  </script>
</body>
</html>

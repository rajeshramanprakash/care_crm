<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Create Account - Carelix CRM</title>
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
    .right { flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 40px 30px; background: #fff; }
    .logo { margin-bottom: 20px; text-align: center; }
    .logo img { max-width: 200px; margin-bottom: 0px; }
    .register-title { font-size: 1.4rem; font-weight: 700; margin-bottom: 10px; color: #222; }
    .register-form { width: 100%; max-width: 400px; margin: 0 auto; }
    .type-card {
      border: 2px solid #e5e7eb;
      border-radius: 8px;
      padding: 20px;
      margin-bottom: 15px;
      cursor: pointer;
      transition: all 0.3s;
      text-align: center;
    }
    .type-card:hover {
      border-color: #ea8a2b;
      background: #fff5eb;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(234, 138, 43, 0.2);
    }
    .type-card.selected {
      border-color: #ea8a2b;
      background: #fff5eb;
    }
    .type-card i {
      font-size: 2.5rem;
      color: #ea8a2b;
      margin-bottom: 10px;
    }
    .type-card h4 {
      margin: 10px 0 5px 0;
      color: #222;
    }
    .type-card p {
      margin: 0;
      color: #666;
      font-size: 0.9rem;
    }
    .back-link {
      text-align: center;
      margin-top: 20px;
    }
    .back-link a {
      color: #ea8a2b;
      text-decoration: none;
      font-weight: 600;
    }
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
      <div class="logo">
        <img src="{{ asset('images/carelix-logo.png') }}" alt="Carelix Healthcare Logo">
      </div>
      <h2 style="text-align: center; margin-top: 20px;">Join Carelix</h2>
      <p style="text-align: center; max-width: 400px; margin-top: 10px; line-height: 1.6;">
        Create your account and get access to our healthcare services. Choose the account type that best fits your needs.
      </p>
    </div>
    <div class="right">
      <div class="register-title">Create Your Account</div>
      <div style="margin-bottom: 20px; color: #666; font-size: 1rem;">Select your account type to get started</div>
      
      <div class="register-form">
        <div class="type-card" data-type="customer">
          <i class="fas fa-user"></i>
          <h4>Customer</h4>
          <p>I need healthcare services</p>
        </div>
        
        <div class="type-card" data-type="vendor">
          <i class="fas fa-store"></i>
          <h4>Vendor</h4>
          <p>I provide healthcare services</p>
        </div>
        
        <div class="type-card" data-type="freelancer">
          <i class="fas fa-user-tie"></i>
          <h4>Freelancer</h4>
          <p>I'm a healthcare professional</p>
        </div>

        <div class="type-card" data-type="doctor">
          <i class="fas fa-user-md"></i>
          <h4>Doctor</h4>
          <p>I'm a registered medical practitioner</p>
        </div>
        
        <div class="back-link">
          <a href="{{ route('home') }}"><i class="fas fa-arrow-left"></i> Back to Login</a>
        </div>
      </div>
    </div>
  </div>
  
  <script src="{{ asset('plugins/jquery/jquery.min.js') }}"></script>
  <script src="{{ asset('plugins/toastr/toastr.min.js') }}"></script>
  <script>
    $(document).ready(function() {
      $('.type-card').on('click', function() {
        var type = $(this).data('type');
        window.location.href = '{{ route("register.form", ":type") }}'.replace(':type', type);
      });
    });
  </script>
</body>
</html>


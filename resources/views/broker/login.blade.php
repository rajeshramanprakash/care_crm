<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Broker Login - Carelix</title>
    <link rel="shortcut icon" href="{{ asset('favicon.jpg') }}" type="image/x-icon">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:400,600,700&display=swap">
    <link rel="stylesheet" href="{{ asset('plugins/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/css/adminlte.min.css') }}">
    @include('partials.carelix-partner-login-styles')
</head>
<body>
<div class="contain">
    <div class="left">
        <div class="logo"><img src="{{ asset('images/carelix-logo.png') }}" alt="Carelix"></div>
        <h2 style="margin-top: 24px; text-align: center;">Broker Portal</h2>
        <p style="text-align: center; max-width: 360px; line-height: 1.6; opacity: 0.95;">Sign in with the username and password provided by your Carelix administrator.</p>
    </div>
    <div class="right">
        <div class="login-box">
            <div class="login-title">Broker Login</div>
            <p class="text-muted mb-4">Username &amp; password</p>

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger">
                    @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('broker.login.submit') }}">
                @csrf
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" name="username" id="username" class="form-control" value="{{ old('username') }}" required autofocus autocomplete="username">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" class="form-control" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-primary btn-block btn-login">Login</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>

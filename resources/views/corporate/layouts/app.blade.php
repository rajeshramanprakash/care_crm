<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Corporate Portal') - Carelix</title>
    <link rel="shortcut icon" href="{{ asset('favicon.jpg') }}" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('plugins/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/css/adminlte.min.css') }}">
    @include('partials.carelix-portal-theme')
    @stack('styles')
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <nav class="main-header navbar navbar-expand navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a></li>
        </ul>
        <ul class="navbar-nav ml-auto align-items-center">
            <li class="nav-item d-none d-sm-inline-block mr-3">
                <span class="carelix-portal-badge"><i class="fas fa-building mr-1"></i> Corporate Portal</span>
            </li>
            <li class="nav-item d-none d-md-inline-block mr-3"><span class="text-muted">{{ session('corporate_user_name') }}</span></li>
            <li class="nav-item">
                <form action="{{ route('corporate.logout') }}" method="POST" class="d-inline">@csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-sign-out-alt mr-1"></i> Logout</button>
                </form>
            </li>
        </ul>
    </nav>

    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="{{ route('corporate.dashboard') }}" class="brand-link text-center">
            <img src="{{ asset('images/carelix-logo.png') }}" alt="Carelix" class="brand-image img-fluid">
        </a>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column">
                    <li class="nav-item">
                        <a href="{{ route('corporate.dashboard') }}" class="nav-link {{ request()->routeIs('corporate.dashboard') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-tachometer-alt"></i><p>Dashboard</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('corporate.employees.index') }}" class="nav-link {{ request()->routeIs('corporate.employees.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-users"></i><p>Employees</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('corporate.password') }}" class="nav-link {{ request()->routeIs('corporate.password*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-key"></i><p>Reset Password</p>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    <div class="content-wrapper">
        <section class="content-header pb-0">
            <div class="container-fluid"><h1>@yield('page_title', 'Dashboard')</h1></div>
        </section>
        <section class="content pt-3">
            <div class="container-fluid">
                @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
                @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
                <div class="carelix-content-card">@yield('content')</div>
            </div>
        </section>
    </div>
</div>
<script src="{{ asset('plugins/jquery/jquery.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('adminlte/js/adminlte.min.js') }}"></script>
@stack('scripts')
</body>
</html>

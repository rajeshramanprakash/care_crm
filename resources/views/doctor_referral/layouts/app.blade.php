<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Doctor referral | {{ env('APP_NAME', 'Carelix') }}</title>
    <link rel="shortcut icon" href="{{ asset('favicon.jpg') }}" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('plugins/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/css/adminlte.min.css') }}">
    @include('partials.carelix-portal-theme')
    <style>
        :root {
            --drp-primary: var(--carelix-primary);
            --drp-primary-dark: var(--carelix-primary-dark);
            --drp-primary-soft: var(--carelix-primary-soft);
            --drp-border: #fed7aa;
            --drp-bg: var(--carelix-bg);
            --drp-text: var(--carelix-text);
            --drp-muted: #6b7280;
        }
        .sidebar .nav-link.nav-logout { color: #b91c1c !important; }
        .sidebar .nav-link.nav-logout .nav-icon { color: #ef4444 !important; }
        .sidebar .nav-link.nav-logout:hover {
            background: #fff1f2 !important;
            border-color: #fecdd3 !important;
            color: #b91c1c !important;
        }
        .drp-dash-table-wrap {
            max-height: min(58vh, 520px);
            overflow: auto;
            -webkit-overflow-scrolling: touch;
            width: 100%;
        }
        .drp-dash-table-wrap thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: linear-gradient(180deg, #fffaf5 0%, var(--carelix-primary-soft) 100%);
            color: var(--carelix-primary-dark);
            border-bottom: 2px solid var(--drp-border) !important;
            box-shadow: 0 1px 0 #fff5eb;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            white-space: nowrap;
            vertical-align: middle;
        }
        .text-drp-accent { color: var(--carelix-primary-dark) !important; }
        .portal-accent-badge {
            display: inline-block;
            padding: 0.28rem 0.52rem;
            font-size: 0.74rem;
            font-weight: 600;
            border-radius: 8px;
            background: var(--carelix-primary-soft);
            color: var(--carelix-primary-dark);
            border: 1px solid var(--drp-border);
        }
        .portal-lead-pill {
            font-weight: 700;
            font-size: 0.75rem;
            padding: 0.26rem 0.48rem;
            border-radius: 8px;
            background: var(--carelix-primary-soft);
            color: var(--carelix-primary-dark);
            border: 1px solid var(--drp-border);
            white-space: nowrap;
        }
        .badge-info {
            background: var(--carelix-primary-soft) !important;
            color: var(--carelix-primary-dark) !important;
            border: 1px solid var(--drp-border);
            font-weight: 600;
        }
        .alert-info {
            background: var(--carelix-primary-soft) !important;
            border-color: var(--drp-border) !important;
            color: var(--carelix-primary-dark) !important;
        }
        .drp-section-title { border-bottom-color: var(--drp-border) !important; }
        .drp-table-border { border-color: #e2e8f0 !important; }
    </style>
    @stack('styles')
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <nav class="main-header navbar navbar-expand navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
        </ul>
        <ul class="navbar-nav ml-auto align-items-center">
            <li class="nav-item d-none d-sm-inline-block mr-2">
                <span class="carelix-portal-badge"><i class="fas fa-user-md mr-1"></i> Doctor referral</span>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="{{ route('logout') }}" class="btn btn-sm btn-outline-danger">
                    <i class="fas fa-sign-out-alt mr-1"></i>Logout
                </a>
            </li>
        </ul>
    </nav>

    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="{{ route('doctor_referral.dashboard') }}" class="brand-link text-center">
            <img src="{{ asset('images/carelix-logo.png') }}" alt="Carelix Logo" class="brand-image img-fluid">
        </a>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                    <li class="nav-item">
                        <a href="{{ route('doctor_referral.dashboard') }}" class="nav-link {{ request()->routeIs('doctor_referral.dashboard') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('doctor_referral.leads') }}" class="nav-link {{ request()->routeIs('doctor_referral.leads') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-list-alt"></i>
                            <p>Leads</p>
                        </a>
                    </li>
                    <li class="nav-item d-sm-none">
                        <a href="{{ route('logout') }}" class="nav-link nav-logout">
                            <i class="nav-icon fas fa-sign-out-alt"></i>
                            <p>Logout</p>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    <div class="content-wrapper">
        <section class="content-header pb-0">
            <div class="container-fluid">
                <div class="d-flex align-items-center justify-content-between">
                    <h1>@yield('page_title', 'Dashboard')</h1>
                </div>
            </div>
        </section>
        <section class="content pt-3">
            <div class="container-fluid">
                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif
                @if(session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif
                @if(isset($errors) && $errors->any())
                    <div class="alert alert-danger">
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif
                <div class="carelix-content-card">
                    @yield('content')
                </div>
            </div>
        </section>
    </div>
</div>
<script src="{{ asset('plugins/jquery/jquery.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct" crossorigin="anonymous"></script>
<script src="{{ asset('adminlte/js/adminlte.min.js') }}"></script>
@stack('scripts')
</body>
</html>

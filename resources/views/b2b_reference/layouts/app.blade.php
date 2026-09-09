<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Carelix B2B Referral</title>
    <link rel="shortcut icon" href="{{ asset('favicon.jpg') }}" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('plugins/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/css/adminlte.min.css') }}">
    <style>
        :root {
            --b2b-primary: #f07f28;
            --b2b-primary-dark: #cf6413;
            --b2b-primary-soft: #fff3e8;
            --b2b-bg: #f5f7fb;
            --b2b-text: #1f2937;
            --b2b-muted: #6b7280;
            --b2b-sidebar-text: #4b5563;
            --b2b-sidebar-icon: #9ca3af;
            --b2b-sidebar-active-bg: #fff3e8;
            --b2b-sidebar-active-border: #ffd8b7;
        }
        html, body { font-family: 'Poppins', sans-serif; background: var(--b2b-bg); }
        /* AdminLTE sidebar-dark-* + light skin: force light bar so theme matches */
        .layout-fixed .main-sidebar.sidebar-dark-primary,
        .layout-fixed .main-sidebar.sidebar-dark-primary .sidebar {
            background: #fff !important;
            border-right: 1px solid #eceff4;
            box-shadow: 8px 0 26px rgba(15, 23, 42, 0.05);
        }
        .main-sidebar.sidebar-dark-primary .brand-link {
            color: #1f2937 !important;
        }
        .brand-link {
            border-bottom: 1px solid #eceff4 !important;
            padding-top: 0.9rem;
            padding-bottom: 0.9rem;
            background: linear-gradient(180deg, #ffffff 0%, #fffaf5 100%);
        }
        .brand-link .brand-image {
            float: none;
            max-height: 42px;
            margin: 0 auto;
            display: block;
        }
        .sidebar { padding-top: 0.3rem; }
        .sidebar .nav-link {
            border-radius: 10px;
            margin: 6px 10px;
            color: var(--b2b-sidebar-text) !important;
            font-weight: 600;
            width: auto;
            padding: 0.68rem 0.85rem;
            border: 1px solid transparent;
        }
        .sidebar .nav-link .nav-icon { color: var(--b2b-sidebar-icon) !important; font-size: 0.95rem; margin-right: 0.45rem; }
        .sidebar .nav-link:hover { background: #fff7ef; color: var(--b2b-primary) !important; border-color: #ffe3cc; }
        .sidebar .nav-link:hover .nav-icon { color: var(--b2b-primary) !important; }
        .sidebar .nav-link.active {
            background: var(--b2b-sidebar-active-bg) !important;
            color: var(--b2b-primary) !important;
            box-shadow: 0 6px 14px rgba(240, 127, 40, 0.14);
            border: 1px solid var(--b2b-sidebar-active-border);
        }
        .sidebar .nav-link.active .nav-icon { color: var(--b2b-primary) !important; }
        .main-header.navbar .nav-link[data-widget="pushmenu"] { color: var(--b2b-primary) !important; }
        .main-header.navbar .nav-link[data-widget="pushmenu"]:hover { color: var(--b2b-primary-dark) !important; }
        .main-header.navbar { border-bottom: 1px solid #e5e7eb; background: #fff; }
        .b2bref-header-badge { border-radius: 9px; font-weight: 600; padding: 0.35rem 0.65rem; background: #fff3e8; color: #cf6413; border: 1px solid #ffd8b7; font-size: 0.82rem; }
        .content-wrapper { background: var(--b2b-bg); }
        .content-header h1 { color: var(--b2b-text); font-size: 1.3rem; font-weight: 700; margin: 0; }
        .b2b-content-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            box-shadow: 0 8px 24px rgba(17, 24, 39, 0.04);
            padding: 14px;
        }
        .card { border: 1px solid #e5e7eb; border-radius: 14px; box-shadow: 0 8px 24px rgba(17, 24, 39, 0.04); }
        .btn-primary { background: var(--b2b-primary); border-color: var(--b2b-primary); }
        .btn-primary:hover, .btn-primary:focus { background: var(--b2b-primary-dark); border-color: var(--b2b-primary-dark); }
        .btn-outline-primary {
            color: var(--b2b-primary-dark) !important;
            border-color: #fdba74 !important;
            background: #fff;
        }
        .btn-outline-primary:hover {
            background: var(--b2b-primary-soft) !important;
            border-color: var(--b2b-primary) !important;
            color: var(--b2b-primary-dark) !important;
        }
        .text-primary, a.text-primary { color: var(--b2b-primary-dark) !important; }
        .badge-primary { background: var(--b2b-primary) !important; color: #fff !important; }
        .badge-info {
            background: var(--b2b-primary-soft) !important;
            color: var(--b2b-primary-dark) !important;
            border: 1px solid #ffd8b7;
            font-weight: 600;
        }
        .alert-info {
            background: var(--b2b-primary-soft) !important;
            border-color: #ffd8b7 !important;
            color: var(--b2b-primary-dark) !important;
        }
        html { overflow-x: hidden; }
        .wrapper { overflow-x: hidden; }
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
            <li class="nav-item d-none d-sm-inline-block mr-3">
                <span class="b2bref-header-badge"><i class="fas fa-handshake mr-1"></i> B2B Referral Portal</span>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="{{ route('logout') }}" class="btn btn-sm btn-outline-danger">
                    <i class="fas fa-sign-out-alt mr-1"></i>Logout
                </a>
            </li>
        </ul>
    </nav>

    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="{{ route('b2b_reference.dashboard') }}" class="brand-link text-center">
            <img src="{{ asset('images/carelix-logo.png') }}" alt="Carelix Logo" class="brand-image img-fluid">
        </a>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column">
                    <li class="nav-item">
                        <a href="{{ route('b2b_reference.dashboard') }}" class="nav-link {{ request()->routeIs('b2b_reference.dashboard') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('b2b_reference.leads') }}" class="nav-link {{ request()->routeIs('b2b_reference.leads') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-list-ul"></i>
                            <p>Leads</p>
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
                    <h1>@yield('page_title', 'Referral Dashboard')</h1>
                </div>
            </div>
        </section>
        <section class="content pt-3">
            <div class="container-fluid">
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
                <div class="b2b-content-card">
                    @yield('content')
                </div>
            </div>
        </section>
    </div>
</div>
<script src="{{ asset('plugins/jquery/jquery.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="{{ asset('adminlte/js/adminlte.min.js') }}"></script>
@stack('scripts')
</body>
</html>

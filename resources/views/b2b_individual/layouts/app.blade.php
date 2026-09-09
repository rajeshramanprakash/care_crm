<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Carelix — Individual Partner</title>
    <link rel="shortcut icon" href="{{ asset('favicon.jpg') }}" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('plugins/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/css/adminlte.min.css') }}">
    <style>
        :root {
            --b2b-primary: #F7941D;
            --b2b-primary-dark: #cf6413;
            --b2b-accent: #F07F28;
            --b2b-bg: #f4f6f9;
            --b2b-text: #0f172a;
            --b2b-muted: #64748b;
            --b2b-soft: #fff3e8;
            --b2b-soft-border: #ffd8b7;
            --b2b-sidebar-active-bg: #fff3e8;
            --b2b-sidebar-active-border: #ffd8b7;
            --b2b-tab-active-color: #c25f14;
            --b2b-modal-header-end: #fff8f1;
            --b2b-row-hover: #fffbf6;
            --b2b-focus-ring: rgba(240, 127, 40, 0.15);
            --b2b-chip-bg: #fff3e8;
            --b2b-chip-color: #c25f14;
            --b2b-section-accent: #9a3412;
        }
        html, body { font-family: 'Poppins', sans-serif; background: var(--b2b-bg); }
        .layout-fixed .main-sidebar { background: #fff; border-right: 1px solid #e2e8f0; }
        .brand-link { border-bottom: 1px solid #e2e8f0 !important; background: linear-gradient(180deg, #fff 0%, #fffaf5 100%); }
        .brand-link .brand-image { float: none; max-height: 42px; margin: 0 auto; display: block; }
        .sidebar .nav-link { border-radius: 6px; margin: 6px 10px; color: #475569 !important; font-weight: 600; border: none; }
        .sidebar .nav-link:not(.active):hover { color: var(--b2b-accent) !important; background: transparent !important; }
        .sidebar .nav-link:not(.active):hover .nav-icon { color: var(--b2b-accent) !important; }
        .sidebar .nav-link.active { background: var(--b2b-accent) !important; color: #fff !important; }
        .sidebar .nav-link.active .nav-icon, .sidebar .nav-link.active p { color: #fff !important; }
        .main-header .nav-link { color: var(--b2b-accent) !important; }
        .content-wrapper { background: var(--b2b-bg); }
        .content-header h1 { color: var(--b2b-text); }
        .b2b-content-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 14px; box-shadow: 0 8px 24px rgba(15,23,42,.04); }
        .btn-primary { background: var(--b2b-primary); border-color: var(--b2b-primary); }
        .btn-primary:hover, .btn-primary:focus { background: var(--b2b-primary-dark); border-color: var(--b2b-primary-dark); }
        .btn-outline-primary { color: var(--b2b-primary); border-color: var(--b2b-primary); }
        .btn-outline-primary:hover { background: var(--b2b-primary); border-color: var(--b2b-primary-dark); color: #fff; }
        .portal-badge { font-size: .72rem; font-weight: 700; color: #fff; background: var(--b2b-accent); padding: .2rem .5rem; border-radius: 6px; }
        .text-warning { color: var(--b2b-primary) !important; }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <nav class="main-header navbar navbar-expand navbar-light border-bottom">
        <ul class="navbar-nav"><li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a></li></ul>
        <ul class="navbar-nav ml-auto">
            <li class="nav-item d-none d-sm-inline-block"><span class="nav-link text-muted"><span class="portal-badge">Individual Partner</span></span></li>
            <li class="nav-item"><a href="{{ route('logout') }}" class="nav-link text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
    <aside class="main-sidebar elevation-1">
        <a href="{{ route('b2b.individual.dashboard') }}" class="brand-link text-center py-3">
            <img src="{{ asset('images/carelix-logo.png') }}" alt="Carelix" class="brand-image img-fluid">
        </a>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column">
                    <li class="nav-item">
                        <a href="{{ route('b2b.individual.dashboard') }}" class="nav-link {{ request()->routeIs('b2b.individual.dashboard') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-tachometer-alt"></i><p>Dashboard</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('b2b.individual.leads.index') }}" class="nav-link {{ request()->routeIs('b2b.individual.leads.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-user-plus"></i><p>Leads</p>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>
    <div class="content-wrapper">
        <section class="content-header pb-0"><div class="container-fluid"><h1 class="font-weight-bold">@yield('page_title', 'Dashboard')</h1></div></section>
        <section class="content pt-3">
            <div class="container-fluid">
                @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
                @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
                @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
                <div class="b2b-content-card">@yield('content')</div>
            </div>
        </section>
    </div>
</div>
<script src="{{ asset('plugins/jquery/jquery.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
@stack('scripts')
<script src="{{ asset('adminlte/js/adminlte.min.js') }}"></script>
</body>
</html>

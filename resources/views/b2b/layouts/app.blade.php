<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Carelix B2B</title>
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
            --b2b-bg: #f5f7fb;
            --b2b-text: #1f2937;
            --b2b-muted: #6b7280;
            --b2b-sidebar-text: #4b5563;
            --b2b-sidebar-icon: #9ca3af;
            --b2b-sidebar-active-bg: #fff3e8;
            --b2b-sidebar-active-border: #ffd8b7;
            --b2b-accent: #f07f28;
            --b2b-soft: #fff3e8;
            --b2b-soft-border: #ffd8b7;
            --b2b-tab-active-color: #c25f14;
            --b2b-modal-header-end: #fff8f1;
            --b2b-row-hover: #fffbf6;
            --b2b-focus-ring: rgba(240, 127, 40, 0.15);
            --b2b-chip-bg: #fff3e8;
            --b2b-chip-color: #c25f14;
            --b2b-section-accent: #9a3412;
        }

        html, body {
            font-family: 'Poppins', sans-serif;
            background: var(--b2b-bg);
        }

        .layout-fixed .main-sidebar {
            background: #fff;
            border-right: 1px solid #eceff4;
            box-shadow: 8px 0 26px rgba(15, 23, 42, 0.05);
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

        .sidebar {
            padding-top: 0.3rem;
        }

        .sidebar .nav-link {
            border-radius: 10px;
            margin: 6px 10px;
            color: var(--b2b-sidebar-text) !important;
            font-weight: 600;
            width: auto;
            padding: 0.68rem 0.85rem;
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }

        .nav-sidebar > .nav-item {
            margin: 0;
        }

        .sidebar .nav-link .nav-icon {
            color: var(--b2b-sidebar-icon) !important;
            font-size: 0.95rem;
            margin-right: 0.45rem;
        }

        .sidebar .nav-link:hover {
            background: #fff7ef;
            color: var(--b2b-primary) !important;
            border-color: #ffe3cc;
        }

        .sidebar .nav-link:hover .nav-icon {
            color: var(--b2b-primary) !important;
        }

        .sidebar .nav-link.active {
            background: var(--b2b-sidebar-active-bg) !important;
            color: var(--b2b-primary) !important;
            box-shadow: 0 6px 14px rgba(240, 127, 40, 0.14);
            border: 1px solid var(--b2b-sidebar-active-border);
            width: auto;
        }

        .sidebar .nav-link.active .nav-icon {
            color: var(--b2b-primary) !important;
        }

        .sidebar .nav-link.active p {
            font-weight: 700;
        }

        .sidebar .nav-link.nav-logout {
            color: #b91c1c !important;
        }

        .sidebar .nav-link.nav-logout .nav-icon {
            color: #ef4444 !important;
        }

        .sidebar .nav-link.nav-logout:hover {
            background: #fff1f2;
            border-color: #fecdd3;
            color: #b91c1c !important;
        }

        .main-header.navbar {
            border-bottom: 1px solid #e5e7eb;
            background: #fff;
        }

        .b2b-header-logout {
            border-radius: 9px;
            font-weight: 600;
            padding: 0.4rem 0.75rem;
            border: 1px solid #fecaca;
            color: #b91c1c !important;
            background: #fff1f2;
        }

        .b2b-header-logout:hover {
            background: #ffe4e6;
            color: #991b1b !important;
            border-color: #fda4af;
        }

        .content-wrapper {
            background: var(--b2b-bg);
        }

        .content-header h1 {
            color: var(--b2b-text);
            font-size: 1.3rem;
            font-weight: 700;
            margin: 0;
        }

        .b2b-content-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            box-shadow: 0 8px 24px rgba(17, 24, 39, 0.04);
            padding: 14px;
        }

        .card {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            box-shadow: 0 8px 24px rgba(17, 24, 39, 0.04);
        }

        .card-header {
            border-bottom: 1px solid #eef2f7;
            background: #fff;
        }

        .card-title {
            color: var(--b2b-text);
            font-weight: 700;
            margin-bottom: 0;
        }

        .btn-primary {
            background: var(--b2b-primary);
            border-color: var(--b2b-primary);
        }

        .btn-primary:hover,
        .btn-primary:focus {
            background: var(--b2b-primary-dark);
            border-color: var(--b2b-primary-dark);
        }

        .btn-outline-primary {
            color: var(--b2b-primary);
            border-color: var(--b2b-primary);
        }

        .btn-outline-primary:hover {
            background: var(--b2b-primary);
            border-color: var(--b2b-primary-dark);
            color: #fff;
        }

        .text-warning {
            color: var(--b2b-primary) !important;
        }

        .table thead th {
            border-top: 0;
            border-bottom: 1px solid #e5e7eb;
            color: var(--b2b-muted);
            font-weight: 600;
            font-size: 0.86rem;
            white-space: nowrap;
        }

        .table tbody td {
            border-top: 0;
            border-bottom: 1px solid #f1f5f9;
            color: var(--b2b-text);
            font-size: 0.9rem;
            vertical-align: middle;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <nav class="main-header navbar navbar-expand navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
        </ul>
        <ul class="navbar-nav ml-auto">
            <li class="nav-item d-none d-sm-inline-block">
                <span class="nav-link text-muted">B2B Portal</span>
            </li>
            <li class="nav-item d-none d-sm-inline-block ml-2">
                <a href="{{ route('logout') }}" class="nav-link b2b-header-logout">
                    <i class="fas fa-sign-out-alt mr-1"></i>Logout
                </a>
            </li>
        </ul>
    </nav>

    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="{{ route('b2b.dashboard') }}" class="brand-link text-center">
            <img src="{{ asset('images/carelix-logo.png') }}" alt="Carelix Logo" class="brand-image img-fluid">
        </a>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                    <li class="nav-item">
                        <a href="{{ route('b2b.dashboard') }}" class="nav-link {{ request()->routeIs('b2b.dashboard') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('b2b.leads.index') }}" class="nav-link {{ request()->routeIs('b2b.leads.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-user-plus"></i>
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
                    <h1>@yield('page_title', 'B2B Dashboard')</h1>
                </div>
            </div>
        </section>
        <section class="content pt-3">
            <div class="container-fluid">
                @if(session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif
                @if($errors->any())
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
{{-- Bootstrap 4 JS (required for modals/tabs); AdminLTE 3.2 is BS4-based. Plugin path was absent in many deploys. --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct" crossorigin="anonymous"></script>
@stack('scripts')
<script src="{{ asset('adminlte/js/adminlte.min.js') }}"></script>
</body>
</html>


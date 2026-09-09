<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('plugins/fontawesome/css/all.min.css') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.jpg') }}" type="image/x-icon">
    <link rel="stylesheet" href="{{ asset('adminlte/css/adminlte.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/toastr/toastr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') | {{ env('APP_NAME') }}</title>
    @yield('header-css')
    @stack('styles')
</head>
<style>
    html body {
        font-family: "Poppins", sans-serif;
        font-weight: 500;
    }
    .content-wrapper {
        flex: 1 0 auto;
        overflow-y: auto;
        overflow-x: hidden;
        max-height: calc(100vh - 120px);
        scrollbar-width: thin;
        scrollbar-color: #F7941D #f1f1f1;
    }
    .content-wrapper::-webkit-scrollbar { width: 8px; }
    .content-wrapper::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
    .content-wrapper::-webkit-scrollbar-thumb { background: #F7941D; border-radius: 10px; }
    .main-footer { position: sticky; bottom: 0; background: #fff; z-index: 1030; }
    .table-responsive { overflow-x: auto !important; }
</style>

<body class="sidebar-mini layout-fixed" style="overflow: hidden">
    @include('includes.preloader')
    <div class="wrapper">
        @include('vendor.layouts.navbar')
        @include('vendor.layouts.sidebar')
        @yield('main')
        @include('includes.footer')
    </div>

    <script src="{{ asset('plugins/jquery/jquery.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('adminlte/js/adminlte.min.js') }}"></script>
    <script src="{{ asset('plugins/toastr/toastr.min.js') }}"></script>
    <script src="{{ asset('js/common.js') }}"></script>

    @php
        if (session()->has('status')) {
            $type = session('status');
            $alert_type = $type['alert_type'];
            $msg = $type['message'];
    @endphp
    <script>
        toastr["{{ $alert_type }}"](`{{ $msg }}`);
    </script>
    @php
        }
        if (session()->has('error')) {
    @endphp
    <script>
        toastr.error(@json(session('error')));
    </script>
    @php
        }
        if (session()->has('success')) {
    @endphp
    <script>
        toastr.success(@json(session('success')));
    </script>
    @php
        }
    @endphp

    @yield('footer-script')
    @stack('scripts')
</body>

</html>

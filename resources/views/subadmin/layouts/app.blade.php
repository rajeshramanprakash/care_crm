<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="{{ asset('plugins/fontawesome/css/all.min.css') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.jpg') }}" type="image/x-icon">
    <link rel="stylesheet" href="{{ asset('adminlte/css/adminlte.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/toastr/toastr.min.css') }}">
    <script src="{{ asset('plugins/jquery/jquery.min.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') | {{ env('APP_NAME') }}</title>
    @yield('header-css')
    @yield('header-script')
</head>
<style>
    .table-responsive {
        overflow-x: visible !important;
    }

    .table-responsive #serverTable thead {
        position: sticky !important;
        top: 0;
    }

    .vendor-list {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
</style>

<body class="sidebar-mini layout-fixed">
    @include('includes.preloader')
    @include('subadmin.layouts.navbar')
    @include('subadmin.layouts.sidebar')

    <div class="wrapper">
        @section('main')
        @show
        @include('includes.footer')
    </div>

    <script src="{{ asset('adminlte/js/adminlte.js') }}"></script>
    <script src="{{ asset('plugins/toastr/toastr.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
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
    @endphp

    <script>
        $(function() {
            $(document).on('submit', '#filters-form', function() {
                var $toggle = $('[data-widget="control-sidebar"]').first();
                var $body = $('body');
                if ($toggle.length && typeof $toggle.ControlSidebar === 'function' &&
                    ($body.hasClass('control-sidebar-slide-open') || $body.hasClass('control-sidebar-open'))) {
                    $toggle.ControlSidebar('collapse');
                }
            });
        });
    </script>

    @yield('footer-script')
</body>

</html>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('plugins/fontawesome/css/all.min.css') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.jpg') }}" type="image/x-icon">
    <link rel="stylesheet" href="{{ asset('adminlte/css/adminlte.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/toastr/toastr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    <script src="{{ asset('plugins/jquery/jquery.min.js') }}"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') | {{ env('APP_NAME') }}</title>
    @yield('header-css')
    @yield('header-script')
</head>
<style>

    .dataTables_scroll, .dataTables_scrollHead, .dataTables_scrollHeadInner, .dataTable{
        width: 100% !important;
    }
    .dataTables_scrollBody .dataTable {
        width: 100% !important;
    }

    .table-responsive {
        overflow-x: auto !important;
    }

    .table-responsive #serverTable thead {
        position: sticky !important;
        top: 0;
    }

     /* Table header */
     table thead th {
            color: #b0b0b0;
            font-weight: 600;
            font-size: 0.92rem;
            background: #fff;
            border-bottom: 2px solid #f2f2f2;
            letter-spacing: 0.01em;
            padding: 7px 6px;
        }



        /* Table row hover */
        table tbody tr:hover {
            background: #fafbfc;
        }

        /* Status badges - dynamic width, always show full text, reduced padding */
        .status-badge, .badge {
            display: inline-block;
            text-align: center;
            vertical-align: middle;
            font-size: 0.7rem;
            font-weight: 500;
            padding: 4px 10px;
            border-radius: 8px;
            border: 1.5px solid transparent;
            /* Removed min-width, width, white-space, overflow, text-overflow */
        }

        .badge.bg-success, .badge.status-active {
            color: #1abc9c !important;
            border-color: #1abc9c !important;
            background: #eafaf6 !important;
        }
        .badge.bg-danger, .badge.status-inactive {
            color: #ff4d4f !important;
            border-color: #ff4d4f !important;
            background: #fff0f0 !important;
        }
        .badge.bg-info, .badge.status-followup {
            color: #3498db !important;
            border-color: #3498db !important;
            background: #eaf4fb !important;
        }
        .badge.bg-warning, .badge.status-inactive {
            color: #f39c12 !important;
            border-color: #f39c12 !important;
            background: #fef9e7 !important;
        }
        .badge.bg-secondary {
            color: #6c757d !important;
            border-color: #6c757d !important;
            background: #f8f9fa !important;
        }
        .badge.bg-dark {
            color: #343a40 !important;
            border-color: #343a40 !important;
            background: #e9ecef !important;
        }
        .status-badge.status-active {
            color: #1abc9c;
            border-color: #1abc9c;
            background: #eafaf6 !important;
        }
        .status-badge.status-inactive {
            color: #f39c12;
            border-color: #f39c12;
            background: #fef9e7 !important;
        }
        .status-badge.status-closed {
            color: #e74c3c;
            border-color: #e74c3c;
            background: #f0665738 !important;
        }
        .status-badge.status-profile-required {
            color: #3498db;
            border-color: #3498db;
            background: #a6d6f7 !important;
        }
        .status-badge.status-default {
            color: #95a5a6;
            border-color: #95a5a6;
            background: #f8f9fa !important;
        }

        /* Action icon */
        .table .fa-pen-to-square, .table .fa-edit {
            color: #b0b0b0;
            font-size: 1.05rem;
            transition: color 0.2s;
            cursor: pointer;
        }
        .table .fa-pen-to-square:hover, .table .fa-edit:hover {
            color: #1abc9c;
        }

        /* Remove table borders for a clean look */
        .table, .table th, .table td {
            border: none !important;
        }

        /* Optional: Remove table shadow if any */
        .card, .table-responsive {
            box-shadow: none !important;
            border-radius: 20px;
        }

        /* Make table more compact and modern */
        table.dataTable {
            border-collapse: separate !important;
            border-spacing: 0;
            background: #fff;
            font-size: 0.95rem;
            border-radius: 12px;
            overflow: hidden;
        }

        table.dataTable thead th {
            color: #b0b0b0;
            font-weight: 400;
            font-size: 14px;
            background: #fff;
            border-bottom: 2px solid #f2f2f2 !important;
            letter-spacing: 0.01em;
            padding: 7px 6px;
            text-align: center;
        }

        table.dataTable tbody td {
            color: #444;
            text-align: center;
            font-size: 14px;
            background: #fff;
            border-bottom: 1px solid #f2f2f2 !important;
            padding: 7px 6px;
            vertical-align: middle;
        }

        table.dataTable tbody tr:hover {
            background: #f8f9fa;
        }

        /* Remove background, border, and padding from call and WhatsApp icons */
        .table .fa-phone,
        .table .fa-whatsapp {
            background: none !important;
            border: none !important;
            border-radius: 0 !important;
            padding: 0 !important;
            margin: 0 2px 0 0;
            color: #27ae60 !important; /* or your preferred green */
            font-size: 1.05rem;
            box-shadow: none !important;
            transition: color 0.2s;
            vertical-align: middle;
        }

        .table .fa-phone:hover,
        .table .fa-whatsapp:hover {
            color: #1abc9c !important; /* slightly lighter green on hover */
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border-radius: 6px !important;
            border: 1px solid #e0e0e0 !important;
            background: #fff !important;
            color: #888 !important;
            margin: 0 2px;
            padding: 6px 12px;
            font-weight: 500;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #fd7e14 !important;
            color: #fff !important;
            border: none !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #eafaf6 !important;
            color: #fd7e14 !important;
        }

        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 6px;
            border: 1px solid #e0e0e0;
            padding: 6px 12px;
            font-size: 1rem;
        }

        .dataTables_wrapper .dataTables_info {
            color: #b0b0b0;
            font-size: 0.95rem;
            padding-top: 10px;
        }

        /* Responsive fix for horizontal scroll */
        .table-responsive {
            border-radius: 12px;
            box-shadow: none;
            background: #fff;
            margin-bottom: 0;
        }

        .action-btn {
            background: none;
            border: none;
            padding: 0;
            margin: 0 4px;
            cursor: pointer;
            outline: none;
            box-shadow: none;
            vertical-align: middle;
        }

        .action-btn i {
            color: #b0b0b0;
            font-size: 20px;
            transition: color 0.2s;
        }

        .action-btn.edit-btn i {
            /* Edit icon color */
            color: #b0b0b0;
        }
        .action-btn.update-btn i {
            /* Update icon color */
            color: #1abc9c;
        }
        .action-btn.delete-btn i {
            /* Delete icon color */
            color: #ff4d4f;
        }

        .action-btn:hover i {
            color: #1abc9c; /* or any highlight color you prefer */
        }
        .action-btn.delete-btn:hover i {
            color: #ff4d4f;
        }

        /* --- Enhanced Modal Styles --- */
        .modal-content {
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(44, 62, 80, 0.18);
            border: none;
            background: #f8f9fa;
        }
        .modal-header {
            border-top-left-radius: 18px;
            border-top-right-radius: 18px;
            background: linear-gradient(90deg, #fd7e14 0%, #ffb347 100%);
            color: #fff;
            box-shadow: 0 2px 8px rgba(44, 62, 80, 0.08);
        }
        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }
        .modal-body {
            background: #fff;
            border-bottom-left-radius: 18px;
            border-bottom-right-radius: 18px;
            padding: 2rem 1.5rem;
            max-height: 70vh;
            overflow-y: auto;
        }
        .form-label {
            font-weight: 600;
            /* color: #fd7e14; */
            font-size: 1.08rem;
        }
        .form-control, select.form-control {
            border-radius: 8px;
            /* border: 1.5px solid #fd7e14; */
            box-shadow: 0 1px 4px rgba(44, 62, 80, 0.06);
            font-size: 1.05rem;
            transition: border-color 0.2s;
        }
        .form-control:focus {
            border-color: #ffb347;
            box-shadow: 0 0 0 2px #ffe5c2;
        }
        .modal-footer {
            border-bottom-left-radius: 18px;
            border-bottom-right-radius: 18px;
            background: #f8f9fa;
            padding: 1.2rem 1.5rem;
        }
        .btn-primary {
            background: linear-gradient(90deg, #fd7e14 0%, #ffb347 100%);
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1.08rem;
            box-shadow: 0 2px 8px rgba(44, 62, 80, 0.08);
            transition: background 0.2s;
        }
        .btn-primary:hover {
            background: linear-gradient(90deg, #ffb347 0%, #fd7e14 100%);
        }
        .btn-secondary {
            border-radius: 8px;
            font-weight: 600;
            font-size: 1.08rem;
        }
        .modal-body .bg-light {
            background: #f8f9fa !important;
        }
        @media (max-width: 576px) {
            .modal-content, .modal-header, .modal-body, .modal-footer {
                border-radius: 10px !important;
                padding: 1rem !important;
            }
            .modal-title {
                font-size: 1.15rem;
            }
        }
        .contact-icons{
            font-size: 18px !important;
        }

        .no-wrap {
            white-space: nowrap !important;
            /* text-overflow: ellipsis; */
            /* overflow: hidden; */
            max-width: 140px;
        }

    .vendor-list {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .dataTables_wrapper .dataTables_filter input {
        border-radius: 20px;
        border: 1px solid #ddd;
        padding: 6px 14px;
    }

    html body{
        font-family: "Poppins", sans-serif;
  font-weight: 500;
  font-style: normal;
    }
    .content-wrapper { flex: 1 0 auto; }
    .main-footer {position: sticky; bottom: 0; background: #fff; z-index: 1030; }

</style>

<body class="sidebar-mini layout-fixed" style="overflow: hidden">
    @include('includes.preloader')
    @include('admin.layouts.navbar')
    @include('admin.layouts.sidebar')

    <div class="wrapper">
        @yield('content')
        @yield('main')
        @include('includes.footer')
    </div>

    <script src="{{ asset('adminlte/js/adminlte.js') }}"></script>
    <script src="{{ asset('plugins/toastr/toastr.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/modal-helper.js') }}"></script>
    <script src="{{ asset('js/common.js') }}"></script>

 <script>
    // More aggressive approach - completely override AdminLTE
$(document).ready(function() {
    // Wait for page to fully load
    setTimeout(function() {
        // Remove ALL click handlers from the filter button
        $('[data-widget="control-sidebar"]').off();
        $('.filter-toggle').off();

        // Add our own handler with namespace
        $(document).on('click.customSidebar', '[data-widget="control-sidebar"], .filter-toggle', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation(); // Stop all other handlers

            toggleFilterSidebar();
        });

        // Close handlers
        $(document).on('click.customSidebar', '.control-sidebar-close, .control-sidebar-backdrop', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            closeFilterSidebar();
        });

    }, 100); // Small delay to ensure AdminLTE is loaded

    // Escape key
    $(document).keyup(function(e) {
        if (e.keyCode === 27) {
            closeFilterSidebar();
        }
    });
});

// Separate functions for better control
function toggleFilterSidebar() {
    console.log('Toggling sidebar');

    if ($('.control-sidebar').hasClass('control-sidebar-open')) {
        closeFilterSidebar();
    } else {
        openFilterSidebar();
    }
}

function openFilterSidebar() {
    $('.control-sidebar').addClass('control-sidebar-open');
    $('body').addClass('control-sidebar-slide-open');

    if (!$('.control-sidebar-backdrop').length) {
        $('body').append('<div class="control-sidebar-backdrop"></div>');
    }
}

function closeFilterSidebar() {
    $('.control-sidebar').removeClass('control-sidebar-open');
    $('body').removeClass('control-sidebar-slide-open');
    $('.control-sidebar-backdrop').remove();
}

 </script>

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




    @yield('footer-script')
</body>

</html>

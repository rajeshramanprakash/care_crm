@php
$doctorId = Session::get('doctor_reg_id');
$doctorName = Session::get('doctor_reg_name');
$doctor = \App\Models\DoctorRequest::find($doctorId);
$uri_arr = explode(".", Route::currentRouteName());
$uri = end($uri_arr);
$route_name = Route::currentRouteName();
@endphp
<style>
    .dataTables_scroll, .dataTables_scrollHead, .dataTables_scrollHeadInner, .dataTable{
        width: 100% !important;
    }
     .nav-sidebar .nav-link .nav-icon,
    .nav-sidebar .nav-link p {
        color: inherit;
        transition: color 0.2s;
    }
    .nav-sidebar .nav-link.active .nav-icon,
    .nav-sidebar .nav-link.active p {
        color: #fff !important;
    }
    .nav-sidebar .nav-link:not(.active):hover .nav-icon,
    .nav-sidebar .nav-link:not(.active):hover p {
        color: #F07F28 !important;
    }
    .navbar-dark .navbar-nav .nav-link {
        color: #F07F28 !important;

    }
    .main-sidebar {
        display: flex !important;
        flex-direction: column;
        height: 100vh;
    }
    .sidebar {
        flex: 1 1 auto;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        height: 100%;
        padding-bottom: 0;
    }
    .user-panel-bottom {
        margin-top: auto;
        padding-bottom: 1rem;
    }
    .user-panel .image img {
        width: 43px;
        height: 43px;
    }
    .badge.unread-count-sidebar {
        background: #25d366 !important;
        color: #fff !important;
        font-weight: bold;
    }
</style>
<aside class="main-sidebar sidebar-dark-danger" style="background: #ffffff;">
    <a href="{{route('doctor_portal.dashboard')}}" class="brand-link text-center">
        <img src="{{asset('images/carelix-logo.png')}}" alt="Carelix Logo" style="width: 40% !important; filter: drop-shadow(1px 1px 5px #ffffff50);">
    </a>
    <div class="sidebar">

        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <li class="nav-item">
                    <a href="{{route('doctor_portal.dashboard')}}" class="nav-link {{$uri == "dashboard" ? 'active' : ''}}">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{route('doctor_portal.personal-details')}}" class="nav-link {{ strpos($route_name, 'personal-details') !== false ? 'active' : '' }}">
                        <i class="nav-icon fas fa-user"></i>
                        <p>Personal Details</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{route('doctor_portal.availability')}}" class="nav-link {{ strpos($route_name, 'availability') !== false ? 'active' : '' }}">
                        <i class="nav-icon fas fa-calendar-week"></i>
                        <p>Calendar availability</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{route('doctor_portal.bookings')}}" class="nav-link {{ strpos($route_name, 'bookings') !== false ? 'active' : '' }}">
                        <i class="nav-icon fas fa-book-medical"></i>
                        <p>Website bookings</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{route('doctor_portal.customer-chats')}}" class="nav-link {{ strpos($route_name, 'customer-chats') !== false ? 'active' : '' }}">
                        <i class="nav-icon fas fa-comments"></i>
                        <p>Customer chats</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{route('doctor_portal.refer-leads')}}" class="nav-link {{ strpos($route_name, 'refer-leads') !== false ? 'active' : '' }}">
                        <i class="nav-icon fas fa-user-plus"></i>
                        <p>Refer Lead</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{route('doctor_portal.bank-details')}}" class="nav-link {{ strpos($route_name, 'bank-details') !== false ? 'active' : '' }}">
                        <i class="nav-icon fas fa-university"></i>
                        <p>Bank Account Details</p>
                    </a>
                </li>
            </ul>
        </nav>
        <div class="user-panel mt-3 pb-3 mb-3 d-flex align-items-center">
            <div class="image">
                <img src="{{ asset('images/default-user.png') }}"
                     onerror="this.onerror=null; this.src='{{ asset('images/default-user.png') }}'"
                     class="img-circle elevation-2"
                     alt="User Image"
                     style="width: 43px; height: 43px;">
            </div>
            <div class="info text-center py-0" style="margin-left: 10px;">
                <a href="javascript:void(0);" class="d-block" style="font-weight: 600;">{{$doctorName ?? 'Doctor'}}</a>
                <div style="font-size: 0.85rem; color: #888;">Doctor</div>
            </div>
        </div>
    </div>
</aside>
<script>
    // Sidebar should be collapsed by default
    document.addEventListener('DOMContentLoaded', function() {
        if (!document.body.classList.contains('sidebar-collapse')) {
            document.body.classList.add('sidebar-collapse');
        }
    });

    // Sidebar toggle button logic
    var sidebarToggleBtn = document.getElementById('sidebar_toggle_btn');
    if (sidebarToggleBtn) {
        sidebarToggleBtn.addEventListener('click', function() {
            document.body.classList.toggle('sidebar-collapse');
            localStorage.setItem('sidebar_collapse', document.body.classList.contains('sidebar-collapse'));
        });
    }

    // Keep your existing sidebar collapse logic if needed
    function initialize_sidebar_collapse() {
        const sidebar_collapsible_elem = document.getElementById('sidebar_collapsible_elem');
        const localstorage_value = localStorage.getItem('sidebar_collapse');
        if (localstorage_value !== null) {
            if (localstorage_value == "true") {
                if (sidebar_collapsible_elem) sidebar_collapsible_elem.setAttribute('data-collapse', 0);
                document.body.classList.add('sidebar-collapse');
            } else {
                document.body.classList.remove('sidebar-collapse');
            }
        }
    }
    initialize_sidebar_collapse();
</script>


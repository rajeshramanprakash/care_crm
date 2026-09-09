@php
$freelancerId = Session::get('freelancer_id');
$freelancerName = Session::get('freelancer_name');
$freelancer = \App\Models\JobRequest::find($freelancerId);
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
    <a href="{{route('freelancer.dashboard')}}" class="brand-link text-center">
        <img src="{{asset('images/carelix-logo.png')}}" alt="Carelix Logo" style="width: 40% !important; filter: drop-shadow(1px 1px 5px #ffffff50);">
    </a>
    <div class="sidebar">

        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <li class="nav-item">
                    <a href="{{route('freelancer.dashboard')}}" class="nav-link {{$uri == "dashboard" ? 'active' : ''}}">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{route('freelancer.personal-details')}}" class="nav-link {{ strpos($route_name, 'personal-details') !== false ? 'active' : '' }}">
                        <i class="nav-icon fas fa-user"></i>
                        <p>Personal Details</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{route('freelancer.bank-details')}}" class="nav-link {{ strpos($route_name, 'bank-details') !== false ? 'active' : '' }}">
                        <i class="nav-icon fas fa-university"></i>
                        <p>Bank Account Details</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{route('freelancer.assigned-leads')}}" class="nav-link {{ strpos($route_name, 'assigned-leads') !== false ? 'active' : '' }}">
                        <i class="nav-icon fas fa-list-alt"></i>
                        <p>Assigned Leads</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{route('freelancer.payment-details')}}" class="nav-link {{ strpos($route_name, 'payment-details') !== false ? 'active' : '' }}">
                        <i class="nav-icon fas fa-rupee-sign"></i>
                        <p>Payment Details</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{route('freelancer.emergency-details')}}" class="nav-link {{ strpos($route_name, 'emergency-details') !== false ? 'active' : '' }}">
                        <i class="nav-icon fas fa-phone-alt"></i>
                        <p>Emergency Details</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{route('freelancer.customer-chats')}}" class="nav-link {{ strpos($route_name, 'customer-chats') !== false ? 'active' : '' }}">
                        <i class="nav-icon fas fa-comments"></i>
                        <p>Customer Chats</p>
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
                <a href="javascript:void(0);" class="d-block" style="font-weight: 600;">{{$freelancerName ?? 'Freelancer'}}</a>
                <div style="font-size: 0.85rem; color: #888;">Freelancer</div>
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


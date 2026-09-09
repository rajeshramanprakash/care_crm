@php
$auth_user = Auth::user();
$uri_arr = explode(".", Route::currentRouteName());
$uri = end($uri_arr);
$route_name = Route::currentRouteName();
@endphp
<style>
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
<aside class="main-sidebar sidebar-dark-danger" style="background: #ffffff; display: flex; flex-direction: column; height: 100vh;">
    <a href="{{route('admin.dashboard')}}" class="brand-link text-center">
        <img src="{{asset('images/carelix-logo.png')}}" alt="Carelix Logo" style="width: 40% !important; filter: drop-shadow(1px 1px 5px #ffffff50);">
    </a>
    <div class="sidebar" style="flex: 1 1 auto; display: flex; flex-direction: column; justify-content: space-between; height: 100%;">
        <div>
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <li class="nav-item">
                        <a href="{{route('admin.dashboard')}}" class="nav-link {{$uri == "dashboard" ? 'active' : ''}}">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{route('admin.leads.index')}}" class="nav-link {{ strpos($route_name, 'leads') !== false ? 'active' : '' }}">
                            <i class="nav-icon fas fa-user-friends"></i>
                            <p>Leads</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{route('admin.users.index')}}" class="nav-link {{ strpos($route_name, 'users') !== false ? 'active' : '' }}">
                            <i class="nav-icon fas fa-user-friends"></i>
                            <p>Users</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{route('admin.break_logs.index')}}" class="nav-link {{ strpos($route_name, 'break_logs') !== false ? 'active' : '' }}">
                            <i class="nav-icon fas fa-clock"></i>
                            <p>Break Logs</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{route('admin.duty_logs.index')}}" class="nav-link {{ strpos($route_name, 'duty_logs') !== false ? 'active' : '' }}">
                            <i class="nav-icon fas fa-user-check"></i>
                            <p>Duty Logs</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{route('admin.operation_leads.index')}}" class="nav-link {{ strpos($route_name, 'operation_leads') !== false ? 'active' : '' }}">
                            <i class="nav-icon fas fa-briefcase-medical"></i>
                            <p>Operation Leads</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{route('admin.locations.index')}}" class="nav-link {{ strpos($route_name, 'locations') !== false ? 'active' : '' }}">
                            <i class="nav-icon fas fa-map-marker-alt"></i>
                            <p>Locations</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{route('admin.services.index')}}" class="nav-link {{ strpos($route_name, 'services') !== false ? 'active' : '' }}">
                            <i class="nav-icon fas fa-cogs"></i>
                            <p>Services</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{route('admin.jobproc.index')}}" class="nav-link {{ strpos($route_name, 'jobproc') !== false ? 'active' : '' }}">
                            <i class="nav-icon fas fa-comments"></i>
                            <p>Job Request</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{route('admin.chat.index')}}" class="nav-link {{ strpos($route_name, 'chat') !== false ? 'active' : '' }}">
                            <i class="nav-icon fas fa-comments"></i>
                            <p>
                                Chat
                                <span id="sidebar-unread-badge-admin" class="badge unread-count-sidebar" style="display:none; margin-left:8px; background:#25d366; color:#fff; font-weight:bold;"></span>
                            </p>
                        </a>
                    </li>
                    <li class="nav-item">
                         <a href="{{route('admin.staff.chats')}}" class="nav-link {{ strpos($route_name, 'staff.chats') !== false ? 'active' : '' }}">
                            <i class="nav-icon fas fa-users"></i>
                            <p>All Staff Chats</p>
                        </a>
                    </li>
                    <li class="nav-item">
                         <a href="{{route('admin.all_whatsapp_chats.index')}}" class="nav-link {{ strpos($route_name, 'all_whatsapp_chats.index') !== false ? 'active' : '' }}">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Whatsapp</p>
                        </a>
                    </li>
                    <li class="nav-item">
                         <a href="{{route('admin.vendors.index')}}" class="nav-link {{ strpos($route_name, 'admin.vendors.index') !== false ? 'active' : '' }}">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Vendor</p>
                        </a>
                    </li>
                    <li class="nav-item">
                         <a href="{{route('admin.vendor_payments.index')}}" class="nav-link {{ strpos($route_name, 'vendor_payments') !== false ? 'active' : '' }}">
                            <i class="nav-icon fas fa-money-bill-wave"></i>
                            <p>Vendor Payments</p>
                        </a>
                    </li>
                    <li class="nav-item">
                         <a href="{{route('admin.technical-support.index')}}" class="nav-link {{ strpos($route_name, 'technical-support') !== false ? 'active' : '' }}">
                            <i class="nav-icon fas fa-headset"></i>
                            <p>Technical Support</p>
                        </a>
                    </li>
                    <br>
                </ul>
            </nav>
        </div>
        <div class="user-panel-bottom mb-3">
            <div class="user-panel d-flex align-items-center" style="padding: 0 1rem;">
                <div class="image">
                    <a href="javascript:void(0);" onclick="handle_view_image('{{ $auth_user->profile_image ? asset('storage/'.$auth_user->profile_image) : asset('images/default-user.png') }}', '{{ route('updateProfileImage') }}/{{ $auth_user->id }}')">
                        <img src="{{ $auth_user->profile_image ? asset('storage/'.$auth_user->profile_image) : asset('images/default-user.png') }}"
                             onerror="this.onerror=null; this.src='{{ asset('images/default-user.png') }}'"
                             class="img-circle elevation-2"
                             alt="User Image"
                             style="width: 43px; height: 43px;">
                    </a>
                </div>
                <div class="info text-center py-0" style="margin-left: 10px;">
                    <a href="javascript:void(0);" class="d-block" style="font-weight: 600;">{{$auth_user->f_name}} {{$auth_user->l_name ?: 'N/A'}}</a>
                    <div style="font-size: 0.85rem; color: #888;">{{$auth_user->get_role->name}}</div>
                </div>
            </div>
        </div>
    </div>
</aside>
<script>
    // $('.nav-sidebar').tree(); // Removed for Bootstrap 5/AdminLTE 3+
    function initialize_sidebar_collapse() {
        const sidebar_collapsible_elem = document.getElementById('sidebar_collapsible_elem');
        const localstorage_value = localStorage.getItem('sidebar_collapse');
        if (localstorage_value !== null) {
            if (localstorage_value == "true") {
                sidebar_collapsible_elem.setAttribute('data-collapse', 0);
                document.body.classList.add('sidebar-collapse');
            }
        }
    }
    initialize_sidebar_collapse();

    function updateSidebarUnreadBadgeAdmin() {
        fetch('{{ route('admin.chat.unread-counts') }}')
            .then(response => response.json())
            .then(counts => {
                let total = 0;
                Object.values(counts).forEach(count => total += count);
                const badge = document.getElementById('sidebar-unread-badge-admin');
                if (badge) {
                    if (total > 0) {
                        badge.textContent = total;
                        badge.style.display = '';
                    } else {
                        badge.style.display = 'none';
                    }
                }
            });
    }
    updateSidebarUnreadBadgeAdmin();
    setInterval(updateSidebarUnreadBadgeAdmin, 5000);
</script>


@php
    $rolePrefix = auth()->check() && session('role_name') === 'Sub Admin' ? 'subadmin' : 'admin';
@endphp
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
        transition: color 0.2s, background-color 0.2s;
    }
    .main-sidebar .nav-sidebar .nav-link.active {
        background-color: #F07F28 !important;
        color: #fff !important;
        border-radius: 6px;
    }
    .main-sidebar .nav-sidebar .nav-treeview .nav-link.active {
        background-color: #F07F28 !important;
        color: #fff !important;
        border-radius: 6px;
    }
    .nav-sidebar .nav-link.active .nav-icon,
    .nav-sidebar .nav-link.active p {
        color: #fff !important;
    }
    .nav-sidebar .nav-link:not(.active):hover .nav-icon,
    .nav-sidebar .nav-link:not(.active):hover p {
        color: #F07F28 !important;
    }
    .nav-sidebar .nav-link.active:hover .nav-icon,
    .nav-sidebar .nav-link.active:hover p {
        color: #fff !important;
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
    <a href="{{route($rolePrefix . '.dashboard')}}" class="brand-link text-center">
        <img src="{{asset('images/carelix-logo.png')}}" alt="Carelix Logo" style="width: 40% !important; filter: drop-shadow(1px 1px 5px #ffffff50);">
    </a>
    <div class="sidebar" style="flex: 1 1 auto; display: flex; flex-direction: column; justify-content: space-between; height: 100%;">
        <div>
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    @can('view_dashboard')
                    <li class="nav-item">
                        <a href="{{route($rolePrefix . '.dashboard')}}" class="nav-link {{$uri == "dashboard" ? 'active' : ''}}">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>
                    @endcan
                    @can('view_payments')
                    <li class="nav-item">
                        <a href="{{ route($rolePrefix . '.payments.index') }}" class="nav-link {{ str_starts_with($route_name, 'admin.payments.') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-link"></i>
                            <p>Payments</p>
                        </a>
                    </li>
                    @endcan
                    @can('view_leads')
                    <li class="nav-item">
                        <a href="{{route($rolePrefix . '.leads.index')}}" class="nav-link {{ str_starts_with($route_name, $rolePrefix . '.leads.') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-user-friends"></i>
                            <p>Leads</p>
                        </a>
                    </li>
                    @endcan
                    @can('view_referral_leads')
                    <li class="nav-item">
                        <a href="{{ route($rolePrefix . '.referral_leads.index') }}" class="nav-link {{ strpos($route_name, 'referral_leads') !== false ? 'active' : '' }}">
                            <i class="nav-icon fas fa-share-square"></i>
                            <p>Sales &amp; Operation Referral Leads</p>
                        </a>
                    </li>
                    @endcan
                    @can('view_user')
                    <li class="nav-item">
                        <a href="{{route($rolePrefix . '.users.index')}}" class="nav-link {{ strpos($route_name, 'users') !== false ? 'active' : '' }}">
                            <i class="nav-icon fas fa-user-friends"></i>
                            <p>Users</p>
                        </a>
                    </li>
                    @endcan
                    @can('view_b2b_users')
                    <li class="nav-item">
                        <a href="{{route($rolePrefix . '.b2b_users.index')}}" class="nav-link {{ strpos($route_name, 'b2b_users') !== false ? 'active' : '' }}">
                            <i class="nav-icon fas fa-briefcase"></i>
                            <p>B2B Users</p>
                        </a>
                    </li>
                    @endcan
                    @if(auth()->user()->can('view_b2b_corporate') || auth()->user()->can('view_b2b_individual'))
                    @php
                        $ci_b2b_open = strpos($route_name, 'corporate_individual') !== false
                            || strpos($route_name, 'b2b_corporate') !== false
                            || strpos($route_name, 'b2b_individual') !== false;
                    @endphp
                    <li class="nav-item has-treeview {{ $ci_b2b_open ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ $ci_b2b_open ? 'active' : '' }}">
                            <i class="nav-icon fas fa-building"></i>
                            <p>
                                Corporate / Individual (B2B)
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="{{ route($rolePrefix . '.corporate_individual.hub') }}" class="nav-link {{ strpos($route_name, 'corporate_individual') !== false ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Hub</p>
                                </a>
                            </li>
                            @can('view_b2b_corporate')
                            <li class="nav-item">
                                <a href="{{ route($rolePrefix . '.b2b_corporate.index') }}" class="nav-link {{ strpos($route_name, 'b2b_corporate') !== false ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>B2B Corporate</p>
                                </a>
                            </li>
                            @endcan
                            @can('view_b2b_individual')
                            <li class="nav-item">
                                <a href="{{ route($rolePrefix . '.b2b_individual.index') }}" class="nav-link {{ strpos($route_name, 'b2b_individual') !== false ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Individual</p>
                                </a>
                            </li>
                            @endcan
                            @if($showB2bCorporateStaffChat ?? false)
                            <li class="nav-item">
                                <a href="{{ route('crm.b2b_corporate_chat.index') }}" class="nav-link {{ strpos($route_name, 'b2b_corporate_chat') !== false ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>B2B Corporate Chat</p>
                                </a>
                            </li>
                            @endif
                        </ul>
                    </li>
                    @endif
                    @php
                        $insurer_broker_sidebar_open = strpos($route_name, 'insurers') !== false
                            || strpos($route_name, 'brokers') !== false
                            || strpos($route_name, 'corporate-accounts') !== false
                            || strpos($route_name, 'corporate-employees') !== false;
                    @endphp
                    @canany(['view_insurers', 'view_brokers'])
                    <li class="nav-item has-treeview {{ $insurer_broker_sidebar_open ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ $insurer_broker_sidebar_open ? 'active' : '' }}">
                            <i class="nav-icon fas fa-shield-alt"></i>
                            <p>
                                Insurer &amp; Broker
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('view_insurers')
                            <li class="nav-item">
                                <a href="{{ route($rolePrefix . '.insurers.index') }}" class="nav-link {{ strpos($route_name, 'insurers') !== false ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Insurers</p>
                                </a>
                            </li>
                            @endcan
                            @can('view_brokers')
                            <li class="nav-item">
                                <a href="{{ route($rolePrefix . '.brokers.index') }}" class="nav-link {{ strpos($route_name, 'brokers') !== false ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Brokers</p>
                                </a>
                            </li>
                            @endcan
                            @if(session('logged_role') == 1)
                            <li class="nav-item">
                                <a href="{{ route($rolePrefix . '.corporate-accounts.index') }}" class="nav-link {{ strpos($route_name, 'corporate-accounts') !== false ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Corporate Accounts</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route($rolePrefix . '.corporate-employees.index') }}" class="nav-link {{ strpos($route_name, 'corporate-employees') !== false ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Corporate Employees</p>
                                </a>
                            </li>
                            @endif
                        </ul>
                    </li>
                    @endcanany
                    @if(session('logged_role') == 1)
                    <li class="nav-item">
                        <a href="{{route($rolePrefix . '.break_logs.index')}}" class="nav-link {{ strpos($route_name, 'break_logs') !== false ? 'active' : '' }}">
                            <i class="nav-icon fas fa-clock"></i>
                            <p>Break Logs</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{route($rolePrefix . '.duty_logs.index')}}" class="nav-link {{ strpos($route_name, 'duty_logs') !== false ? 'active' : '' }}">
                            <i class="nav-icon fas fa-user-check"></i>
                            <p>Duty Logs</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{route($rolePrefix . '.operation_leads.index')}}" class="nav-link {{ strpos($route_name, 'operation_leads') !== false ? 'active' : '' }}">
                            <i class="nav-icon fas fa-briefcase-medical"></i>
                            <p>Operation Leads</p>
                        </a>
                    </li>
                    @endif
                    @can('view_locations')
                    <li class="nav-item">
                        <a href="{{route($rolePrefix . '.locations.index')}}" class="nav-link {{ strpos($route_name, 'locations') !== false ? 'active' : '' }}">
                            <i class="nav-icon fas fa-map-marker-alt"></i>
                            <p>Locations</p>
                        </a>
                    </li>
                    @endcan
                    @can('view_services')
                    <li class="nav-item">
                        <a href="{{route($rolePrefix . '.services.index')}}" class="nav-link {{ strpos($route_name, 'services') !== false ? 'active' : '' }}">
                            <i class="nav-icon fas fa-cogs"></i>
                            <p>Services</p>
                        </a>
                    </li>
                    @endcan
                    @if(session('logged_role') == 1)
                    <li class="nav-item">
                        <a href="{{ route('admin.languages.index') }}" class="nav-link {{ strpos($route_name, 'languages') !== false ? 'active' : '' }}">
                            <i class="nav-icon fas fa-language"></i>
                            <p>Registration Languages</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.agreements.index') }}" class="nav-link {{ str_starts_with($route_name, 'admin.agreements') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-file-contract"></i>
                            <p>All Agreements &amp; Partners</p>
                        </a>
                    </li>
                    @endif
                    @if(session('logged_role') == 1)
                    @php
                        $doctor_sidebar_open = strpos($route_name, 'doctor_requests') !== false
                            || strpos($route_name, 'doctor_consultation_services') !== false
                            || strpos($route_name, 'website_consultation_payments') !== false
                            || strpos($route_name, 'doctor_registration_otp_logs') !== false;
                    @endphp
                    <li class="nav-item has-treeview {{ $doctor_sidebar_open ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ $doctor_sidebar_open ? 'active' : '' }}">
                            <i class="nav-icon fas fa-user-md"></i>
                            <p>
                                Doctor
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="{{ route($rolePrefix . '.doctor_registration_otp_logs.index') }}" class="nav-link {{ strpos($route_name, 'doctor_registration_otp_logs') !== false ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Verify No.</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route($rolePrefix . '.doctor_requests.index') }}" class="nav-link {{ strpos($route_name, 'doctor_requests') !== false ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Doctor requests</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route($rolePrefix . '.doctor_consultation_services.index') }}" class="nav-link {{ strpos($route_name, 'doctor_consultation_services') !== false ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Doctor consultation services</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.website_consultation_payments.index') }}" class="nav-link {{ strpos($route_name, 'website_consultation_payments') !== false ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Website consultation payments</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    @php
                        $chats_sidebar_open = str_starts_with($route_name, 'admin.chat.')
                            || str_starts_with($route_name, 'admin.staff.chats')
                            || str_starts_with($route_name, 'admin.customer_chats');
                    @endphp
                    <li class="nav-item has-treeview {{ $chats_sidebar_open ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ $chats_sidebar_open ? 'active' : '' }}">
                            <i class="nav-icon fas fa-comments"></i>
                            <p>
                                All Chats
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="{{ route('admin.chat.index') }}" class="nav-link {{ str_starts_with($route_name, 'admin.chat.') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>
                                        Chat
                                        <span id="sidebar-unread-badge-admin" class="badge unread-count-sidebar" style="display:none; margin-left:8px; background:#25d366; color:#fff; font-weight:bold;"></span>
                                    </p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.staff.chats') }}" class="nav-link {{ str_starts_with($route_name, 'admin.staff.chats') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>All Staff Chats</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.customer_chats.index') }}" class="nav-link {{ str_starts_with($route_name, 'admin.customer_chats') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Customer Chats</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item">
                         <a href="{{route($rolePrefix . '.all_whatsapp_chats.index')}}" class="nav-link {{ strpos($route_name, 'all_whatsapp_chats.index') !== false ? 'active' : '' }}">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Whatsapp</p>
                        </a>
                    </li>
                    @php
                        $freelancer_vendor_sidebar_open = str_starts_with($route_name, 'admin.vendors.')
                            || str_starts_with($route_name, 'admin.vendor_payments.')
                            || str_starts_with($route_name, 'admin.freelancer_payments.')
                            || str_starts_with($route_name, 'admin.location_attendance.')
                            || str_starts_with($route_name, 'admin.jobproc.')
                            || str_starts_with($route_name, 'admin.vendor_registration_otp_logs')
                            || str_starts_with($route_name, 'admin.freelancer_registration_otp_logs');
                    @endphp
                    <li class="nav-item has-treeview {{ $freelancer_vendor_sidebar_open ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ $freelancer_vendor_sidebar_open ? 'active' : '' }}">
                            <i class="nav-icon fas fa-user-tie"></i>
                            <p>
                                All Freelancer &amp; Vendor
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="{{ route($rolePrefix . '.vendor_registration_otp_logs.index') }}" class="nav-link {{ str_starts_with($route_name, 'admin.vendor_registration_otp_logs') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Vendor Verify No.</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route($rolePrefix . '.vendors.index') }}" class="nav-link {{ str_starts_with($route_name, 'admin.vendors.') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Vendor</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route($rolePrefix . '.vendor_payments.index') }}" class="nav-link {{ str_starts_with($route_name, 'admin.vendor_payments.') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Vendor Payments</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route($rolePrefix . '.freelancer_registration_otp_logs.index') }}" class="nav-link {{ str_starts_with($route_name, 'admin.freelancer_registration_otp_logs') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Freelancer Verify No.</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route($rolePrefix . '.freelancer_payments.index') }}" class="nav-link {{ str_starts_with($route_name, 'admin.freelancer_payments.') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Freelancer Payments</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route($rolePrefix . '.location_attendance.index') }}" class="nav-link {{ str_starts_with($route_name, 'admin.location_attendance.') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Freelancer &amp; Vendor Attendance</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route($rolePrefix . '.jobproc.index') }}" class="nav-link {{ str_starts_with($route_name, 'admin.jobproc.') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Job Request</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item">
                         <a href="{{route($rolePrefix . '.bulk_registration.index')}}" class="nav-link {{ strpos($route_name, 'bulk_registration') !== false ? 'active' : '' }}">
                            <i class="nav-icon fas fa-money-bill-wave"></i>
                            <p>Bulk Price / Reg</p>
                        </a>
                    </li>
                    <li class="nav-item">
                         <a href="{{route($rolePrefix . '.technical-support.index')}}" class="nav-link {{ strpos($route_name, 'technical-support') !== false ? 'active' : '' }}">
                            <i class="nav-icon fas fa-headset"></i>
                            <p>Technical Support</p>
                        </a>
                    </li>
                    @endif
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
        function initSidebarRealtime() {
        if (window.ChatRealtime && window.chatRealtimeConfig && window.chatRealtimeConfig.enabled) {
            window.ChatRealtime.subscribeUserChannel({{ Auth::id() }}, function(payload, eventName) {
                updateSidebarUnreadBadgeAdmin();
            });
            console.log('Sidebar real-time subscribed for updateSidebarUnreadBadgeAdmin');
        } else if (window.chatRealtimeConfig && window.chatRealtimeConfig.enabled) {
            // Real-time enabled but not yet loaded, wait and retry
            setTimeout(initSidebarRealtime, 500);
        } else if (window.chatRealtimeConfig && !window.chatRealtimeConfig.enabled) {
            // Fallback to slow polling ONLY if real-time is explicitly disabled
            setInterval(updateSidebarUnreadBadgeAdmin, 30000);
        }
    }
    initSidebarRealtime();
</script>

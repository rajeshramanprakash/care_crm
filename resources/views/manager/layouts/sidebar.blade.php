@php
$auth_user = Auth::user();
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
    <a href="{{route('manager.dashboard')}}" class="brand-link text-center">
        <img src="{{asset('images/carelix-logo.png')}}" alt="Carelix Logo" style="width: 40% !important; filter: drop-shadow(1px 1px 5px #ffffff50);">
    </a>
    <div class="sidebar">

        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <li class="nav-item">
                    <a href="{{route('manager.dashboard')}}" class="nav-link {{$uri == "dashboard" ? 'active' : ''}}">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{route('manager.leads.index')}}" class="nav-link {{ str_starts_with($route_name, 'manager.leads.') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-user-friends"></i>
                        <p>Leads</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{route('manager.referral_leads.index')}}" class="nav-link {{ strpos($route_name, 'referral_leads') !== false ? 'active' : '' }}">
                        <i class="nav-icon fas fa-share-square"></i>
                        <p>Referral Leads</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{route('manager.chat.index')}}" class="nav-link {{ strpos($route_name, 'chat') !== false && strpos($route_name, 'b2b_corporate_chat') === false ? 'active' : '' }}">
                        <i class="nav-icon fas fa-comments"></i>
                        <p>
                            Chat
                            <span id="sidebar-unread-badge-manager" class="badge unread-count-sidebar" style="display:none; margin-left:8px; background:#25d366; color:#fff; font-weight:bold;"></span>
                        </p>
                    </a>
                </li>
                @if($showB2bCorporateStaffChat ?? false)
                <li class="nav-item">
                    <a href="{{ route('crm.b2b_corporate_chat.index') }}" class="nav-link {{ strpos($route_name, 'b2b_corporate_chat') !== false ? 'active' : '' }}">
                        <i class="nav-icon fas fa-building"></i>
                        <p>B2B Corporate Chat</p>
                    </a>
                </li>
                @endif

                <li class="nav-item">
                    <a href="{{route('manager.staff.chats')}}" class="nav-link {{ strpos($route_name, 'staff.chats') !== false ? 'active' : '' }}">
                        <i class="nav-icon fas fa-headset"></i>
                        <p>Sales Staff Chats</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{route('admin.all_whatsapp_chats.index')}}" class="nav-link {{ strpos($route_name, 'all_whatsapp_chats.index') !== false ? 'active' : '' }}">
                       <i class="nav-icon fas fa-users"></i>
                       <p>Whatsapp</p>
                   </a>
               </li>
               <li class="nav-item">
                   <a href="{{route('manager.break_logs.index')}}" class="nav-link {{ strpos($route_name, 'break_logs') !== false ? 'active' : '' }}">
                       <i class="nav-icon fas fa-clock"></i>
                       <p>Break Logs</p>
                   </a>
               </li>
               <li class="nav-item">
                   <a href="{{route('manager.duty_logs.index')}}" class="nav-link {{ strpos($route_name, 'duty_logs') !== false ? 'active' : '' }}">
                       <i class="nav-icon fas fa-user-check"></i>
                       <p>Duty Logs</p>
                   </a>
               </li>
               <li class="nav-item">
                   <a href="{{route('manager.users.index')}}" class="nav-link {{ strpos($route_name, 'users') !== false ? 'active' : '' }}">
                       <i class="nav-icon fas fa-user-check"></i>
                       <p>users</p>
                   </a>
               </li>
               <li class="nav-item">
                   <a href="{{route('manager.technical-support.index')}}" class="nav-link {{ strpos($route_name, 'technical-support') !== false ? 'active' : '' }}">
                       <i class="nav-icon fas fa-headset"></i>
                       <p>Technical Support</p>
                   </a>
               </li>
            </ul>
        </nav>
        <div class="user-panel mt-3 pb-3 mb-3 d-flex align-items-center">
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
            // Optionally, save state to localStorage
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

    function updateSidebarUnreadBadgeManager() {
        fetch('{{ route('manager.chat.unread-counts') }}')
            .then(response => response.json())
            .then(counts => {
                let total = 0;
                Object.values(counts).forEach(count => total += count);
                const badge = document.getElementById('sidebar-unread-badge-manager');
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
    updateSidebarUnreadBadgeManager();
        function initSidebarRealtime() {
        if (window.ChatRealtime && window.chatRealtimeConfig && window.chatRealtimeConfig.enabled) {
            window.ChatRealtime.subscribeUserChannel({{ Auth::id() }}, function(payload, eventName) {
                updateSidebarUnreadBadgeManager();
            });
            console.log('Sidebar real-time subscribed for updateSidebarUnreadBadgeManager');
        } else if (window.chatRealtimeConfig && window.chatRealtimeConfig.enabled) {
            // Real-time enabled but not yet loaded, wait and retry
            setTimeout(initSidebarRealtime, 500);
        } else if (window.chatRealtimeConfig && !window.chatRealtimeConfig.enabled) {
            // Fallback to slow polling ONLY if real-time is explicitly disabled
            setInterval(updateSidebarUnreadBadgeManager, 30000);
        }
    }
    initSidebarRealtime();
</script>

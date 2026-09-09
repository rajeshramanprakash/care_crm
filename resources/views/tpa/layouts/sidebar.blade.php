@php
$auth_user = Auth::user();

$uri_arr = explode(".", Route::currentRouteName());
$uri = end($uri_arr);
@endphp
<aside class="main-sidebar sidebar-dark-danger" style="background: var(--wb-dark-red);">
    <a href="{{route('tpa.dashboard')}}" class="brand-link text-center">
                <img src="{{asset('images/logo.png')}}" alt="AdminLTE Logo" style="width: 80% !important; filter: drop-shadow(1px 1px 5px #ffffff50);">

    </a>
    <div class="sidebar">
        <div class="user-panel mt-3 pb-3 mb-3 d-flex align-items-center">
            <div class="image">
                <a href="javascript:void(0);" onclick="handle_view_image('{{$auth_user->profile_image}}', '/change')">
                    <img src="{{$auth_user->profile_image}}" onerror="this.src = null; this.src='{{asset('/images/default-user.png')}}'" class="img-circle elevation-2" alt="User Image" style="width: 43px; height: 43px;">
                </a>
            </div>
            <div class="info text-center py-0">
                <a href="javascript:void(0);" class="d-block">{{$auth_user->f_name}} {{$auth_user->l_name ?: 'N/A'}} - {{$auth_user->get_role->name}}</a>
            </div>
        </div>
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <li class="nav-item">
                    <a href="{{route('tpa.dashboard')}}" class="nav-link {{$uri == "dashboard" ? 'active' : ''}}">
                        <i class="nav-icon fas fa-home"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{route('tpa.case.index')}}" class="nav-link {{$uri == "cases" ? 'active' : ''}}">
                        <i class="nav-icon fa-solid fa-file"></i>
                        <p>Cases</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{route('tpa.wallets.index')}}" class="nav-link {{$uri == "wallets" ? 'active' : ''}}">
                        <i class="nav-icon fas fa-wallet"></i>
                        <p>Wallet</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{route('tpa.chat.index')}}" class="nav-link {{$uri == "chat" ? 'active' : ''}}">
                        <i class="nav-icon fas fa-comments"></i>
                        <p>
                            Chat
                            <span id="sidebar-unread-badge-admin" class="badge unread-count-sidebar" style="display:none; margin-left:8px; background:#25d366; color:#fff; font-weight:bold;"></span>
                        </p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{route('tpa.tickets.index')}}" class="nav-link {{$uri == "tickets" ? 'active' : ''}}">
                        <i class="nav-icon fas fa-ticket-alt"></i>
                        <p>
                            Tickets
                            <span id="sidebar-ticket-badge-tpa" class="badge unread-count-sidebar" style="display:none; margin-left:8px; background:#ffc107; color:#000; font-weight:bold;"></span>
                        </p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>

<script>

function updateSidebarUnreadBadgeAdmin() {
        fetch('{{ route('tpa.chat.unread-counts') }}')
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

    function updateSidebarTicketBadgeTpa() {
        fetch('{{ route('tpa.tickets.unread-count') }}')
            .then(response => response.json())
            .then(data => {
                const badge = document.getElementById('sidebar-ticket-badge-tpa');
                if (badge) {
                    const unreadCount = data.count || 0;
                    if (unreadCount > 0) {
                        badge.textContent = unreadCount;
                        badge.style.display = '';
                    } else {
                        badge.style.display = 'none';
                    }
                }
            });
    }
    updateSidebarTicketBadgeTpa();
    setInterval(updateSidebarTicketBadgeTpa, 10000);

    $('.nav-sidebar').tree();
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
</script>

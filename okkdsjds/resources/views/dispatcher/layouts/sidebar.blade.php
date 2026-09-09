@php
$auth_user = Auth::user();

$uri_arr = explode(".", Route::currentRouteName());
$uri = end($uri_arr);
$route_name = Route::currentRouteName();
@endphp
<aside class="main-sidebar sidebar-dark-danger" style="background: var(--wb-dark-red);">
    <a href="{{route('dispatcher.dashboard')}}" class="brand-link text-center">
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
                    <a href="{{route('dispatcher.dashboard')}}" class="nav-link {{$uri == "dashboard" ? 'active' : ''}}">
                        <i class="nav-icon fas fa-home"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
                <li class="nav-item">
                    @if($auth_user->is_courier_enabled)
                    <a href="{{route('dispatcher.courier.index')}}" class="nav-link {{$uri == "courier" ? 'active' : ''}}">
                        <i class="nav-icon fas fa-shipping-fast"></i>
                        <p>Courier</p>
                    </a>
                    @endif
                </li>
                <li class="nav-item">
                    @if($auth_user->is_query_enabled)
                    <a href="{{route('dispatcher.query.index')}}" class="nav-link {{$uri == "query" ? 'active' : ''}}">
                        <i class="nav-icon fas fa-shipping-fast"></i>
                        <p>Query</p>
                    </a>
                    @endif
                </li>
                <li class="nav-item">
                    <a href="{{route('dispatcher.chat.index')}}" class="nav-link {{$uri == "chat" ? 'active' : ''}}">
                        <i class="nav-icon fas fa-comments"></i>
                        <p>
                            Chat
                            <span id="sidebar-unread-badge-dispatcher" class="badge unread-count-sidebar" style="display:none; margin-left:8px; background:#25d366; color:#fff; font-weight:bold;"></span>
                        </p>
                    </a>
                </li>
                <li class="nav-item">
                    @if($auth_user->is_ticket_enabled)
                    <a href="{{route('dispatcher.tickets.index')}}" class="nav-link {{ strpos($route_name, 'tickets') !== false ? 'active' : '' }}">
                        <i class="nav-icon fas fa-ticket-alt"></i>
                        <p>
                            Tickets
                            <span id="sidebar-ticket-badge-dispatcher" class="badge unread-count-sidebar" style="display:none; margin-left:8px; background:#ffc107; color:#000; font-weight:bold;"></span>
                        </p>
                    </a>
                    @endif
                </li>
            </ul>
        </nav>
    </div>
</aside>

<script>
    function updateSidebarUnreadBadgeDispatcher() {
        fetch('{{ route('dispatcher.chat.unread-counts') }}')
            .then(response => response.json())
            .then(counts => {
                let total = 0;
                Object.values(counts).forEach(count => total += count);
                const badge = document.getElementById('sidebar-unread-badge-dispatcher');
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
    updateSidebarUnreadBadgeDispatcher();
    setInterval(updateSidebarUnreadBadgeDispatcher, 5000);

    @if($auth_user->is_ticket_enabled)
    function updateSidebarTicketBadgeDispatcher() {
        fetch('{{ route('dispatcher.tickets.statistics') }}')
            .then(response => response.json())
            .then(stats => {
                const badge = document.getElementById('sidebar-ticket-badge-dispatcher');
                if (badge) {
                    const activeCount = stats.active || 0;
                    if (activeCount > 0) {
                        badge.textContent = activeCount;
                        badge.style.display = '';
                    } else {
                        badge.style.display = 'none';
                    }
                }
            });
    }
    updateSidebarTicketBadgeDispatcher();
    setInterval(updateSidebarTicketBadgeDispatcher, 10000);
    @endif

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

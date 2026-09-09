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
<aside class="main-sidebar sidebar-dark-primary elevation-4" style="background: white;">
    <a href="{{route('sales.dashboard')}}" class="brand-link text-center">
        <img src="{{asset('images/carelix-logo.png')}}" alt="Carelix Logo" style="width: 40% !important; filter: drop-shadow(1px 1px 5px #ffffff50);">
    </a>

    <div class="sidebar">
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <li class="nav-item">
                    <a href="{{ route('operation-manager.dashboard') }}" class="nav-link {{ request()->routeIs('operation-manager.dashboard') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('operation-manager.operation_leads.index') }}" class="nav-link {{ request()->routeIs('operation-manager.operation_leads.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-clipboard-list"></i>
                        <p>Operation Leads</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('operation-manager.referral_leads.index') }}" class="nav-link {{ request()->routeIs('operation-manager.referral_leads.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-share-square"></i>
                        <p>Referral Leads</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('operation-manager.jobproc.all_job_req.index') }}" class="nav-link {{ request()->routeIs('operation-manager.jobproc.all_job_req.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-list"></i>
                        <p>All Job Requests</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('operation-manager.chat.index') }}" class="nav-link {{ request()->routeIs('operation-manager.chat.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-comments"></i>
                        <p>
                            Chat
                            <span id="sidebar-unread-badge-operation-manager" class="badge unread-count-sidebar" style="display:none; margin-left:8px; background:#25d366; color:#fff; font-weight:bold;"></span>
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
                    <a href="{{ route('operation-manager.coordinator.chats') }}" class="nav-link {{ request()->routeIs('operation-manager.coordinator.chats') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-headset"></i>
                        <p>Operation Staff Chats</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{route('admin.all_whatsapp_chats.index')}}" class="nav-link {{ request()->routeIs('admin.all_whatsapp_chats.index') ? 'active' : '' }}">
                       <i class="nav-icon fas fa-users"></i>
                       <p>Whatsapp</p>
                   </a>
               </li>
                <li class="nav-item">
                    <a href="{{route('operation-manager.users.index')}}" class="nav-link {{ request()->routeIs('operation-manager.users.index') ? 'active' : '' }}">
                       <i class="nav-icon fas fa-users"></i>
                       <p>Team</p>
                   </a>
               </li>
               <li class="nav-item">
                   <a href="{{route('operation-manager.break_logs.index')}}" class="nav-link {{ request()->routeIs('operation-manager.break_logs.*') ? 'active' : '' }}">
                       <i class="nav-icon fas fa-clock"></i>
                       <p>Break Logs</p>
                   </a>
               </li>
               <li class="nav-item">
                   <a href="{{route('operation-manager.duty_logs.index')}}" class="nav-link {{ request()->routeIs('operation-manager.duty_logs.*') ? 'active' : '' }}">
                       <i class="nav-icon fas fa-user-check"></i>
                       <p>Duty Logs</p>
                   </a>
               </li>
               <li class="nav-item">
                   <a href="{{route('operation-manager.technical-support.index')}}" class="nav-link {{ request()->routeIs('operation-manager.technical-support.*') ? 'active' : '' }}">
                       <i class="nav-icon fas fa-headset"></i>
                       <p>Technical Support</p>
                   </a>
               </li>
            </ul>
        </nav>
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                @if(Auth::user()->profile_image)
                    <img src="{{ asset('storage/' . Auth::user()->profile_image) }}" class="img-circle elevation-2" alt="User Image">
                @else
                    <img src="{{ asset('adminlte/img/user2-160x160.jpg') }}" class="img-circle elevation-2" alt="User Image">
                @endif
            </div>
            <div class="info">
                <a href="#" class="d-block">{{ Auth::user()->name }}</a>
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

    function updateSidebarUnreadBadgeOperationManager() {
        fetch('{{ route('operation-manager.chat.unread-counts') }}')
            .then(response => response.json())
            .then(counts => {
                let total = 0;
                Object.values(counts).forEach(count => total += count);
                const badge = document.getElementById('sidebar-unread-badge-operation-manager');
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
    updateSidebarUnreadBadgeOperationManager();
        function initSidebarRealtime() {
        if (window.ChatRealtime && window.chatRealtimeConfig && window.chatRealtimeConfig.enabled) {
            window.ChatRealtime.subscribeUserChannel({{ Auth::id() }}, function(payload, eventName) {
                updateSidebarUnreadBadgeOperationManager();
            });
            console.log('Sidebar real-time subscribed for updateSidebarUnreadBadgeOperationManager');
        } else if (window.chatRealtimeConfig && window.chatRealtimeConfig.enabled) {
            // Real-time enabled but not yet loaded, wait and retry
            setTimeout(initSidebarRealtime, 500);
        } else if (window.chatRealtimeConfig && !window.chatRealtimeConfig.enabled) {
            // Fallback to slow polling ONLY if real-time is explicitly disabled
            setInterval(updateSidebarUnreadBadgeOperationManager, 30000);
        }
    }
    initSidebarRealtime();
</script>

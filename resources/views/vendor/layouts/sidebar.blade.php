@php
    $vendorId = Session::get('vendor_id');
    $vendorName = Session::get('vendor_name');
    $vendor = $vendorId ? \App\Models\Vendor::find($vendorId) : null;
    $uri_arr = explode('.', Route::currentRouteName() ?? '');
    $uri = end($uri_arr);
    $route_name = Route::currentRouteName() ?? '';
    $profileImage = ($vendor && $vendor->profile_image)
        ? asset('storage/' . $vendor->profile_image)
        : asset('images/default-user.png');
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
    .user-panel .image img {
        width: 43px;
        height: 43px;
        object-fit: cover;
    }
    .navbar-dark .navbar-nav .nav-link,
    .main-header.navbar .navbar-nav .nav-link {
        color: #F07F28 !important;
    }
    .main-header.navbar .navbar-nav .nav-link:hover {
        color: #d96a1a !important;
    }
</style>
<aside class="main-sidebar sidebar-dark-danger" style="background: #ffffff;">
    <a href="{{ route('vendor.dashboard') }}" class="brand-link text-center">
        <img src="{{ asset('images/carelix-logo.png') }}" alt="Carelix Logo" style="width: 40% !important; filter: drop-shadow(1px 1px 5px #ffffff50);">
    </a>
    <div class="sidebar">
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <li class="nav-item">
                    <a href="{{ route('vendor.dashboard') }}" class="nav-link {{ $uri === 'dashboard' ? 'active' : '' }}">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('vendor.personal-details') }}" class="nav-link {{ str_contains($route_name, 'personal-details') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-user"></i>
                        <p>Personal Details</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('vendor.bank-details') }}" class="nav-link {{ str_contains($route_name, 'bank-details') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-university"></i>
                        <p>Bank Account Details</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('vendor.assigned-leads') }}" class="nav-link {{ str_contains($route_name, 'assigned-leads') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-list-alt"></i>
                        <p>Assigned Leads</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('vendor.payment-details') }}" class="nav-link {{ str_contains($route_name, 'payment-details') || str_contains($route_name, 'statement') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-rupee-sign"></i>
                        <p>Payment Details</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('vendor.emergency-details') }}" class="nav-link {{ str_contains($route_name, 'emergency-details') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-phone-alt"></i>
                        <p>Emergency Details</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('vendor.customer-chats') }}" class="nav-link {{ str_contains($route_name, 'customer-chats') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-comments"></i>
                        <p>Customer Chats</p>
                    </a>
                </li>
            </ul>
        </nav>
        <div class="user-panel mt-3 pb-3 mb-3 d-flex align-items-center">
            <div class="image">
                <img src="{{ $profileImage }}"
                     onerror="this.onerror=null; this.src='{{ asset('images/default-user.png') }}'"
                     class="img-circle elevation-2"
                     alt="Vendor">
            </div>
            <div class="info text-center py-0" style="margin-left: 10px;">
                <a href="{{ route('vendor.personal-details') }}" class="d-block" style="font-weight: 600;">{{ $vendorName ?? 'Vendor' }}</a>
                <div style="font-size: 0.85rem; color: #888;">Vendor</div>
            </div>
        </div>
    </div>
</aside>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (!document.body.classList.contains('sidebar-collapse')) {
            document.body.classList.add('sidebar-collapse');
        }
    });

    function initialize_sidebar_collapse() {
        const sidebar_collapsible_elem = document.getElementById('sidebar_collapsible_elem');
        const localstorage_value = localStorage.getItem('sidebar_collapse');
        if (localstorage_value !== null && localstorage_value === 'true') {
            if (sidebar_collapsible_elem) {
                sidebar_collapsible_elem.setAttribute('data-collapse', 0);
            }
            document.body.classList.add('sidebar-collapse');
        }
    }
    initialize_sidebar_collapse();
</script>

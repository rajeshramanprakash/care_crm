<style>
    .main-header.navbar .navbar-nav .nav-link {
        color: #F07F28 !important;
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 40px;
        min-height: 40px;
        padding: 0.4rem 0.65rem;
    }
    .main-header.navbar .navbar-nav .nav-link:hover {
        color: #d96a1a !important;
    }
    .main-header.navbar .navbar-nav .nav-link i {
        font-size: 1.05rem;
    }
    .main-header.navbar .vd-navbar-actions {
        margin-left: auto;
        flex-direction: row;
        align-items: center;
    }
</style>
<nav class="main-header navbar navbar-expand navbar-light border-bottom" style="background: #fff; position: sticky; top: 0; z-index: 1030;">
    <ul class="navbar-nav align-items-center">
        <li class="nav-item">
            <a href="javascript:void(0);" class="nav-link" data-widget="pushmenu" id="sidebar_collapsible_elem"
               data-collapse="1" onclick="handle_sidebar_collapse(this)" title="Toggle menu">
                <i class="fas fa-bars"></i>
            </a>
        </li>
    </ul>

    <ul class="navbar-nav vd-navbar-actions">
        @yield('navbar-right-links')
        <li class="nav-item">
            <a class="nav-link" data-widget="fullscreen" href="#" role="button" title="Fullscreen">
                <i class="fas fa-expand-arrows-alt"></i>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" title="Logout" onclick="return confirm('Are you sure you want to logout?')"
               href="{{ route('logout') }}">
                <i class="fas fa-power-off"></i>
            </a>
        </li>
    </ul>
</nav>

<nav class="main-header navbar navbar-expand navbar-white navbar-light" style="background: var(--wb-renosand); position: sticky; top: 0; z-index: 1030;">
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
    </ul>

    <ul class="navbar-nav ml-auto">
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="far fa-user"></i> {{ Auth::user()->name }}
            </a>
            <li class="nav-item">
                <a class="nav-link" title="logout" onclick="return confirm('Are you sure want to logout?')"
                    href="{{ route('logout') }}">
                    <i class="fas fa-power-off"></i>
                </a>
            </li>
        </li>
        @yield('navbar-right-links')
    </ul>
</nav>

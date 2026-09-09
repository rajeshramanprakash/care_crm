@php
    $auth_user = Auth::guard('TPA')->user();
@endphp
<nav class="main-header navbar navbar-expand navbar-dark navbar-light" style="background: var(--wb-renosand)">
    <ul class="navbar-nav">
        <li class="nav-item">
            <a href="javascript:void(0);" class="nav-link" data-widget="pushmenu" id="sidebar_collapsible_elem"
                data-collapse="1" onclick="handle_sidebar_collapse(this)"><i class="fas fa-bars"></i></a>
        </li>
    </ul>

    <ul class="navbar-nav ml-auto">
        <li class="nav-item">
            <button type="button" class="btn btn-primary"
                style="padding: 3px 0; margin-top:2px; background-color: #ffffff50; border: 1px solid #ffffff80; border-radius: 30px; ">&nbsp;&nbsp;&nbsp;&nbsp;
                <svg fill="#fff" height="25px" width="25px" version="1.1" id="Layer_1"
                    xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                    viewBox="0 0 458.531 458.531" xml:space="preserve">
                    <g id="XMLID_830_">
                        <g>
                            <g>
                                <path d="M336.688,343.962L336.688,343.962c-21.972-0.001-39.848-17.876-39.848-39.848v-66.176
    c0-21.972,17.876-39.847,39.848-39.847h103.83c0.629,0,1.254,0.019,1.876,0.047v-65.922c0-16.969-13.756-30.725-30.725-30.725
    H30.726C13.756,101.49,0,115.246,0,132.215v277.621c0,16.969,13.756,30.726,30.726,30.726h380.943
    c16.969,0,30.725-13.756,30.725-30.726v-65.922c-0.622,0.029-1.247,0.048-1.876,0.048H336.688z" />
                                <path d="M440.518,219.925h-103.83c-9.948,0-18.013,8.065-18.013,18.013v66.176c0,9.948,8.065,18.013,18.013,18.013h103.83
    c9.948,0,18.013-8.064,18.013-18.013v-66.176C458.531,227.989,450.466,219.925,440.518,219.925z M372.466,297.024
    c-14.359,0-25.999-11.64-25.999-25.999s11.64-25.999,25.999-25.999c14.359,0,25.999,11.64,25.999,25.999
    C398.465,285.384,386.825,297.024,372.466,297.024z" />
                                <path
                                    d="M358.169,45.209c-6.874-20.806-29.313-32.1-50.118-25.226L151.958,71.552h214.914L358.169,45.209z" />
                            </g>
                        </g>
                    </g>
                </svg>
                ₹{{ $auth_user->wallet ?? '0' }}&nbsp;&nbsp;&nbsp;&nbsp;
            </button>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                <i class="fas fa-expand-arrows-alt"></i>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" title="logout" onclick="return confirm('Are you sure want to logout?')"
                href="{{ route('logout') }}">
                <i class="fas fa-power-off"></i>
            </a>
        </li>
        @yield('navbar-right-links')
    </ul>
</nav>

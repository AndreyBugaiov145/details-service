<nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom-3 p-3 mb-0">
    <ul class="navbar-nav mr-auto d-flex flex-row bd-highlight">
        <li class="nav-item mr-3">
            <a class="nav-link {{ request()->is('admin') ? 'active' : '' }}" href="/">Home</a>
        </li>
        <li class="nav-item mr-3">
            <a class="nav-link {{ request()->is('admin/parser-setting') ? 'active' : '' }}" href="{{ route('parser-setting') }}">Parser Settings</a>
        </li>
        <li class="nav-item mr-3">
            <a class="nav-link {{ request()->is('admin/reset-password') ? 'active' : '' }}" href="{{ route('reset-password-page') }}">Reset password</a>
        </li>
        <li class="nav-item mr-3">
            <a class="nav-link {{ request()->is('admin/logout') ? 'active' : '' }}" href="{{ route('logout') }}">Logout</a>
        </li>
    </ul>
</nav>

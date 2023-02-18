<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <ul class="navbar-nav mr-auto d-flex flex-row bd-highlight mb-3">
        <li class="nav-item mr-3">
            <a class="nav-link {{ request()->is('admin') ? 'active' : '' }}" href="{{ route('admin') }}">Home</a>
        </li>
        <li class="nav-item mr-3">
            <a class="nav-link {{ request()->is('admin/parser-setting') ? 'active' : '' }}" href="{{ route('parser-setting') }}">Parser Settings</a>
        </li>
        <li class="nav-item mr-3">
            <a class="nav-link {{ request()->is('admin/reset-password') ? 'active' : '' }}" href="{{ route('reset-password-page') }}">Reset password</a>
        </li>
    </ul>
</nav>

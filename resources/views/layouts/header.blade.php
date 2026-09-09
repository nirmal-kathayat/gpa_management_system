{{--
    Fixed topbar: the page title on the left, the account menu on the right.

    The title falls back to the section name derived from the current route, so
    a view only needs @section('page-title') when it wants something different.
--}}
@php
    $navUser = auth()->user();

    $sectionTitles = [
        'dashboard' => 'Dashboard',
        'students.*' => 'Students',
        'reports.*' => 'Report Cards',
        'schools.*' => 'Schools',
        'users.profile' => 'My Profile',
        'users.*' => 'Users',
        'roles.*' => 'Roles',
        'permissions.*' => 'Permissions',
        'subjects.*' => 'Subjects',
        'grades.*' => 'Grade Scale',
        'bulk-import.*' => 'Bulk Import',
    ];

    $defaultTitle = 'GPA Management System';
    foreach ($sectionTitles as $pattern => $title) {
        if (request()->routeIs($pattern)) {
            $defaultTitle = $title;
            break;
        }
    }
@endphp

<header class="top-navbar no-print">
    <div class="d-flex align-items-center gap-3">
        <button class="icon-btn d-lg-none" id="sidebarToggle" aria-label="Toggle navigation">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="page-title">@yield('page-title', $defaultTitle)</h1>
    </div>

    <div class="topbar-right">
        <div class="dropdown">
            <a class="user-chip" href="#" role="button" data-bs-toggle="dropdown"
               data-bs-display="static" aria-expanded="false">
                <span class="user-avatar"><i class="fas fa-user"></i></span>
                <span class="d-none d-md-block">
                    <span class="user-name d-block">{{ $navUser->name }}</span>
                    <span class="user-role d-block">{{ \Illuminate\Support\Str::headline($navUser->role_name ?? 'No role') }}</span>
                </span>
                <i class="fas fa-chevron-down user-caret"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item" href="{{ route('users.profile') }}">
                        <i class="fas fa-user me-2"></i>My Profile
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="dropdown-item">
                            <i class="fas fa-right-from-bracket me-2"></i>Log out
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>

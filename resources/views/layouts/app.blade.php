<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'GPA Management System')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('assets/css/custom/gridtable.css') }}" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    @stack('styles')
</head>

<body>
    @auth
    @php
        $navUser = auth()->user();

        // Falls back to the section name so every page gets a correct topbar
        // title without each view having to declare one.
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
        // Each entry is shown only if the signed-in user holds the permission that
        // guards its route, so the sidebar never links to a 403.
        $navGroups = array_values(array_filter([
            ['label' => null, 'items' => [
                ['route' => 'dashboard', 'match' => 'dashboard', 'icon' => 'fa-gauge-high', 'label' => 'Dashboard'],
            ]],
            ['label' => 'Management', 'icon' => 'fa-folder-open', 'items' => array_values(array_filter([
                $navUser->can('schools.viewAny') ? ['route' => 'schools.index', 'match' => 'schools.*', 'icon' => 'fa-school', 'label' => 'Schools'] : null,
                $navUser->can('students.viewAny') ? ['route' => 'students.index', 'match' => 'students.*', 'icon' => 'fa-user-graduate', 'label' => 'Students'] : null,
            ]))],
            ['label' => 'Academics', 'icon' => 'fa-book-open', 'items' => array_values(array_filter([
                $navUser->can('subjects.viewAny') ? ['route' => 'subjects.index', 'match' => 'subjects.*', 'icon' => 'fa-book', 'label' => 'Subjects'] : null,
                $navUser->can('grades.viewAny') ? ['route' => 'grades.index', 'match' => 'grades.*', 'icon' => 'fa-award', 'label' => 'Grade Scale'] : null,
                $navUser->can('reports.viewAny') ? ['route' => 'reports.index', 'match' => 'reports.*', 'icon' => 'fa-file-lines', 'label' => 'Report Cards'] : null,
            ]))],
            ['label' => 'Tools', 'icon' => 'fa-screwdriver-wrench', 'items' => array_values(array_filter([
                $navUser->can('bulk-import.run') ? ['route' => 'bulk-import.index', 'match' => 'bulk-import.*', 'icon' => 'fa-upload', 'label' => 'Bulk Import'] : null,
            ]))],
            ['label' => 'User Management', 'icon' => 'fa-user-shield', 'items' => array_values(array_filter([
                $navUser->can('users.viewAny') ? ['route' => 'users.index', 'match' => ['users.index', 'users.create', 'users.edit', 'users.show'], 'icon' => 'fa-users', 'label' => 'Users'] : null,
                $navUser->can('roles.viewAny') ? ['route' => 'roles.index', 'match' => 'roles.*', 'icon' => 'fa-user-tag', 'label' => 'Roles'] : null,
                $navUser->can('permissions.viewAny') ? ['route' => 'permissions.index', 'match' => 'permissions.*', 'icon' => 'fa-key', 'label' => 'Permissions'] : null,
            ]))],
        ], fn ($group) => $group && $group['items']));
    @endphp

    <aside class="sidebar no-print" id="sidebar">
        <div class="sidebar-header">
            <a href="{{ route('dashboard') }}" class="sidebar-brand" aria-label="GPA Management System">
                <img class="brand-logo" src="{{ asset('images/logo.svg') }}" alt="" width="36" height="36">
                <span class="brand-word">GPA</span>
            </a>
        </div>

        <nav class="sidebar-nav">
            @foreach($navGroups as $group)
                @php
                    $groupItems = $group['items'];
                    $groupOpen = collect($groupItems)->contains(fn ($item) => request()->routeIs($item['match']));
                @endphp

                @if(!$group['label'])
                    @foreach($groupItems as $item)
                        <a class="nav-link {{ request()->routeIs($item['match']) ? 'active' : '' }}"
                           href="{{ route($item['route']) }}">
                            <i class="fas {{ $item['icon'] }}"></i>{{ $item['label'] }}
                        </a>
                    @endforeach
                @else
                    {{-- <details> keeps the group collapsible without any JavaScript. --}}
                    <details class="nav-group" {{ $groupOpen ? 'open' : '' }}>
                        <summary class="nav-parent {{ $groupOpen ? 'has-active' : '' }}">
                            <i class="fas {{ $group['icon'] }}"></i>
                            <span>{{ $group['label'] }}</span>
                            <i class="fas fa-chevron-down nav-caret"></i>
                        </summary>
                        <div class="nav-children">
                            @foreach($groupItems as $item)
                                <a class="nav-link {{ request()->routeIs($item['match']) ? 'active' : '' }}"
                                   href="{{ route($item['route']) }}">
                                    <i class="fas {{ $item['icon'] }}"></i>{{ $item['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </details>
                @endif
            @endforeach
        </nav>

        <div class="sidebar-footer">GPA Management System</div>
    </aside>

    <div class="main-content" id="mainContent">
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

        <div class="page-body">
            @if(session('success'))
                <div class="flash flash-success no-print">
                    <i class="fas fa-circle-check"></i>{{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="flash flash-error no-print">
                    <i class="fas fa-circle-exclamation"></i>{{ session('error') }}
                </div>
            @endif

            @yield('content')
        </div>
    </div>
    @else
    <div class="container py-4">
        @yield('content')
    </div>
    @endauth

    {{-- TableHelper needs jQuery (see TABLE-HELPER-GUIDE.md §1) and Bootstrap 5. --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('assets/js/table-helper.js') }}"></script>
    <script>
        // Grid rows cannot hold a Blade <form>, so a delete action posts one.
        window.tableDelete = function (url, message) {
            if (message && !window.confirm(message)) return;

            const token = document.querySelector('meta[name="csrf-token"]').content;
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = url;
            form.innerHTML =
                '<input type="hidden" name="_token" value="' + token + '">' +
                '<input type="hidden" name="_method" value="DELETE">';
            document.body.appendChild(form);
            form.submit();
        };

        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.getElementById('sidebarToggle');
            if (!sidebar || !toggle) return;

            toggle.addEventListener('click', function (event) {
                event.stopPropagation();
                sidebar.classList.toggle('show');
            });

            document.addEventListener('click', function (event) {
                if (window.innerWidth > 991) return;
                if (sidebar.contains(event.target)) return;
                sidebar.classList.remove('show');
            });
        });
    </script>
    @stack('scripts')
</body>

</html>

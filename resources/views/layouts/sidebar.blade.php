{{--
    Fixed left navigation.

    Every entry is listed only if the signed-in user holds the permission that
    guards its route, so the sidebar never links to a 403. Groups render as
    <details>, which keeps them collapsible without any JavaScript.
--}}
@php
    $navUser = auth()->user();

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
            $navUser->can('marks.viewAny') ? ['route' => 'marks.index', 'match' => 'marks.*', 'icon' => 'fa-pen-to-square', 'label' => 'Marks Entry'] : null,
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

    {{-- Same height as the page footer, so the two bottom bars read as one line. --}}
    <div class="sidebar-footer">GPA Management System</div>
</aside>

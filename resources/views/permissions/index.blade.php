@extends('layouts.app')

@section('title', 'Permissions - GPA Management System')

@section('content')
<div class="toolbar">
    <div>
        <h2 class="toolbar-title">Permissions</h2>
        <p class="toolbar-sub">
            {{ $permissions->count() }} permissions across {{ collect($groups)->flatten(1)->count() }} modules.
            Grant them to people by editing a <a href="{{ route('roles.index') }}">role</a>.
        </p>
    </div>
</div>

@if(count($missing))
    <div class="flash flash-error">
        <i class="fas fa-circle-exclamation"></i>
        {{ count($missing) }} permissions are defined in the app but missing from the database.
        Run <code>php artisan db:seed --class=RolePermissionSeeder</code> to add them.
    </div>
@endif

<div class="panel">
    <div class="panel-head">
        <span class="panel-title">What each module allows</span>
        <span class="pill pill-muted">Read only</span>
    </div>
    <div class="panel-body">
        @foreach($groups as $groupName => $modules)
            <div class="perm-group">
                <h3 class="perm-group-title">{{ $groupName }}</h3>
                <div class="perm-grid">
                    @foreach($modules as $moduleName => $module)
                        <div class="perm-card is-readonly">
                            <div class="perm-card-head">
                                <span class="perm-card-icon"><i class="fas {{ $module['icon'] }}"></i></span>
                                <span class="perm-card-name">{{ $module['label'] }}</span>
                                <span class="perm-card-count">{{ count($module['abilities']) }}</span>
                            </div>
                            <div class="perm-card-body">
                                @foreach($module['abilities'] as $ability)
                                    @php
                                        $name = "{$moduleName}.{$ability}";
                                        $roles = optional($permissions->get($name))->roles ?? collect();
                                    @endphp
                                    <div class="perm-check" style="align-items:flex-start; flex-direction:column; gap:6px;">
                                        <span style="display:flex; align-items:center; gap:10px; width:100%;">
                                            <strong style="font-weight:500">{{ $abilities[$ability] }}</strong>
                                            <span class="perm-code">{{ $name }}</span>
                                        </span>
                                        <span class="role-chips">
                                            @forelse($roles as $role)
                                                <span class="pill pill-muted">{{ \Illuminate\Support\Str::headline($role->name) }}</span>
                                            @empty
                                                <span class="pill pill-warning">Not granted</span>
                                            @endforelse
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection

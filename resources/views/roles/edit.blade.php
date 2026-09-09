@extends('layouts.app')

@section('title', 'Edit Role - GPA Management System')
@section('page-title', 'Edit Role')

@section('content')
<div class="toolbar">
    <div>
        <h2 class="toolbar-title">{{ \Illuminate\Support\Str::headline($role->name) }}</h2>
        <p class="toolbar-sub">
            {{ $role->users()->count() }} {{ \Illuminate\Support\Str::plural('user', $role->users()->count()) }}
            currently hold this role.
        </p>
    </div>
    <div class="toolbar-actions">
        <a href="{{ route('roles.index') }}" class="btn-ghost"><i class="fas fa-arrow-left"></i>Back</a>
    </div>
</div>

@if($isLocked)
    <div class="panel">
        <div class="panel-head">
            <span class="panel-title">Built-in role</span>
            <span class="pill pill-muted">Read only</span>
        </div>
        <div class="panel-body">
            <p class="toolbar-sub" style="margin:0 0 18px">
                The administrator role passes every permission check by design, so it cannot be renamed,
                edited or deleted. Create a new role if you need a narrower set of permissions.
            </p>
            <div class="perm-grid">
                @foreach($groups as $groupName => $modules)
                    @foreach($modules as $moduleName => $module)
                        <div class="perm-card is-readonly">
                            <div class="perm-card-head">
                                <span class="perm-card-icon"><i class="fas {{ $module['icon'] }}"></i></span>
                                <span class="perm-card-name">{{ $module['label'] }}</span>
                                <span class="perm-card-count">{{ count($module['abilities']) }}/{{ count($module['abilities']) }}</span>
                            </div>
                            <div class="perm-card-body">
                                @foreach($module['abilities'] as $ability)
                                    <span class="perm-check">
                                        <input type="checkbox" class="check" checked disabled>
                                        {{ $abilities[$ability] }}
                                        <span class="perm-code">{{ $moduleName }}.{{ $ability }}</span>
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>
    </div>
@else
    <form method="POST" action="{{ route('roles.update', $role) }}" class="d-flex flex-column gap-4">
        @csrf
        @method('PUT')
        @include('roles._form', ['submitLabel' => 'Save changes'])
    </form>
@endif
@endsection

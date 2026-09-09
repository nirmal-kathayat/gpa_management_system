@extends('layouts.app')

@section('title', 'Edit Role - GPA Management System')
@section('page-title', 'Edit Role')

@section('content')
@if($isLocked)
    <div class="form-card">
        <div class="form-card-head">
            <h2 class="form-card-title">{{ \Illuminate\Support\Str::headline($role->name) }}</h2>
            <p class="form-card-sub">
                The administrator role passes every permission check by design, so it cannot be
                renamed, edited or deleted. Create a new role if you need a narrower set.
            </p>
        </div>

        <div class="form-card-section">
            <span>Permissions</span>
            <span class="pill pill-muted">Read only</span>
        </div>

        <div class="form-card-body">
            <div class="perm-grid">
                @foreach($groups as $modules)
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

        <div class="form-card-foot">
            <a href="{{ route('roles.index') }}" class="btn-ghost">Back to roles</a>
        </div>
    </div>
@else
    <form class="form-card" method="POST" action="{{ route('roles.update', $role) }}">
        @csrf
        @method('PUT')
        @include('roles._form', [
            'title' => 'Edit ' . \Illuminate\Support\Str::headline($role->name),
            'subtitle' => $role->users()->count() . ' ' . \Illuminate\Support\Str::plural('user', $role->users()->count())
                . ' currently hold this role.',
            'submitLabel' => 'Save Changes',
        ])
    </form>
@endif
@endsection

@extends('layouts.app')

@section('title', $user->name . ' - GPA Management System')
@section('page-title', 'User')

@php
    $role = $user->roles->first();
    $grouped = $role ? $role->permissions->groupBy(fn ($permission) => explode('.', $permission->name)[0]) : collect();
    $modules = \App\Support\Permissions::modules();
@endphp

@section('content')
<div class="toolbar">
    <div>
        <h2 class="toolbar-title">{{ $user->name }}</h2>
        <p class="toolbar-sub">{{ '@' . $user->username }} &middot; {{ $user->email }}</p>
    </div>
    <div class="toolbar-actions">
        <a href="{{ route('users.edit', $user) }}" class="btn-primary-flat"><i class="fas fa-pen"></i>Edit</a>
        <a href="{{ route('users.index') }}" class="btn-ghost"><i class="fas fa-arrow-left"></i>Back</a>
    </div>
</div>

<div class="panel">
    <div class="panel-head">
        <span class="panel-title">Account</span>
        <span class="pill {{ $user->is_active ? 'pill-positive' : 'pill-danger' }}">
            {{ $user->is_active ? 'Active' : 'Inactive' }}
        </span>
    </div>
    <div class="panel-body">
        <div class="detail-grid">
            <div class="detail-item">
                <div class="detail-label">Role</div>
                <div class="detail-value">{{ $role ? \Illuminate\Support\Str::headline($role->name) : 'No role assigned' }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">School</div>
                <div class="detail-value">{{ $user->school->name ?? 'All schools' }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Phone</div>
                <div class="detail-value">{{ $user->phone ?: '—' }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Last signed in</div>
                <div class="detail-value">{{ $user->last_login_at?->format('d M Y, g:i a') ?: 'Never' }}</div>
            </div>
            <div class="detail-item is-wide">
                <div class="detail-label">Address</div>
                <div class="detail-value">{{ $user->address ?: '—' }}</div>
            </div>
        </div>
    </div>
</div>

<div class="panel">
    <div class="panel-head">
        <span class="panel-title">What this user can do</span>
        @if($role)
            <a class="btn-ghost" href="{{ route('roles.edit', $role) }}">
                <i class="fas fa-user-tag"></i>Edit the {{ \Illuminate\Support\Str::headline($role->name) }} role
            </a>
        @endif
    </div>
    <div class="panel-body">
        @if($user->isAdmin())
            <p class="toolbar-sub" style="margin:0">
                Administrators pass every permission check, so this account can reach every screen.
            </p>
        @elseif($grouped->isEmpty())
            <div class="empty-state">
                <i class="fas fa-key"></i>
                <p>This user's role grants no permissions yet.</p>
            </div>
        @else
            <div class="perm-grid">
                @foreach($grouped as $moduleName => $permissions)
                    <div class="perm-card is-readonly">
                        <div class="perm-card-head">
                            <span class="perm-card-icon">
                                <i class="fas {{ $modules[$moduleName]['icon'] ?? 'fa-key' }}"></i>
                            </span>
                            <span class="perm-card-name">{{ $modules[$moduleName]['label'] ?? ucfirst($moduleName) }}</span>
                            <span class="perm-card-count">{{ $permissions->count() }}</span>
                        </div>
                        <div class="perm-card-body">
                            @foreach($permissions as $permission)
                                @php $ability = explode('.', $permission->name)[1] ?? ''; @endphp
                                <span class="perm-check">
                                    <i class="fas fa-check" style="color:var(--positive); font-size:11px; width:18px; text-align:center;"></i>
                                    {{ \App\Support\Permissions::ABILITIES[$ability] ?? ucfirst($ability) }}
                                    <span class="perm-code">{{ $permission->name }}</span>
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection

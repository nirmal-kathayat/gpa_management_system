@extends('layouts.app')

@section('title', 'Roles - GPA Management System')

@section('content')
<div class="toolbar">
    <div>
        <h2 class="toolbar-title">Roles</h2>
        <p class="toolbar-sub">
            {{ $roles->count() }} {{ \Illuminate\Support\Str::plural('role', $roles->count()) }},
            {{ $totalPermissions }} permissions available to grant.
        </p>
    </div>
    <div class="toolbar-actions">
        <a href="{{ route('roles.create') }}" class="btn-primary-flat"><i class="fas fa-plus"></i>New Role</a>
    </div>
</div>

<div class="panel">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Role</th>
                    <th>Permissions</th>
                    <th>Users</th>
                    <th class="cell-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($roles as $role)
                    @php $isLocked = in_array($role->name, $locked, true); @endphp
                    <tr>
                        <td>
                            <div class="identity">
                                <span class="avatar-sm"><i class="fas fa-user-tag"></i></span>
                                <div>
                                    <div class="cell-strong">{{ \Illuminate\Support\Str::headline($role->name) }}</div>
                                    <div class="identity-sub">{{ $role->name }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($isLocked)
                                <span class="pill pill-positive">Full access</span>
                            @else
                                <span class="cell-muted">{{ $role->permissions_count }} of {{ $totalPermissions }}</span>
                            @endif
                        </td>
                        <td class="cell-muted">{{ $role->users_count }}</td>
                        <td class="cell-actions">
                            <span class="row-actions">
                                <a class="icon-action" href="{{ route('roles.edit', $role) }}"
                                   title="{{ $isLocked ? 'View permissions' : 'Edit role' }}">
                                    <i class="fas {{ $isLocked ? 'fa-eye' : 'fa-pen' }}"></i>
                                </a>
                                @if($isLocked)
                                    <button type="button" class="icon-action" disabled title="Built-in role">
                                        <i class="fas fa-lock"></i>
                                    </button>
                                @else
                                    <form action="{{ route('roles.destroy', $role) }}" method="POST"
                                          onsubmit="return confirm('Delete the {{ \Illuminate\Support\Str::headline($role->name) }} role?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="icon-action is-danger" title="Delete role">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">
                            <div class="empty-state">
                                <i class="fas fa-user-tag"></i>
                                <p>No roles yet. Create one to start granting permissions.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

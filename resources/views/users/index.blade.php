@extends('layouts.app')

@section('title', 'Users - GPA Management System')

@section('content')
<div class="toolbar">
    <div>
        <h2 class="toolbar-title">Users</h2>
        <p class="toolbar-sub">{{ $users->total() }} {{ \Illuminate\Support\Str::plural('account', $users->total()) }} in the system.</p>
    </div>
    <div class="toolbar-actions">
        <a href="{{ route('users.create') }}" class="btn-primary-flat"><i class="fas fa-plus"></i>New User</a>
    </div>
</div>

<div class="panel">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>School</th>
                    <th>Status</th>
                    <th class="cell-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>
                            <div class="identity">
                                <span class="avatar-sm">{{ \Illuminate\Support\Str::of($user->name)->explode(' ')->take(2)->map(fn ($word) => \Illuminate\Support\Str::substr($word, 0, 1))->implode('') }}</span>
                                <div>
                                    <div class="cell-strong">{{ $user->name }}</div>
                                    <div class="identity-sub">{{ '@' . $user->username }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="cell-muted">{{ $user->email }}</td>
                        <td>
                            @if($user->role_name)
                                <span class="pill pill-muted">{{ \Illuminate\Support\Str::headline($user->role_name) }}</span>
                            @else
                                <span class="pill pill-warning">No role</span>
                            @endif
                        </td>
                        <td class="cell-muted">{{ $user->school->name ?? 'All schools' }}</td>
                        <td>
                            <span class="pill {{ $user->is_active ? 'pill-positive' : 'pill-danger' }}">
                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="cell-actions">
                            <span class="row-actions">
                                <a class="icon-action" href="{{ route('users.show', $user) }}" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a class="icon-action" href="{{ route('users.edit', $user) }}" title="Edit">
                                    <i class="fas fa-pen"></i>
                                </a>
                                @if($user->id === auth()->id())
                                    <button type="button" class="icon-action" disabled title="This is your account">
                                        <i class="fas fa-lock"></i>
                                    </button>
                                @else
                                    <form action="{{ route('users.destroy', $user) }}" method="POST"
                                          onsubmit="return confirm('Delete {{ $user->name }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="icon-action is-danger" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <i class="fas fa-users"></i>
                                <p>No users yet.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
        <div class="pager">{{ $users->links() }}</div>
    @endif
</div>
@endsection

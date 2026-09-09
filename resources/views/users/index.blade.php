@extends('layouts.app')

@section('title', 'Users - GPA Management System')

@section('content')
<div class="toolbar">
    <div>
        <h2 class="toolbar-title">Users</h2>
        <p class="toolbar-sub">Accounts that can sign in, and the role each one holds.</p>
    </div>
    @can('users.create')
        <div class="toolbar-actions">
            <a href="{{ route('users.create') }}" class="btn-primary-flat"><i class="fas fa-plus"></i>New User</a>
        </div>
    @endcan
</div>

<div class="panel">
    <div class="panel-body">
        <div id="users-grid" class="cq-grid"></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(function () {
        new TableHelper({
            containerId: 'users-grid',
            apiUrl: '{{ route('users.list') }}',
            perPage: 10,
            pagination: true,
            enableCheckbox: false,
            autoInitDatePickers: false,
            emptyMessage: 'No users found',
            search: { placeholder: 'Search name, username or email…' },
            enableSortColumns: ['name', 'username', 'email'],

            columns: [
                { name: 'S.no', isSerialNo: true, width: '60px', align: 'center' },
                { name: 'Name', field: 'name' },
                { name: 'Username', field: 'username', render: (r) => '@' + th_escapeHtml(r.username) },
                { name: 'Email', field: 'email' },
                {
                    name: 'Role', field: 'role',
                    render: (r) => r.role
                        ? `<span class="badge bg-secondary">${th_escapeHtml(r.role)}</span>`
                        : '<span class="badge bg-warning text-dark">No role</span>'
                },
                { name: 'School', field: 'school' },
                {
                    name: 'Status', field: 'is_active',
                    render: (r) => r.is_active
                        ? '<span class="badge bg-success">Active</span>'
                        : '<span class="badge bg-danger">Inactive</span>'
                },
                {
                    name: 'Action',
                    type: 'actions',
                    actions: [
                        { type: 'view', showLabel: false, title: 'View', url: '/users/{id}' },
                        @can('users.update')
                        { type: 'edit', showLabel: false, title: 'Edit', url: '/users/{id}/edit' },
                        @endcan
                        @can('users.delete')
                        {
                            type: 'delete', showLabel: false, title: 'Delete',
                            // You cannot delete the account you are signed in as.
                            visible: (row) => !row.is_self,
                            onClick: (row) => window.tableDelete('/users/' + row.id, {
                                title: 'Delete user?',
                                message: row.name + ' will lose access immediately.'
                            })
                        },
                        @endcan
                    ]
                }
            ],

            filters: {
                autoGenerateColumnFilters: false,
                columnFilters: [
                    { field: 'name', type: 'text', param: 'name', placeholder: 'Name' },
                    { field: 'username', type: 'text', param: 'username', placeholder: 'Username' },
                    { field: 'email', type: 'text', param: 'email', placeholder: 'Email' },
                    {
                        field: 'role', type: 'select', param: 'role', allowBlank: true,
                        options: [
                            @foreach($roles as $role)
                            { value: '{{ $role->name }}', label: '{{ \Illuminate\Support\Str::headline($role->name) }}' },
                            @endforeach
                        ]
                    },
                    {
                        field: 'is_active', type: 'select', param: 'is_active', allowBlank: true,
                        options: [{ value: '1', label: 'Active' }, { value: '0', label: 'Inactive' }]
                    }
                ]
            }
        });
    });
</script>
@endpush

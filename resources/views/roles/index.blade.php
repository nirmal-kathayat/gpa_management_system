@extends('layouts.app')

@section('title', 'Roles - GPA Management System')

@section('content')
<div class="toolbar">
    <div>
        <h2 class="toolbar-title">Roles</h2>
        <p class="toolbar-sub">
            {{ $roleCount }} {{ \Illuminate\Support\Str::plural('role', $roleCount) }},
            {{ $totalPermissions }} permissions available to grant.
        </p>
    </div>
    @can('roles.create')
        <div class="toolbar-actions">
            <a href="{{ route('roles.create') }}" class="btn-primary-flat"><i class="fas fa-plus"></i>New Role</a>
        </div>
    @endcan
</div>

<div class="panel">
    <div class="panel-body">
        <div id="roles-grid" class="cq-grid"></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(function () {
        new TableHelper({
            containerId: 'roles-grid',
            apiUrl: '{{ route('roles.list') }}',
            perPage: 10,
            pagination: true,
            enableCheckbox: false,
            autoInitDatePickers: false,
            emptyMessage: 'No roles yet',
            search: { placeholder: 'Search role…' },
            enableSortColumns: ['name', 'permissions_count', 'users_count'],

            columns: [
                { name: 'S.no', isSerialNo: true, width: '60px', align: 'center' },
                {
                    name: 'Role', field: 'name',
                    render: (r) => `<strong>${th_escapeHtml(r.name)}</strong>` +
                        (r.locked ? ' <span class="badge bg-secondary">Built in</span>' : '')
                },
                {
                    name: 'Permissions', field: 'permissions',
                    render: (r) => r.locked
                        ? '<span class="badge bg-success">Full access</span>'
                        : th_escapeHtml(r.permissions)
                },
                { name: 'Users', field: 'users_count', align: 'center', render: (r) => r.users_count ?? 0 },
                {
                    name: 'Action',
                    type: 'actions',
                    actions: [
                        @can('roles.update')
                        {
                            type: 'edit', showLabel: false,
                            icon: 'fas fa-pen',
                            title: 'Edit role',
                            url: '/roles/{id}/edit'
                        },
                        @endcan
                        @can('roles.delete')
                        {
                            type: 'delete', showLabel: false, title: 'Delete role',
                            // The built-in admin role cannot be removed.
                            visible: (row) => !row.locked,
                            onClick: (row) => window.tableDelete('/roles/' + row.id, {
                                title: 'Delete role?',
                                message: 'The ' + row.name + ' role and its permissions will be removed.'
                            })
                        },
                        @endcan
                    ]
                }
            ],

            filters: {
                autoGenerateColumnFilters: false,
                columnFilters: [
                    { field: 'name', type: 'text', param: 'name', placeholder: 'Role' }
                ]
            }
        });
    });
</script>
@endpush

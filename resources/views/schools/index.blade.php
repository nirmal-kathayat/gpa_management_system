@extends('layouts.app')

@section('title', 'Schools - GPA Management System')

@section('content')
<div class="toolbar">
    <div>
        <h2 class="toolbar-title">Schools</h2>
        <p class="toolbar-sub">Every school on the system and how many students it holds.</p>
    </div>
    @can('schools.create')
        <div class="toolbar-actions">
            <button type="button" class="btn-primary-flat" id="addSchoolBtn">
                <i class="fas fa-plus"></i>Add School
            </button>
        </div>
    @endcan
</div>

<div class="panel">
    <div class="panel-body">
        <div id="schools-grid" class="cq-grid"></div>
    </div>
</div>

@canany(['schools.create', 'schools.update'])
    @include('schools._form_modal')
@endcanany
@endsection

@push('scripts')
<script>
    $(function () {
        new TableHelper({
            containerId: 'schools-grid',
            apiUrl: '{{ route('schools.list') }}',
            perPage: 10,
            pagination: true,
            enableCheckbox: false,
            autoInitDatePickers: false,
            emptyMessage: 'No schools found',
            search: { placeholder: 'Search name, address or email…' },
            enableSortColumns: ['name', 'students_count'],

            columns: [
                { name: 'S.no', isSerialNo: true, width: '60px', align: 'center' },
                { name: 'Name', field: 'name' },
                { name: 'Address', field: 'address', render: (r) => th_escapeHtml(r.address || '—') },
                { name: 'Phone', field: 'phone', render: (r) => th_escapeHtml(r.phone || '—') },
                { name: 'Email', field: 'email', render: (r) => th_escapeHtml(r.email || '—') },
                {
                    name: 'Students', field: 'students_count', align: 'center',
                    render: (r) => `<span class="badge bg-secondary">${r.students_count}</span>`
                },
                {
                    name: 'Action',
                    type: 'actions',
                    actions: [
                        { type: 'view', showLabel: false, title: 'View', url: '/schools/{id}' },
                        @can('schools.update')
                        // No url - the modal opens from the click and fills itself from this row.
                        { type: 'edit', showLabel: false, title: 'Edit' },
                        @endcan
                        @can('schools.delete')
                        {
                            type: 'delete', showLabel: false, title: 'Delete',
                            onClick: (row) => window.tableDelete('/schools/' + row.id, {
                                title: 'Delete school?',
                                message: row.name + ' will be removed, along with every student in it.'
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
                    { field: 'address', type: 'text', param: 'address', placeholder: 'Address' },
                    { field: 'phone', type: 'text', param: 'phone', placeholder: 'Phone' },
                    { field: 'email', type: 'text', param: 'email', placeholder: 'Email' }
                ]
            }
        });
    });
</script>
@endpush

@extends('layouts.app')

@section('title', 'Students - GPA Management System')

@section('content')
<div class="toolbar">
    <div>
        <h2 class="toolbar-title">Students</h2>
        <p class="toolbar-sub">Search, filter and sort every student you have access to.</p>
    </div>
    @can('students.create')
        <div class="toolbar-actions">
            <a href="{{ route('students.create') }}" class="btn-primary-flat"><i class="fas fa-plus"></i>Add Student</a>
        </div>
    @endcan
</div>

<div class="panel">
    <div class="panel-body">
        <div id="students-grid" class="cq-grid"></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(function () {
        new TableHelper({
            containerId: 'students-grid',
            apiUrl: '{{ route('students.list') }}',
            perPage: 10,
            pagination: true,
            enableCheckbox: false,
            autoInitDatePickers: false,
            emptyMessage: 'No students found',
            search: { placeholder: 'Search name, roll no, class or symbol no…' },
            enableSortColumns: ['name', 'class', 'roll_number'],

            columns: [
                { name: 'S.no', isSerialNo: true, width: '60px', align: 'center' },
                { name: 'Name', field: 'name' },
                { name: 'Class', field: 'class' },
                { name: 'Section', field: 'section' },
                { name: 'Roll No.', field: 'roll_number' },
                { name: 'School', field: 'school' },
                {
                    name: 'Status', field: 'is_active',
                    render: (r) => r.is_active
                        ? '<span class="badge bg-success">Enrolled</span>'
                        : '<span class="badge bg-secondary">Left</span>'
                },
                {
                    name: 'Action',
                    type: 'actions',
                    actions: [
                        { type: 'view', showLabel: false, title: 'View', url: '/students/{id}' },
                        @can('students.update')
                        { type: 'edit', showLabel: false, title: 'Edit', url: '/students/{id}/edit' },
                        @endcan
                        @can('students.delete')
                        {
                            type: 'delete', showLabel: false, title: 'Delete',
                            onClick: (row) => window.tableDelete('/students/' + row.id, {
                                title: 'Delete student?',
                                message: row.name + ' will be removed'
                                    + (row.reports_count
                                        ? ', along with ' + row.reports_count + ' report card' + (row.reports_count === 1 ? '' : 's')
                                        : '')
                                    + '. This cannot be undone.'
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
                    { field: 'class', type: 'text', param: 'class', placeholder: 'Class' },
                    { field: 'section', type: 'text', param: 'section', placeholder: 'Section' },
                    { field: 'roll_number', type: 'text', param: 'roll_number', placeholder: 'Roll no.' },
                    { field: 'school', type: 'text', param: 'school', placeholder: 'School' },
                    {
                        field: 'is_active', type: 'select', param: 'is_active', allowBlank: true,
                        options: [{ value: '1', label: 'Enrolled' }, { value: '0', label: 'Left' }]
                    }
                ]
            }
        });
    });
</script>
@endpush

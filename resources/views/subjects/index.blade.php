@extends('layouts.app')

@section('title', 'Subjects - GPA Management System')

@section('content')
<div class="toolbar">
    <div>
        <h2 class="toolbar-title">Subjects</h2>
        <p class="toolbar-sub">Subjects available when building a report card.</p>
    </div>
    @can('subjects.create')
        <div class="toolbar-actions">
            <button type="button" class="btn-primary-flat" id="addSubjectBtn">
                <i class="fas fa-plus"></i>Add Subject
            </button>
        </div>
    @endcan
</div>

<div class="panel">
    <div class="panel-body">
        <div id="subjects-grid" class="cq-grid"></div>
    </div>
</div>

@canany(['subjects.create', 'subjects.update'])
    @include('subjects._form_modal')
@endcanany
@endsection

@push('scripts')
<script>
    $(function () {
        new TableHelper({
            containerId: 'subjects-grid',
            apiUrl: '{{ route('subjects.list') }}',
            perPage: 10,
            pagination: true,
            enableCheckbox: false,
            autoInitDatePickers: false,
            emptyMessage: 'No subjects found',
            search: { placeholder: 'Search name or code…' },
            enableSortColumns: ['name', 'code', 'full_marks'],

            columns: [
                { name: 'S.no', isSerialNo: true, width: '60px', align: 'center' },
                { name: 'Name', field: 'name' },
                { name: 'Code', field: 'code', render: (r) => `<code>${th_escapeHtml(r.code)}</code>` },
                { name: 'Full Marks', field: 'full_marks', align: 'right', render: (r) => r.full_marks ?? 0 },
                { name: 'Pass Marks', field: 'pass_marks', align: 'right', render: (r) => r.pass_marks ?? 0 },
                {
                    name: 'Status', field: 'is_active',
                    render: (r) => r.is_active
                        ? '<span class="badge bg-success">Active</span>'
                        : '<span class="badge bg-secondary">Inactive</span>'
                },
                {
                    name: 'Action',
                    type: 'actions',
                    actions: [
                        { type: 'view', showLabel: false, title: 'View', url: '/subjects/{id}' },
                        @can('subjects.update')
                        // No url - the modal opens from the click and fills itself from this row.
                        { type: 'edit', showLabel: false, title: 'Edit' },
                        @endcan
                        @can('subjects.delete')
                        {
                            type: 'delete', showLabel: false, title: 'Delete',
                            onClick: (row) => window.tableDelete('/subjects/' + row.id, {
                                title: 'Delete subject?',
                                message: row.name + ' will no longer be available on new report cards.'
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
                    { field: 'code', type: 'text', param: 'code', placeholder: 'Code' },
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

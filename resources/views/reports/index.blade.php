@extends('layouts.app')

@section('title', 'Report Cards - GPA Management System')

@section('content')
<div class="toolbar">
    <div>
        <h2 class="toolbar-title">Student Reports</h2>
        <p class="toolbar-sub">Manage and view all student academic reports.</p>
    </div>
    @can('reports.create')
        <div class="toolbar-actions">
            <a href="{{ route('reports.create') }}" class="btn-primary-flat"><i class="fas fa-plus"></i>Create New Report</a>
        </div>
    @endcan
</div>

{{-- Counted over every accessible report, not just the page the grid shows. --}}
<div class="stat-grid">
    <div class="card stat-card">
        <div class="stat-label">Total Reports</div>
        <div class="stat-value">{{ $totalReports }}</div>
    </div>
    <div class="card stat-card">
        <div class="stat-label">Passed Students</div>
        <div class="stat-value">{{ $passedCount }}</div>
    </div>
    <div class="card stat-card">
        <div class="stat-label">Failed Students</div>
        <div class="stat-value">{{ $failedCount }}</div>
    </div>
    <div class="card stat-card">
        <div class="stat-label">Average GPA</div>
        <div class="stat-value">{{ number_format($averageGpa, 2) }}</div>
    </div>
</div>

<div class="panel">
    <div class="panel-body">
        <div id="reports-grid" class="cq-grid"></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(function () {
        new TableHelper({
            containerId: 'reports-grid',
            apiUrl: '{{ route('reports.list') }}',
            perPage: 10,
            pagination: true,
            enableCheckbox: false,
            autoInitDatePickers: false,
            emptyMessage: 'No reports found',
            search: { placeholder: 'Search student or academic year…' },
            enableSortColumns: ['academic_year', 'final_gpa', 'issue_date'],

            columns: [
                { name: 'S.no', isSerialNo: true, width: '60px', align: 'center' },
                { name: 'Student', field: 'student' },
                { name: 'Class', field: 'class' },
                { name: 'Academic Year', field: 'academic_year' },
                { name: 'Final GPA', field: 'final_gpa', align: 'right', render: (r) => `<strong>${th_escapeHtml(r.final_gpa)}</strong>` },
                {
                    name: 'Grade', field: 'final_grade',
                    render: (r) => r.final_grade
                        ? `<span class="badge bg-primary">${th_escapeHtml(r.final_grade)}</span>`
                        : '—'
                },
                {
                    name: 'Result', field: 'result_status',
                    render: (r) => r.result_status === 'FAILED'
                        ? '<span class="badge bg-danger">Failed</span>'
                        : '<span class="badge bg-success">Passed</span>'
                },
                { name: 'Issue Date', field: 'issue_date' },
                {
                    name: 'Action',
                    type: 'actions',
                    actions: [
                        { type: 'view', showLabel: false, title: 'View report', url: '/reports/{id}' },
                        @can('reports.update')
                        { type: 'edit', showLabel: false, title: 'Edit report', url: '/reports/{id}/edit' },
                        @endcan
                        @can('reports.pdf')
                        {
                            type: 'custom', showLabel: false, title: 'Download PDF',
                            icon: 'fas fa-file-pdf', class: 'btn btn-sm btn-secondary',
                            url: '/reports/{id}/pdf'
                        },
                        @endcan
                        @can('reports.delete')
                        {
                            type: 'delete', showLabel: false, title: 'Delete report',
                            onClick: (row) => window.tableDelete('/reports/' + row.id,
                                "Delete " + row.student + "'s report for " + row.academic_year + '?')
                        },
                        @endcan
                    ]
                }
            ],

            filters: {
                autoGenerateColumnFilters: false,
                columnFilters: [
                    { field: 'student', type: 'text', param: 'student', placeholder: 'Student' },
                    { field: 'academic_year', type: 'text', param: 'academic_year', placeholder: 'Year' },
                    { field: 'final_grade', type: 'text', param: 'final_grade', placeholder: 'Grade' },
                    {
                        field: 'result_status', type: 'select', param: 'result_status', allowBlank: true,
                        options: [{ value: 'PASSED', label: 'Passed' }, { value: 'FAILED', label: 'Failed' }]
                    }
                ]
            }
        });
    });
</script>
@endpush

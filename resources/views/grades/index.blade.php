@extends('layouts.app')

@section('title', 'Grade Scale - GPA Management System')

@section('content')
<div class="toolbar">
    <div>
        <h2 class="toolbar-title">Grading System</h2>
        <p class="toolbar-sub">Letter grades, their grade points and the marks range each covers.</p>
    </div>
    @can('grades.create')
        <div class="toolbar-actions">
            <a href="{{ route('grades.create') }}" class="btn-primary-flat"><i class="fas fa-plus"></i>Add Grade</a>
        </div>
    @endcan
</div>

<div class="panel">
    <div class="panel-body">
        <div id="grades-grid" class="cq-grid"></div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Grade Point Scale</h5>
            </div>
            <div class="card-body">
                <p>The grading system follows the standard Nepalese educational format:</p>
                <ul>
                    <li><strong>4.0 Scale:</strong> Grade points range from 0.0 to 4.0</li>
                    <li><strong>Letter Grades:</strong> A+ (highest) to NG (not graded)</li>
                    <li><strong>Percentage Conversion:</strong> Based on marks obtained</li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Division System</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td><strong>1st Division</strong></td>
                        <td>60% and above</td>
                        <td><span class="badge bg-success">Distinction</span></td>
                    </tr>
                    <tr>
                        <td><strong>2nd Division</strong></td>
                        <td>50% - 59%</td>
                        <td><span class="badge bg-info">Good</span></td>
                    </tr>
                    <tr>
                        <td><strong>3rd Division</strong></td>
                        <td>35% - 49%</td>
                        <td><span class="badge bg-warning">Pass</span></td>
                    </tr>
                    <tr>
                        <td><strong>Fail</strong></td>
                        <td>Below 35%</td>
                        <td><span class="badge bg-danger">Fail</span></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(function () {
        new TableHelper({
            containerId: 'grades-grid',
            apiUrl: '{{ route('grades.list') }}',
            perPage: 25,
            pagination: true,
            enableCheckbox: false,
            autoInitDatePickers: false,
            emptyMessage: 'No grades defined yet',
            search: { placeholder: 'Search grade or remarks…' },
            enableSortColumns: ['letter_grade', 'grade_point', 'marks'],

            columns: [
                { name: 'S.no', isSerialNo: true, width: '60px', align: 'center' },
                {
                    name: 'Letter Grade', field: 'letter_grade',
                    render: (r) => `<span class="badge bg-primary">${th_escapeHtml(r.letter_grade)}</span>`
                },
                { name: 'Grade Point', field: 'grade_point', align: 'right', render: (r) => `<strong>${th_escapeHtml(r.grade_point)}</strong>` },
                { name: 'Marks Range', field: 'marks' },
                { name: 'Remarks', field: 'remarks', render: (r) => th_escapeHtml(r.remarks || '\u2014') },
                {
                    name: 'Action',
                    type: 'actions',
                    actions: [
                        @can('grades.update')
                        { type: 'edit', showLabel: false, title: 'Edit', url: '/grades/{id}/edit' },
                        @endcan
                        @can('grades.delete')
                        {
                            type: 'delete', showLabel: false, title: 'Delete',
                            onClick: (row) => window.tableDelete('/grades/' + row.id, {
                                title: 'Delete grade?',
                                message: 'The ' + row.letter_grade + ' band will be removed from the grading scale.'
                            })
                        },
                        @endcan
                    ]
                }
            ],

            filters: {
                autoGenerateColumnFilters: false,
                columnFilters: [
                    { field: 'letter_grade', type: 'text', param: 'letter_grade', placeholder: 'Grade' },
                    { field: 'remarks', type: 'text', param: 'remarks', placeholder: 'Remarks' }
                ]
            }
        });
    });
</script>
@endpush

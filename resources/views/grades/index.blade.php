@extends('layouts.app')

@section('title', 'Grade Scale - GPA Management System')

@php
    $covered = empty($gaps) && empty($overlaps) && $bandCount > 0;
@endphp

@section('content')
<div class="toolbar">
    <div>
        <h2 class="toolbar-title">Grading System</h2>
        <p class="toolbar-sub">
            Every mark is turned into a percentage of its subject's full marks, then read off this scale.
        </p>
    </div>
    <div class="toolbar-actions">
        @can('grades.create')
            @if(!$covered)
                <form method="POST" action="{{ route('grades.load-standard') }}" class="d-inline"
                      data-confirm="{{ $bandCount
                          ? 'The '.$bandCount.' '.\Illuminate\Support\Str::plural('band', $bandCount)
                            .' you have now will be replaced by the eight NEB standard bands.'
                          : 'Eight bands will be added, from A+ down to NG.' }}"
                      data-confirm-title="Load the NEB standard scale?"
                      data-confirm-label="Load standard scale"
                      data-confirm-tone="primary">
                    @csrf
                    <button type="submit" class="btn-ghost"><i class="fas fa-wand-magic-sparkles"></i>Load NEB standard</button>
                </form>
            @endif
            <button type="button" class="btn-primary-flat" id="addGradeBtn">
                <i class="fas fa-plus"></i>Add Grade
            </button>
        @endcan
    </div>
</div>

{{-- A scale with holes or overlaps grades some marks wrongly, or not at all. --}}
@if($bandCount === 0)
    <div class="flash flash-error">
        <i class="fas fa-circle-exclamation"></i>
        No grading scale is set up, so every report card comes out ungraded.
        @can('grades.create')Load the NEB standard above, or add bands by hand.@endcan
    </div>
@else
    @if(!empty($gaps))
        <div class="flash flash-error">
            <i class="fas fa-circle-exclamation"></i>
            <span>
                No grade covers
                @foreach($gaps as $index => [$from, $to])
                    <strong>{{ $from }}&ndash;{{ $to }}%</strong>{{ $index < count($gaps) - 1 ? ', ' : '' }}
                @endforeach.
                A mark in that range cannot be graded.
            </span>
        </div>
    @endif

    @if(!empty($overlaps))
        <div class="flash flash-error">
            <i class="fas fa-circle-exclamation"></i>
            <span>
                Overlapping ranges:
                @foreach($overlaps as $index => [$a, $b])
                    <strong>{{ $a->letter_grade }}</strong> ({{ $a->marks_from }}&ndash;{{ $a->marks_to }}%)
                    and <strong>{{ $b->letter_grade }}</strong> ({{ $b->marks_from }}&ndash;{{ $b->marks_to }}%){{ $index < count($overlaps) - 1 ? '; ' : '' }}
                @endforeach.
                A mark in the overlap could be graded either way.
            </span>
        </div>
    @endif

    @if($covered)
        <p class="page-note">
            <i class="fas fa-circle-check"></i>
            {{ $bandCount }} bands covering 0&ndash;100%, with
            <strong>{{ $passMark !== null ? $passMark.'%' : 'no' }}</strong>
            {{ $passMark !== null ? 'as the pass mark.' : 'passing band - every mark would fail.' }}
        </p>
    @endif
@endif

<div class="panel">
    <div class="panel-body">
        <div id="grades-grid" class="cq-grid"></div>
    </div>
</div>

<div class="info-grid is-two">
    <section class="panel info-card">
        <div class="info-card-head">
            <span class="info-card-icon"><i class="fas fa-circle-info"></i></span>
            <h3 class="info-card-title">How a subject is graded</h3>
        </div>
        <div class="info-card-body">
            <ol class="rule-list">
                <li>Theory and practical marks are added together.</li>
                <li>The total is turned into a percentage of that subject's <strong>full marks</strong>.</li>
                <li>The percentage is matched against the band whose range contains it.</li>
                <li>
                    The subject is failed if that band is marked as a failure, or if the marks are below the
                    subject's own <strong>pass marks</strong>.
                </li>
                <li>Only the <strong>final terminal</strong> counts toward the GPA. Fail one subject and the whole report card fails.</li>
            </ol>
        </div>
    </section>

    <section class="panel info-card">
        <div class="info-card-head">
            <span class="info-card-icon"><i class="fas fa-award"></i></span>
            <h3 class="info-card-title">How the result is decided</h3>
        </div>
        <div class="info-card-body">
            <table class="record-table">
                <thead>
                    <tr>
                        <th>Final GPA</th>
                        <th>Result</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($resultBands as $band)
                        <tr>
                            <td class="cell-strong">{{ number_format($band['min'], 1) }} and above</td>
                            <td>{{ \Illuminate\Support\Str::headline(strtolower($band['status'])) }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td class="cell-strong">Any failed subject</td>
                        <td>Failed</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</div>

@canany(['grades.create', 'grades.update'])
    @include('grades._form_modal')
@endcanany
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
            search: { placeholder: 'Search grade or description…' },
            enableSortColumns: ['letter_grade', 'grade_point', 'marks'],

            columns: [
                { name: 'S.no', isSerialNo: true, width: '60px', align: 'center' },
                {
                    name: 'Grade', field: 'letter_grade',
                    render: (r) => `<span class="badge ${r.is_failing ? 'bg-danger' : 'bg-primary'}">`
                        + `${th_escapeHtml(r.letter_grade)}</span>`
                },
                { name: 'Grade Point', field: 'grade_point', align: 'right', render: (r) => `<strong>${th_escapeHtml(r.grade_point)}</strong>` },
                { name: 'Percentage', field: 'marks' },
                { name: 'Description', field: 'description', render: (r) => th_escapeHtml(r.description || '—') },
                {
                    name: 'Result', field: 'is_failing',
                    render: (r) => r.is_failing
                        ? '<span class="badge bg-danger">Fail</span>'
                        : '<span class="badge bg-success">Pass</span>'
                },
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
                        @can('grades.update')
                        // No url - the modal opens from the click and fills itself from this row.
                        { type: 'edit', showLabel: false, title: 'Edit' },
                        @endcan
                        @can('grades.delete')
                        {
                            type: 'delete', showLabel: false, title: 'Delete',
                            onClick: (row) => window.tableDelete('/grades/' + row.id, {
                                title: 'Delete grade?',
                                message: 'The ' + row.letter_grade + ' band (' + row.marks + ') will be removed from the scale.'
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
                    { field: 'description', type: 'text', param: 'description', placeholder: 'Description' },
                    {
                        field: 'is_failing', type: 'select', param: 'is_failing', allowBlank: true,
                        options: [{ value: '0', label: 'Pass' }, { value: '1', label: 'Fail' }]
                    }
                ]
            }
        });
    });
</script>
@endpush

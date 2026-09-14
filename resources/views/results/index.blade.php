@extends('layouts.app')

@section('title', 'Class Results - GPA Management System')
@section('page-title', 'Class Results')

@push('styles')
<link href="{{ asset('css/resultsheet.css') }}" rel="stylesheet">
@endpush

@section('content')
<form class="form-card is-full no-print" action="{{ route('results.index') }}" method="GET" data-validate>
    <div class="form-card-head is-iconed">
        <span class="card-icon"><i class="fas fa-table-list"></i></span>
        <div>
            <h2 class="form-card-title">Class Result Sheet</h2>
            <p class="form-card-sub">One exam, every student and subject in the class, on one page.</p>
        </div>
    </div>

    <div class="form-card-body">
        <div class="form-grid">
            @include('partials.class-picker')

            <div class="form-field is-third">
                <label class="form-label" for="exam_type">Exam <span class="req">*</span></label>
                <select id="exam_type" name="exam_type" required class="form-input" data-picker data-placeholder="Choose an exam">
                    <option value=""></option>
                    @foreach($exams as $key => $label)
                        <option value="{{ $key }}" {{ $filters['exam_type'] === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="form-card-foot">
        <button type="submit" class="btn-primary-flat"><i class="fas fa-table-list"></i>Show Result Sheet</button>
    </div>
</form>

@if($sheet)
    <div class="detail-head no-print">
        <div>
            <h2 class="detail-title">Class {{ $filters['class'] }} {{ $filters['section'] }} &nbsp;·&nbsp; {{ $sheet->examLabel }}</h2>
            <p class="detail-sub">
                {{ $school?->name }} &nbsp;·&nbsp; Academic year {{ $filters['academic_year'] }}
                &nbsp;·&nbsp; {{ $sheet->summary['students'] }} {{ Str::plural('student', $sheet->summary['students']) }}
            </p>
        </div>
        @unless($sheet->isEmpty())
            <div class="toolbar-actions">
                <button type="button" class="btn-ghost" onclick="window.print()">
                    <i class="fas fa-print"></i>Print
                </button>
                @can('reports.pdf')
                    {{-- The class's report cards, one page each, in roll order. --}}
                    <a href="{{ route('reports.class-pdf', Arr::except($filters, ['complete', 'exam_type'])) }}" class="btn-ghost"
                       title="Every student's mark sheet for {{ $filters['academic_year'] }} in one PDF">
                        <i class="fas fa-file-pdf"></i>All Mark Sheets
                    </a>
                @endcan
                @can('results.pdf')
                    <a href="{{ route('results.pdf', Arr::except($filters, 'complete')) }}" class="btn-primary-flat">
                        <i class="fas fa-file-arrow-down"></i>Download PDF
                    </a>
                @endcan
            </div>
        @endunless
    </div>

    @if($sheet->isEmpty())
        <div class="form-card is-full">
            <div class="form-card-body">
                <div class="empty-state">
                    <i class="fas fa-file-lines"></i>
                    <p>No report cards for class {{ $filters['class'] }} {{ $filters['section'] }} in {{ $filters['academic_year'] }} yet.
                        Marks entered on the <a href="{{ route('marks.index', Arr::except($filters, ['complete', 'exam_type'])) }}">ledger</a> create them.</p>
                </div>
            </div>
        </div>
    @else
        @include('results._sheet')
    @endif
@endif
@endsection

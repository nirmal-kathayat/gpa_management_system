@extends('layouts.app')

@section('title', 'Mark Sheet - ' . $report->student->name)
@section('page-title', 'Report Cards')

@push('styles')
<link href="{{ asset('css/marksheet.css') }}" rel="stylesheet">
@endpush

@section('content')
{{-- The page chrome sits directly in .page-body so it picks up the column gap. --}}
<nav class="crumbs no-print" aria-label="Breadcrumb">
    <a href="{{ route('reports.index') }}">Report Cards</a>
    <i class="fas fa-chevron-right" aria-hidden="true"></i>
    <span>{{ $report->student->name }}</span>
</nav>

<div class="detail-head no-print">
    <div>
        <h2 class="detail-title">Mark Sheet</h2>
        <p class="detail-sub">
            {{ $report->student->name }} &nbsp;·&nbsp;
            Class {{ $report->student->class }}{{ $report->student->section ? ' '.$report->student->section : '' }}
            &nbsp;·&nbsp; {{ $report->academic_year }}
        </p>
    </div>
    <div class="toolbar-actions">
        <a href="{{ route('reports.index') }}" class="btn-ghost">
            <i class="fas fa-arrow-left"></i>Back
        </a>
        <button type="button" class="btn-ghost" onclick="window.print()">
            <i class="fas fa-print"></i>Print
        </button>
        @can('reports.pdf')
            <a href="{{ route('reports.pdf', $report) }}" class="btn-primary-flat">
                <i class="fas fa-file-arrow-down"></i>Download PDF
            </a>
        @endcan
    </div>
</div>

@include('reports._sheet')
@endsection

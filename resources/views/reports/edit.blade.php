@extends('layouts.app')

@section('title', 'Edit Report - GPA Management System')
@section('page-title', 'Edit Report')

@section('content')
<form class="form-card is-roomy" action="{{ route('reports.update', $report) }}" method="POST">
    @csrf
    @method('PUT')
    @include('reports._form', [
        'title' => 'Edit ' . $report->student->name . "'s report",
        'subtitle' => 'Academic year ' . $report->academic_year . '. Changing a mark recalculates the GPA on save.',
        'submitLabel' => 'Update Report',
    ])
</form>
@endsection

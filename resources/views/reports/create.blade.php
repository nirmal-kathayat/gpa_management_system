@extends('layouts.app')

@section('title', 'Create Report - GPA Management System')
@section('page-title', 'Create Report')

@section('content')
<form class="form-card is-roomy" action="{{ route('reports.store') }}" method="POST">
    @csrf
    @include('reports._form', [
        'title' => 'Create Student Report',
        'subtitle' => 'Enter the marks for each subject, then the attendance and behavioural grades.',
        'submitLabel' => 'Create Report',
    ])
</form>
@endsection

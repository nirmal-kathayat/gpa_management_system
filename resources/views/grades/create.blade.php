@extends('layouts.app')

@section('title', 'Add Grade - GPA Management System')
@section('page-title', 'Add Grade')

@section('content')
<form class="form-card" action="{{ route('grades.store') }}" method="POST">
    @csrf
    @include('grades._form', [
        'title' => 'Add New Grade',
        'subtitle' => 'Define a letter grade and the marks range it covers.',
        'submitLabel' => 'Create Grade',
    ])
</form>
@endsection

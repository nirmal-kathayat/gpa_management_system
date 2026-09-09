@extends('layouts.app')

@section('title', 'Edit Grade - GPA Management System')
@section('page-title', 'Edit Grade')

@section('content')
<form class="form-card" action="{{ route('grades.update', $grade) }}" method="POST">
    @csrf
    @method('PUT')
    @include('grades._form', [
        'title' => 'Edit grade ' . $grade->letter_grade,
        'subtitle' => 'Update this band of the grading scale.',
        'submitLabel' => 'Update Grade',
    ])
</form>
@endsection

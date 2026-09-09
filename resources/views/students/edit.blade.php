@extends('layouts.app')

@section('title', 'Edit Student - GPA Management System')
@section('page-title', 'Edit Student')

@section('content')
<form class="form-card" action="{{ route('students.update', $student) }}" method="POST">
    @csrf
    @method('PUT')
    @include('students._form', [
        'title' => 'Edit ' . $student->name,
        'subtitle' => 'Update this student\'s details.',
        'submitLabel' => 'Update Student',
    ])
</form>
@endsection

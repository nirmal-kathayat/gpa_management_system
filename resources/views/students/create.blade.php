@extends('layouts.app')

@section('title', 'Add Student - GPA Management System')
@section('page-title', 'Add Student')

@section('content')
<form class="form-card is-roomy" enctype="multipart/form-data" action="{{ route('students.store') }}" method="POST">
    @csrf
    @include('students._form', [
        'title' => 'Add New Student',
        'subtitle' => 'Fill in the details below to enrol a new student.',
        'submitLabel' => 'Create Student',
    ])
</form>
@endsection

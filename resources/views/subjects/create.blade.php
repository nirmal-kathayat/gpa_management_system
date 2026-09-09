@extends('layouts.app')

@section('title', 'Add Subject - GPA Management System')
@section('page-title', 'Add Subject')

@section('content')
<form class="form-card" action="{{ route('subjects.store') }}" method="POST">
    @csrf
    @include('subjects._form', [
        'title' => 'Add New Subject',
        'subtitle' => 'Fill in the details below to create a new subject.',
        'submitLabel' => 'Create Subject',
    ])
</form>
@endsection

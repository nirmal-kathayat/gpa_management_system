@extends('layouts.app')

@section('title', 'Add School - GPA Management System')
@section('page-title', 'Add School')

@section('content')
<form class="form-card" action="{{ route('schools.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @include('schools._form', [
        'title' => 'Add New School',
        'subtitle' => 'Fill in the details below to create a new school.',
        'submitLabel' => 'Create School',
    ])
</form>
@endsection

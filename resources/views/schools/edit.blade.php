@extends('layouts.app')

@section('title', 'Edit School - GPA Management System')
@section('page-title', 'Edit School')

@section('content')
<form class="form-card" action="{{ route('schools.update', $school) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    @include('schools._form', [
        'title' => 'Edit ' . $school->name,
        'subtitle' => 'Update the details of this school.',
        'submitLabel' => 'Update School',
    ])
</form>
@endsection

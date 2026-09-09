@extends('layouts.app')

@section('title', 'Edit Subject - GPA Management System')
@section('page-title', 'Edit Subject')

@section('content')
<form class="form-card" action="{{ route('subjects.update', $subject) }}" method="POST">
    @csrf
    @method('PUT')
    @include('subjects._form', [
        'title' => 'Edit ' . $subject->name,
        'subtitle' => 'Update the details of this subject.',
        'submitLabel' => 'Update Subject',
    ])
</form>
@endsection

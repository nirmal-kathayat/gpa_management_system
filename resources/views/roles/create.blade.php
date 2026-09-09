@extends('layouts.app')

@section('title', 'New Role - GPA Management System')
@section('page-title', 'New Role')

@section('content')
<form class="form-card" method="POST" action="{{ route('roles.store') }}">
    @csrf
    @include('roles._form', [
        'title' => 'Create a Role',
        'subtitle' => 'Name the role, then tick what people holding it are allowed to do.',
        'submitLabel' => 'Create Role',
    ])
</form>
@endsection

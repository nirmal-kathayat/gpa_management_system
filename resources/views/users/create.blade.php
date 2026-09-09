@extends('layouts.app')

@section('title', 'New User - GPA Management System')
@section('page-title', 'New User')

@section('content')
<form class="form-card" method="POST" action="{{ route('users.store') }}">
    @csrf
    @include('users._form', [
        'title' => 'Add a User',
        'subtitle' => 'Create the account, then give it a role so it can reach the right screens.',
        'submitLabel' => 'Create User',
    ])
</form>
@endsection

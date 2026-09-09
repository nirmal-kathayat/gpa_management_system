@extends('layouts.app')

@section('title', 'Edit User - GPA Management System')
@section('page-title', 'Edit User')

@section('content')
<form class="form-card" method="POST" action="{{ route('users.update', $user) }}">
    @csrf
    @method('PUT')
    @include('users._form', [
        'title' => 'Edit ' . $user->name,
        'subtitle' => '@' . $user->username . ' · ' . $user->email,
        'submitLabel' => 'Save Changes',
    ])
</form>
@endsection

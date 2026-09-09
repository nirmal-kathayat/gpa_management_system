@extends('layouts.app')

@section('title', 'Edit User - GPA Management System')
@section('page-title', 'Edit User')

@section('content')
<div class="toolbar">
    <div>
        <h2 class="toolbar-title">{{ $user->name }}</h2>
        <p class="toolbar-sub">{{ '@' . $user->username }} &middot; {{ $user->email }}</p>
    </div>
    <div class="toolbar-actions">
        <a href="{{ route('users.show', $user) }}" class="btn-ghost"><i class="fas fa-eye"></i>View</a>
        <a href="{{ route('users.index') }}" class="btn-ghost"><i class="fas fa-arrow-left"></i>Back</a>
    </div>
</div>

<form method="POST" action="{{ route('users.update', $user) }}" class="d-flex flex-column gap-4">
    @csrf
    @method('PUT')
    @include('users._form', ['submitLabel' => 'Save changes'])
</form>
@endsection

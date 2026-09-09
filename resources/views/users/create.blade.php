@extends('layouts.app')

@section('title', 'New User - GPA Management System')
@section('page-title', 'New User')

@section('content')
<div class="toolbar">
    <div>
        <h2 class="toolbar-title">Add a user</h2>
        <p class="toolbar-sub">Create the account, then give it a role so it can reach the right screens.</p>
    </div>
    <div class="toolbar-actions">
        <a href="{{ route('users.index') }}" class="btn-ghost"><i class="fas fa-arrow-left"></i>Back</a>
    </div>
</div>

<form method="POST" action="{{ route('users.store') }}" class="d-flex flex-column gap-4">
    @csrf
    @include('users._form', ['submitLabel' => 'Create user'])
</form>
@endsection

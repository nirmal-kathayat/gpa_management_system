@extends('layouts.app')

@section('title', 'New Role - GPA Management System')
@section('page-title', 'New Role')

@section('content')
<div class="toolbar">
    <div>
        <h2 class="toolbar-title">Create a role</h2>
        <p class="toolbar-sub">Name the role, then tick what people holding it are allowed to do.</p>
    </div>
    <div class="toolbar-actions">
        <a href="{{ route('roles.index') }}" class="btn-ghost"><i class="fas fa-arrow-left"></i>Back</a>
    </div>
</div>

<form method="POST" action="{{ route('roles.store') }}" class="d-flex flex-column gap-4">
    @csrf
    @include('roles._form', ['submitLabel' => 'Create role'])
</form>
@endsection

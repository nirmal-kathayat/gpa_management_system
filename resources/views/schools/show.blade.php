@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4>{{ $school->name }}</h4>
                <div>
                    <a href="{{ route('schools.edit', $school) }}" class="btn btn-warning btn-sm">Edit</a>
                    <a href="{{ route('schools.index') }}" class="btn btn-secondary btn-sm">Back</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="30%">Name:</th>
                                <td>{{ $school->name }}</td>
                            </tr>
                            <tr>
                                <th>Address:</th>
                                <td>{{ $school->address }}</td>
                            </tr>
                            <tr>
                                <th>Phone:</th>
                                <td>{{ $school->phone ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td>{{ $school->email ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Total Students:</th>
                                <td><span class="badge bg-info">{{ $school->students->count() }}</span></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        @if($school->logo)
                            <img src="{{ Storage::url($school->logo) }}" alt="School Logo" class="img-fluid" style="max-height: 200px;">
                        @else
                            <div class="text-muted">No logo uploaded</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('students.create') }}?school_id={{ $school->id }}" class="btn btn-primary">
                        Add Student
                    </a>
                    <a href="{{ route('students.index') }}?school={{ $school->id }}" class="btn btn-info">
                        View All Students
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@if($school->students->count() > 0)
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5>Students ({{ $school->students->count() }})</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Class</th>
                                <th>Section</th>
                                <th>Roll No.</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($school->students->take(10) as $student)
                            <tr>
                                <td>{{ $student->name }}</td>
                                <td>{{ $student->class }}</td>
                                <td>{{ $student->section }}</td>
                                <td>{{ $student->roll_number }}</td>
                                <td>
                                    <a href="{{ route('students.show', $student) }}" class="btn btn-sm btn-info">View</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                @if($school->students->count() > 10)
                <div class="text-center">
                    <a href="{{ route('students.index') }}?school={{ $school->id }}" class="btn btn-outline-primary">
                        View All {{ $school->students->count() }} Students
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endif
@endsection

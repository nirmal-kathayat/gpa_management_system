@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Schools</h2>
    <a href="{{ route('schools.create') }}" class="btn btn-primary">Add School</a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Address</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Students</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($schools as $school)
                    <tr>
                        <td>{{ $school->id }}</td>
                        <td>{{ $school->name }}</td>
                        <td>{{ Str::limit($school->address, 30) }}</td>
                        <td>{{ $school->phone ?? 'N/A' }}</td>
                        <td>{{ $school->email ?? 'N/A' }}</td>
                        <td>
                            <span class="badge bg-info">{{ $school->students_count }}</span>
                        </td>
                        <td>
                            <a href="{{ route('schools.show', $school) }}" class="btn btn-sm btn-info">View</a>
                            <a href="{{ route('schools.edit', $school) }}" class="btn btn-sm btn-warning">Edit</a>
                            <form action="{{ route('schools.destroy', $school) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure? This will also delete all students in this school.')">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        {{ $schools->links() }}
    </div>
</div>
@endsection

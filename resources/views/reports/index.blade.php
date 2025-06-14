@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Student Reports</h2>
    <a href="{{ route('reports.create') }}" class="btn btn-primary">Create New Report</a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student Name</th>
                        <th>Class</th>
                        <th>Academic Year</th>
                        <th>Final GPA</th>
                        <th>Final Grade</th>
                        <th>Issue Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reports as $report)
                    <tr>
                        <td>{{ $report->id }}</td>
                        <td>{{ $report->student->name }}</td>
                        <td>{{ $report->student->class }} - {{ $report->student->section }}</td>
                        <td>{{ $report->academic_year }}</td>
                        <td>{{ $report->final_gpa }}</td>
                        <td>{{ $report->final_grade }}</td>
                        <td>{{ $report->issue_date->format('Y-m-d') }}</td>
                        <td>
                            <a href="{{ route('reports.show', $report) }}" class="btn btn-sm btn-info">View</a>
                            <a href="{{ route('reports.pdf', $report) }}" class="btn btn-sm btn-danger">PDF</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        {{ $reports->links() }}
    </div>
</div>
@endsection

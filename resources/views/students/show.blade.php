@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4>Student Details</h4>
                <div>
                    <a href="{{ route('students.edit', $student) }}" class="btn btn-warning btn-sm">Edit</a>
                    <a href="{{ route('students.index') }}" class="btn btn-secondary btn-sm">Back</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="40%">Name:</th>
                                <td>{{ $student->name }}</td>
                            </tr>
                            <tr>
                                <th>Class:</th>
                                <td>{{ $student->class }}</td>
                            </tr>
                            <tr>
                                <th>Section:</th>
                                <td>{{ $student->section }}</td>
                            </tr>
                            <tr>
                                <th>Roll Number:</th>
                                <td>{{ $student->roll_number }}</td>
                            </tr>
                            <tr>
                                <th>School:</th>
                                <td>{{ $student->school->name }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="40%">Date of Birth:</th>
                                <td>{{ $student->date_of_birth?->format('Y-m-d') ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Father's Name:</th>
                                <td>{{ $student->father_name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Mother's Name:</th>
                                <td>{{ $student->mother_name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Phone:</th>
                                <td>{{ $student->phone ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Address:</th>
                                <td>{{ $student->address ?? 'N/A' }}</td>
                            </tr>
                        </table>
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
                    <a href="{{ route('reports.create') }}?student_id={{ $student->id }}" class="btn btn-primary">
                        Create Report Card
                    </a>
                    <button class="btn btn-info" onclick="printStudentInfo()">
                        Print Student Info
                    </button>
                </div>
            </div>
        </div>
        
        @if($student->reports->count() > 0)
        <div class="card mt-3">
            <div class="card-header">
                <h5>Recent Reports</h5>
            </div>
            <div class="card-body">
                @foreach($student->reports->take(5) as $report)
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <strong>{{ $report->academic_year }}</strong><br>
                        <small>GPA: {{ $report->final_gpa }} ({{ $report->final_grade }})</small>
                    </div>
                    <div>
                        <a href="{{ route('reports.show', $report) }}" class="btn btn-sm btn-outline-primary">View</a>
                    </div>
                </div>
                @if(!$loop->last)<hr>@endif
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

<script>
function printStudentInfo() {
    window.print();
}
</script>
@endsection

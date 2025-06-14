@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Grading System</h2>
    <a href="{{ route('grades.create') }}" class="btn btn-primary">Add Grade</a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Letter Grade</th>
                        <th>Grade Point</th>
                        <th>Marks Range</th>
                        <th>Remarks</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($grades as $grade)
                    <tr>
                        <td>
                            <span class="badge bg-primary fs-6">{{ $grade->letter_grade }}</span>
                        </td>
                        <td><strong>{{ $grade->grade_point }}</strong></td>
                        <td>{{ $grade->marks_from }} - {{ $grade->marks_to }}</td>
                        <td>{{ $grade->remarks }}</td>
                        <td>
                            <a href="{{ route('grades.edit', $grade) }}" class="btn btn-sm btn-warning">Edit</a>
                            <form action="{{ route('grades.destroy', $grade) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Grade Point Scale</h5>
            </div>
            <div class="card-body">
                <p>The grading system follows the standard Nepalese educational format:</p>
                <ul>
                    <li><strong>4.0 Scale:</strong> Grade points range from 0.0 to 4.0</li>
                    <li><strong>Letter Grades:</strong> A+ (highest) to NG (not graded)</li>
                    <li><strong>Percentage Conversion:</strong> Based on marks obtained</li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Division System</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td><strong>1st Division</strong></td>
                        <td>60% and above</td>
                        <td><span class="badge bg-success">Distinction</span></td>
                    </tr>
                    <tr>
                        <td><strong>2nd Division</strong></td>
                        <td>50% - 59%</td>
                        <td><span class="badge bg-info">Good</span></td>
                    </tr>
                    <tr>
                        <td><strong>3rd Division</strong></td>
                        <td>35% - 49%</td>
                        <td><span class="badge bg-warning">Pass</span></td>
                    </tr>
                    <tr>
                        <td><strong>Fail</strong></td>
                        <td>Below 35%</td>
                        <td><span class="badge bg-danger">Fail</span></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

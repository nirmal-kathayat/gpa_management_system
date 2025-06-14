@extends('layouts.app')

@section('content')
<div class="jumbotron bg-primary text-white text-center py-5 mb-4 rounded">
    <h1 class="display-4">GPA Conversion System</h1>
    <p class="lead">Digital Report Card System for Nepalese Schools</p>
    <hr class="my-4">
    <p>Manage students, generate report cards, and convert to PDF format</p>
    <div class="mt-4">
        <a class="btn btn-light btn-lg me-3" href="{{ route('students.index') }}" role="button">Manage Students</a>
        <a class="btn btn-outline-light btn-lg" href="{{ route('reports.index') }}" role="button">View Reports</a>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <h5 class="card-title">Student Management</h5>
                <p class="card-text">Add, edit, and manage student information including personal details and academic records.</p>
                <a href="{{ route('students.index') }}" class="btn btn-primary">Manage Students</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <h5 class="card-title">Report Generation</h5>
                <p class="card-text">Create comprehensive report cards with GPA calculation and grade conversion.</p>
                <a href="{{ route('reports.create') }}" class="btn btn-success">Create Report</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <h5 class="card-title">PDF Export</h5>
                <p class="card-text">Generate printable PDF reports that match the traditional Nepalese report card format.</p>
                <a href="{{ route('reports.index') }}" class="btn btn-info">View Reports</a>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5>Features</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <ul class="list-unstyled">
                            <li>✓ Complete student information management</li>
                            <li>✓ Multiple examination types support</li>
                            <li>✓ Automatic GPA calculation</li>
                            <li>✓ Grade conversion system</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="list-unstyled">
                            <li>✓ Printable report cards</li>
                            <li>✓ PDF export functionality</li>
                            <li>✓ Traditional Nepalese format</li>
                            <li>✓ Responsive web design</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

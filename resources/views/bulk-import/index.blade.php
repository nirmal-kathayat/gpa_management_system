@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h4>Bulk Import</h4>
            </div>
            <div class="card-body">
                
                <!-- Student Import -->
                <div class="mb-4">
                    <h5>Import Students</h5>
                    <p class="text-muted">Upload a CSV file to import multiple students at once.</p>
                    
                    <form action="{{ route('bulk-import.students') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="csv_file" class="form-label">CSV File *</label>
                            <input type="file" class="form-control @error('csv_file') is-invalid @enderror" 
                                   id="csv_file" name="csv_file" accept=".csv" required>
                            @error('csv_file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Upload CSV file with student data. 
                                <a href="{{ route('bulk-import.template') }}" class="text-decoration-none">Download template</a>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Import Students</button>
                    </form>
                </div>

                <hr>

                <!-- Instructions -->
                <div class="mt-4">
                    <h5>Import Instructions</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Required Columns:</h6>
                            <ul>
                                <li><code>name</code> - Student full name</li>
                                <li><code>class</code> - Class name (e.g., SEVEN)</li>
                                <li><code>section</code> - Section (e.g., A, B)</li>
                                <li><code>roll_number</code> - Roll number</li>
                                <li><code>school_id</code> - School ID (numeric)</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Optional Columns:</h6>
                            <ul>
                                <li><code>father_name</code> - Father's name</li>
                                <li><code>mother_name</code> - Mother's name</li>
                                <li><code>address</code> - Student address</li>
                                <li><code>phone</code> - Contact number</li>
                                <li><code>date_of_birth</code> - Date (YYYY-MM-DD)</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-3">
                        <strong>Tips:</strong>
                        <ul class="mb-0">
                            <li>Download the template file to see the correct format</li>
                            <li>Make sure school_id exists in the schools table</li>
                            <li>Date format should be YYYY-MM-DD (e.g., 2010-01-15)</li>
                            <li>Remove any empty rows from your CSV file</li>
                        </ul>
                    </div>
                </div>

                <!-- Available Schools -->
                <div class="mt-4">
                    <h6>Available Schools:</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>School Name</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(\App\Models\School::all() as $school)
                                <tr>
                                    <td><code>{{ $school->id }}</code></td>
                                    <td>{{ $school->name }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

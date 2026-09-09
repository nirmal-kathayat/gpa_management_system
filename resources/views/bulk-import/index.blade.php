@extends('layouts.app')

@section('title', 'Bulk Import - GPA Management System')

@section('content')
<form class="form-card" action="{{ route('bulk-import.students') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="form-card-head">
        <h2 class="form-card-title">Import Students</h2>
        <p class="form-card-sub">Upload a CSV file to add many students at once.</p>
    </div>

    <div class="form-card-body">
        <div class="form-grid">
            <div class="form-field is-wide">
                <label class="form-label" for="csv_file">CSV File <span class="req">*</span></label>
                <input type="file" id="csv_file" name="csv_file" accept=".csv" required
                       class="form-input @error('csv_file') is-invalid @enderror">
                <p class="form-hint">
                    Not sure of the format?
                    <a href="{{ route('bulk-import.template') }}">Download the template</a>.
                </p>
                @error('csv_file')<p class="form-error">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    <div class="form-card-foot">
        <a href="{{ route('students.index') }}" class="btn-ghost">Cancel</a>
        <button type="submit" class="btn-primary-flat">Import Students</button>
    </div>
</form>

<div class="form-card">
    <div class="form-card-head">
        <h2 class="form-card-title">What the file needs</h2>
        <p class="form-card-sub">Column names must match exactly. Remove any empty rows before uploading.</p>
    </div>

    <div class="form-card-body">
        <div class="detail-grid">
            <div class="detail-item">
                <div class="detail-label">Required columns</div>
                <ul class="import-columns">
                    <li><code>name</code> Student full name</li>
                    <li><code>class</code> Class name, e.g. SEVEN</li>
                    <li><code>section</code> Section, e.g. A</li>
                    <li><code>roll_number</code> Roll number</li>
                    <li><code>school_id</code> School id from the table below</li>
                </ul>
            </div>
            <div class="detail-item">
                <div class="detail-label">Optional columns</div>
                <ul class="import-columns">
                    <li><code>father_name</code></li>
                    <li><code>mother_name</code></li>
                    <li><code>address</code></li>
                    <li><code>phone</code></li>
                    <li><code>date_of_birth</code> as YYYY-MM-DD</li>
                </ul>
            </div>
        </div>
    </div>

    <p class="form-card-section"><span>School ids</span></p>

    <div class="form-card-body">
        <div class="marks-table-wrap">
            <table class="marks-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>School Name</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(\App\Models\School::orderBy('name')->get() as $school)
                        <tr>
                            <td class="marks-subject"><code>{{ $school->id }}</code></td>
                            <td>{{ $school->name }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="marks-empty">
                                No schools yet. <a href="{{ route('schools.create') }}">Add one</a> first.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

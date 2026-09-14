@extends('layouts.app')

@section('title', 'Bulk Import - GPA Management System')
@section('page-title', 'Bulk Import')

@php $import = session('importResult'); @endphp

@section('content')
<form class="form-card" action="{{ route('bulk-import.students') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="form-card-head is-iconed">
        <span class="card-icon"><i class="fas fa-upload"></i></span>
        <div>
            <h2 class="form-card-title">Import Students</h2>
            <p class="form-card-sub">Upload a CSV file to add many students at once.</p>
        </div>
    </div>

    <div class="form-card-body">
        <div class="form-grid">
            <div class="form-field is-wide">
                <label class="form-label" for="csv_file">CSV File <span class="req">*</span></label>
                <input type="file" id="csv_file" name="csv_file" accept=".csv,text/csv" required
                       class="form-input @error('csv_file') is-invalid @enderror">
                <p class="form-hint">
                    Not sure of the format?
                    <a href="{{ route('bulk-import.template') }}">Download the template</a> — it comes
                    with one example row you can overwrite.
                </p>
                @error('csv_file')<p class="form-error">{{ $message }}</p>@enderror
            </div>
        </div>

        <p class="form-note">
            <i class="fas fa-circle-info" aria-hidden="true"></i>
            Rows are checked one at a time. A row with a problem is skipped and listed below;
            the rest still import.
        </p>
    </div>

    <div class="form-card-foot">
        <a href="{{ route('students.index') }}" class="btn-ghost">Cancel</a>
        <button type="submit" class="btn-primary-flat">
            <i class="fas fa-upload"></i>Import Students
        </button>
    </div>
</form>

@if($import)
    <div class="form-card">
        <div class="form-card-head is-iconed">
            <span class="card-icon"><i class="fas fa-clipboard-check"></i></span>
            <div>
                <h2 class="form-card-title">Last import</h2>
                <p class="form-card-sub">
                    {{ $import['read'] }} {{ Str::plural('row', $import['read']) }} read
                    &nbsp;·&nbsp; {{ $import['imported'] }} imported
                    &nbsp;·&nbsp; {{ count($import['skipped']) }} skipped
                </p>
            </div>
        </div>

        @if($import['skipped'])
            <div class="dash-table-wrap">
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th class="is-num">Line</th>
                            <th>Student</th>
                            <th>Why it was skipped</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($import['skipped'] as $row)
                            <tr>
                                <td class="is-num">{{ $row['line'] }}</td>
                                <td>{{ $row['name'] }}</td>
                                <td>{{ $row['reason'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="form-card-body">
                <p class="empty-note">Every row imported. Nothing was skipped.</p>
            </div>
        @endif

        @if($import['imported'] > 0)
            <div class="form-card-foot">
                <a href="{{ route('students.index') }}" class="btn-primary-flat">
                    <i class="fas fa-user-graduate"></i>View Students
                </a>
            </div>
        @endif
    </div>
@endif

<div class="form-card">
    <div class="form-card-head is-iconed">
        <span class="card-icon"><i class="fas fa-list-check"></i></span>
        <div>
            <h2 class="form-card-title">What the file needs</h2>
            <p class="form-card-sub">Column names must match exactly. Their order does not matter.</p>
        </div>
    </div>

    <div class="form-card-body">
        <div class="detail-grid">
            <div class="detail-item">
                <div class="detail-label">Required columns</div>
                <ul class="import-columns">
                    <li><code>name</code> Student full name</li>
                    <li><code>class</code> Class name, e.g. 10 — one the school runs (see <a href="{{ route('structure.index') }}">Years &amp; Classes</a>)</li>
                    <li><code>section</code> Section, e.g. A</li>
                    <li><code>roll_number</code> Roll number, a whole number</li>
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

        <p class="form-note">
            <i class="fas fa-circle-info" aria-hidden="true"></i>
            A roll number already used in that class and section at that school is skipped,
            so re-uploading the same file will not duplicate anyone.
        </p>
    </div>

    <p class="form-card-section"><span>School ids</span></p>

    <div class="form-card-body">
        <div class="dash-table-wrap">
            <table class="dash-table">
                <thead>
                    <tr>
                        <th class="is-num">ID</th>
                        <th>School Name</th>
                        <th>Address</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schools as $school)
                        <tr>
                            <td class="is-num">{{ $school->id }}</td>
                            <td>{{ $school->name }}</td>
                            <td>{{ $school->address ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="empty-note">
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

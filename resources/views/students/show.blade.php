@extends('layouts.app')

@section('title', $student->name . ' - GPA Management System')
@section('page-title', 'Student')

@php
    $na = fn ($value) => filled($value) ? $value : 'N/A';

    $initials = \Illuminate\Support\Str::of($student->name)->explode(' ')->take(2)
        ->map(fn ($word) => \Illuminate\Support\Str::substr($word, 0, 1))->implode('');

    $dob = $student->date_of_birth?->format('d M Y');
    $admitted = $student->date_of_admission?->format('d M Y');

    $heroLeft = [
        ['fa-user', 'Full Name', $student->name],
        ['fa-graduation-cap', 'Class', $student->class],
        ['fa-users', 'Section', $student->section],
        ['fa-hashtag', 'Roll Number', $student->roll_number],
        ['fa-calendar-days', 'Admission Date', $na($admitted)],
    ];

    $heroRight = [
        ['fa-cake-candles', 'Date of Birth', $na($dob)],
        ['fa-venus-mars', 'Gender', $na($student->gender)],
        ['fa-user', "Father's Name", $na($student->father_name)],
        ['fa-user', "Mother's Name", $na($student->mother_name)],
        ['fa-school', 'School', $student->school->name ?? 'N/A'],
    ];

    $tiles = [
        ['fa-book', 'is-blue', $student->class, 'Class'],
        ['fa-users', 'is-green', $student->section, 'Section'],
        ['fa-hashtag', 'is-purple', $student->roll_number, 'Roll Number'],
        ['fa-calendar-days', 'is-amber', $na($dob), 'Date of Birth'],
        ['fa-user', 'is-rose', $student->is_active ? 'Active' : 'Left', 'Status'],
    ];

    $reports = $student->reports->sortByDesc('academic_year');
@endphp

@section('content')
<nav class="crumbs no-print" aria-label="Breadcrumb">
    <a href="{{ route('students.index') }}">Students</a>
    <i class="fas fa-chevron-right" aria-hidden="true"></i>
    <span>{{ $student->name }}</span>
</nav>

<div class="detail-head">
    <div>
        <h2 class="detail-title">
            {{ $student->name }}
            <span class="pill {{ $student->is_active ? 'pill-positive' : 'pill-muted' }}">
                {{ $student->is_active ? 'Active' : 'Left' }}
            </span>
        </h2>
        <p class="detail-sub">View complete details of the student including personal information, academic records and more.</p>
    </div>
    <div class="toolbar-actions no-print">
        @can('students.update')
            <a class="btn-ghost" href="{{ route('students.edit', $student) }}">
                <i class="fas fa-pen"></i>Edit Student
            </a>
        @endcan
        <button type="button" class="btn-primary-flat" onclick="window.print()">
            <i class="fas fa-print"></i>Print Student Info
        </button>
    </div>
</div>

<div class="panel student-hero">
    <div class="student-identity">
        @if($student->photo)
            <img class="student-avatar is-photo" src="{{ asset($student->photo) }}" alt="">
        @else
            <span class="student-avatar">{{ $initials }}</span>
        @endif
        <h3 class="student-name">{{ $student->name }}</h3>
        <span class="pill pill-muted">Roll No. {{ $student->roll_number }}</span>
        <p class="student-motto">&ldquo;Stay curious, keep learning.&rdquo;</p>
    </div>

    <div class="student-facts">
        @foreach([$heroLeft, $heroRight] as $column)
            <dl class="fact-list">
                @foreach($column as [$icon, $label, $value])
                    <div class="fact-row">
                        <dt><i class="fas {{ $icon }}"></i>{{ $label }}</dt>
                        <dd>{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        @endforeach
    </div>

    <figure class="student-quote">
        <span class="student-quote-icon"><i class="fas fa-graduation-cap"></i></span>
        <blockquote>Education is the most powerful weapon which you can use to change the world.</blockquote>
        <figcaption>&mdash; Nelson Mandela</figcaption>
    </figure>
</div>

<div class="tile-row">
    @foreach($tiles as [$icon, $tone, $value, $label])
        <div class="panel mini-stat">
            <span class="mini-stat-icon {{ $tone }}"><i class="fas {{ $icon }}"></i></span>
            <div>
                <div class="mini-stat-value">{{ $value }}</div>
                <div class="mini-stat-label">{{ $label }}</div>
            </div>
        </div>
    @endforeach
</div>

<div class="info-grid">
    <section class="panel info-card">
        <div class="info-card-head">
            <span class="info-card-icon"><i class="fas fa-user"></i></span>
            <h3 class="info-card-title">Personal Information</h3>
        </div>
        <div class="info-card-body">
            <dl class="contact-list">
                @foreach([
                    ['Full Name', $student->name],
                    ['Symbol Number', $na($student->symbol_number)],
                    ['Date of Birth', $na($dob)],
                    ['Gender', $na($student->gender)],
                    ['Class', $student->class],
                    ['Section', $student->section],
                    ['Roll Number', $student->roll_number],
                    ['Admission Date', $na($admitted)],
                    ['School', $student->school->name ?? 'N/A'],
                ] as [$label, $value])
                    <div class="contact-row">
                        <dt>{{ $label }}</dt>
                        <dd>{{ $value }}</dd>
                    </div>
                @endforeach
                <div class="contact-row">
                    <dt>Status</dt>
                    <dd>
                        <span class="pill {{ $student->is_active ? 'pill-positive' : 'pill-muted' }}">
                            {{ $student->is_active ? 'Active' : 'Left' }}
                        </span>
                    </dd>
                </div>
            </dl>
        </div>
    </section>

    <div class="info-col">
        <section class="panel info-card">
            <div class="info-card-head">
                <span class="info-card-icon"><i class="fas fa-phone"></i></span>
                <h3 class="info-card-title">Contact &amp; Address</h3>
            </div>
            <div class="info-card-body">
                <dl class="contact-list">
                    @foreach([
                        ['fa-phone', 'Phone', $na($student->phone)],
                        ['fa-envelope', 'Email', $na($student->email)],
                        ['fa-location-dot', 'Address', $na($student->address)],
                    ] as [$icon, $label, $value])
                        <div class="contact-row">
                            <dt><i class="fas {{ $icon }}"></i>{{ $label }}</dt>
                            <dd>{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </section>

        <section class="panel info-card">
            <div class="info-card-head">
                <span class="info-card-icon"><i class="fas fa-users"></i></span>
                <h3 class="info-card-title">Parents / Guardian</h3>
            </div>
            <div class="info-card-body">
                <dl class="contact-list">
                    @foreach([
                        ['fa-user', "Father's Name", $na($student->father_name)],
                        ['fa-user', "Mother's Name", $na($student->mother_name)],
                        ['fa-users', 'Guardian Name', $na($student->guardian_name)],
                        ['fa-phone', 'Guardian Phone', $na($student->guardian_phone)],
                    ] as [$icon, $label, $value])
                        <div class="contact-row">
                            <dt><i class="fas {{ $icon }}"></i>{{ $label }}</dt>
                            <dd>{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </section>
    </div>

    <section class="panel info-card">
        <div class="info-card-head">
            <span class="info-card-icon"><i class="fas fa-chart-column"></i></span>
            <h3 class="info-card-title">Recent Academic Records</h3>
        </div>
        <div class="info-card-body">
            @if($reports->isEmpty())
                <div class="empty-state">
                    <i class="fas fa-file-lines"></i>
                    <p>No report cards yet.</p>
                </div>
            @else
                <table class="record-table">
                    <thead>
                        <tr>
                            <th>Year</th>
                            <th>Class</th>
                            <th>GPA</th>
                            <th>Grade</th>
                            <th class="cell-actions no-print">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reports->take(6) as $report)
                            <tr>
                                <td>{{ $report->academic_year }}</td>
                                <td>{{ trim($report->class.' '.$report->section) }}</td>
                                <td>{{ number_format((float) $report->final_gpa, 2) }}</td>
                                <td>{{ $report->final_grade ?: '—' }}</td>
                                <td class="cell-actions no-print">
                                    @can('reports.viewAny')
                                        <a class="btn-ghost btn-tiny" href="{{ route('reports.show', $report) }}">View</a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
        @can('reports.create')
            <div class="info-card-foot no-print">
                <a class="btn-ghost" href="{{ route('reports.create') }}?student_id={{ $student->id }}">
                    <i class="fas fa-plus"></i>Create Report Card
                </a>
            </div>
        @endcan
    </section>
</div>

<p class="page-note no-print">
    <i class="fas fa-circle-info"></i>
    Some information shows as N/A until it is filled in on the student's record.
</p>
@endsection

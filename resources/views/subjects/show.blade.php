@extends('layouts.app')

@section('title', $subject->name . ' - GPA Management System')
@section('page-title', 'Subject')

@php
    $full = (int) $subject->full_marks;
    $pass = (int) $subject->pass_marks;
    $threshold = $full > 0 ? round($pass / $full * 100) : 0;

    $facts = [
        ['fa-file-lines', 'Subject Name', $subject->name],
        ['fa-hashtag', 'Subject Code', $subject->code],
        ['fa-award', 'Full Marks', $full],
        ['fa-circle-check', 'Pass Marks', $pass],
        ['fa-list', 'Grade Threshold', $pass.'/'.$full.' ('.$threshold.'%)'],
        ['fa-calendar-days', 'Created', $subject->created_at?->format('d M Y')],
        ['fa-clock', 'Last Updated', $subject->updated_at?->format('d M Y')],
    ];

    $tiles = [
        ['fa-file-lines', 'is-purple', $full, 'Full Marks'],
        ['fa-circle-check', 'is-green', $pass, 'Pass Marks'],
        ['fa-percent', 'is-amber', $threshold.'%', 'Pass Percentage'],
        ['fa-users', 'is-blue', $totalStudents, 'Total Students'],
    ];

    // A ring drawn with stroke-dasharray; 2 * pi * 52 is the circumference.
    $circumference = 2 * M_PI * 52;
@endphp

@section('content')
<nav class="crumbs no-print" aria-label="Breadcrumb">
    <a href="{{ route('subjects.index') }}">Subjects</a>
    <i class="fas fa-chevron-right" aria-hidden="true"></i>
    <span>{{ $subject->name }}</span>
</nav>

<div class="detail-head">
    <div>
        <h2 class="detail-title">
            {{ $subject->name }}
            <span class="pill {{ $subject->is_active ? 'pill-positive' : 'pill-muted' }}">
                {{ $subject->is_active ? 'Active' : 'Inactive' }}
            </span>
        </h2>
        <p class="detail-sub">View complete details and statistics of this subject.</p>
    </div>
    @can('subjects.update')
        <div class="toolbar-actions no-print">
            <a class="btn-ghost" href="{{ route('subjects.edit', $subject) }}">
                <i class="fas fa-pen"></i>Edit Subject
            </a>
        </div>
    @endcan
</div>

<div class="panel subject-hero">
    <div class="student-identity">
        <span class="student-avatar"><i class="fas fa-book"></i></span>
        <h3 class="student-name">{{ $subject->name }}</h3>
        <span class="pill pill-muted">{{ $subject->code }}</span>
        <p class="student-motto">&ldquo;Every subject is a step toward a brighter future.&rdquo;</p>
    </div>

    <dl class="fact-list subject-facts">
        @foreach($facts as [$icon, $label, $value])
            <div class="fact-row">
                <dt><i class="fas {{ $icon }}"></i>{{ $label }}</dt>
                <dd>{{ $value }}</dd>
            </div>
        @endforeach
        <div class="fact-row">
            <dt><i class="fas fa-circle-check"></i>Status</dt>
            <dd>
                <span class="pill {{ $subject->is_active ? 'pill-positive' : 'pill-muted' }}">
                    {{ $subject->is_active ? 'Active' : 'Inactive' }}
                </span>
            </dd>
        </div>
    </dl>

    <section class="subject-stats">
        <div class="info-card-head">
            <span class="info-card-icon"><i class="fas fa-chart-column"></i></span>
            <h3 class="info-card-title">Subject Statistics</h3>
        </div>

        <div class="subject-stats-body">
            <div class="donut">
                <svg viewBox="0 0 120 120" role="img" aria-label="Pass threshold {{ $threshold }} percent">
                    <circle class="donut-track" cx="60" cy="60" r="52"></circle>
                    <circle class="donut-value" cx="60" cy="60" r="52"
                            stroke-dasharray="{{ round($circumference * $threshold / 100, 2) }} {{ round($circumference, 2) }}"></circle>
                </svg>
                <span class="donut-figure">{{ $threshold }}%</span>
                <span class="donut-caption">Pass Percentage</span>
            </div>

            <dl class="stat-list">
                @foreach([
                    ['fa-users', 'is-blue', 'Total Students', $totalStudents],
                    ['fa-circle-check', 'is-green', 'Passed Students', $passedStudents],
                    ['fa-circle-xmark', 'is-rose', 'Failed Students', $failedStudents],
                    ['fa-chart-column', 'is-amber', 'Average Score', $averageScore ? number_format($averageScore, 1) : '—'],
                ] as [$icon, $tone, $label, $value])
                    <div class="stat-list-row">
                        <dt><span class="stat-list-icon {{ $tone }}"><i class="fas {{ $icon }}"></i></span>{{ $label }}</dt>
                        <dd>{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>
</div>

<div class="tile-row is-four">
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
            <span class="info-card-icon"><i class="fas fa-circle-info"></i></span>
            <h3 class="info-card-title">Subject Description</h3>
        </div>
        <div class="info-card-body">
            @if($subject->description)
                <p class="info-text">{{ $subject->description }}</p>
            @else
                <p class="info-text is-muted">No description yet. Add one by editing the subject.</p>
            @endif
        </div>
    </section>

    <section class="panel info-card">
        <div class="info-card-head">
            <span class="info-card-icon"><i class="fas fa-chart-column"></i></span>
            <h3 class="info-card-title">Grading Information</h3>
        </div>
        <div class="info-card-body">
            @if($grades->isEmpty())
                <div class="empty-state">
                    <i class="fas fa-award"></i>
                    <p>
                        No grading scale defined yet.
                        @can('grades.create')
                            <a href="{{ route('grades.create') }}">Add one</a>.
                        @endcan
                    </p>
                </div>
            @else
                <table class="record-table">
                    <thead>
                        <tr>
                            <th>Grade</th>
                            <th>Marks Range</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($grades as $grade)
                            <tr>
                                <td class="cell-strong">{{ $grade->letter_grade }}</td>
                                <td>{{ $grade->marks_from }} &ndash; {{ $grade->marks_to }}</td>
                                <td>{{ $grade->description ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </section>

    <section class="panel info-card">
        <div class="info-card-head">
            <span class="info-card-icon"><i class="fas fa-clock"></i></span>
            <h3 class="info-card-title">Recent Activity</h3>
        </div>
        <div class="info-card-body">
            <ol class="timeline">
                @if($subject->updated_at && $subject->created_at && $subject->updated_at->gt($subject->created_at))
                    <li class="timeline-row">
                        <span class="timeline-dot is-blue"></span>
                        <div>
                            <div class="timeline-title">Details updated</div>
                            <div class="timeline-meta">{{ $subject->updated_at->format('d M Y, g:i a') }}</div>
                        </div>
                    </li>
                @endif
                <li class="timeline-row">
                    <span class="timeline-dot is-green"></span>
                    <div>
                        <div class="timeline-title">Subject created</div>
                        <div class="timeline-meta">{{ $subject->created_at?->format('d M Y, g:i a') ?: '—' }}</div>
                    </div>
                </li>
            </ol>
        </div>
    </section>
</div>

<p class="page-note no-print">
    <i class="fas fa-circle-info"></i>
    @if($subject->is_active)
        This subject is active and can be added to new report cards.
    @else
        This subject is inactive. It stays on existing report cards but cannot be added to new ones.
    @endif
</p>
@endsection

@extends('layouts.app')

@section('title', 'Dashboard - GPA Management System')
@section('page-title', 'Dashboard')

@php
    // Quick actions are the four things this user is actually allowed to start.
    // Schools and subjects are short forms, so they open their modal on this
    // page rather than sending the user off to the listing to fill it in.
    $quickActions = array_values(array_filter([
        auth()->user()->can('students.create')
            ? ['fa-user-graduate', 'is-green', 'Add Student', route('students.create'), null] : null,
        auth()->user()->can('schools.create')
            ? ['fa-school', 'is-blue', 'Add School', null, 'school'] : null,
        auth()->user()->can('subjects.create')
            ? ['fa-book', 'is-purple', 'Add Subject', null, 'subject'] : null,
        auth()->user()->can('reports.create')
            ? ['fa-file-lines', 'is-rose', 'Generate Report', route('reports.create'), null] : null,
    ]));

    $trendTotal = collect($trend)->sum('students');
@endphp

@section('content')
<div class="welcome-row">
    <div>
        <h1 class="welcome-title">Welcome back, {{ Str::before(auth()->user()->name, ' ') }}!</h1>
        <p class="welcome-sub">Here's what's happening in your schools today.</p>
    </div>

    <div class="welcome-actions">
        @if($years->isNotEmpty())
            {{-- Picking a year re-reads the page; it scopes the GPA panels. --}}
            <form method="GET" action="{{ route('dashboard') }}" class="year-picker">
                <i class="fas fa-calendar-days" aria-hidden="true"></i>
                <div>
                    <label class="year-picker-label" for="year">Academic Year</label>
                    <select name="year" id="year" onchange="this.form.submit()">
                        @foreach($years as $option)
                            <option value="{{ $option }}" @selected($option == $academicYear)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        @endif

        @can('schools.create')
            <button type="button" class="btn-primary-flat" data-add-school>
                <i class="fas fa-plus"></i>Add School
            </button>
        @elsecan('reports.create')
            <a class="btn-primary-flat" href="{{ route('reports.create') }}">
                <i class="fas fa-plus"></i>New Report Card
            </a>
        @endcan
    </div>
</div>

<div class="stat-grid">
    @foreach($stats as $stat)
        <div class="card stat-card">
            <span class="stat-icon {{ $stat['tone'] }}"><i class="fas {{ $stat['icon'] }}"></i></span>
            <div class="stat-main">
                <div class="stat-label">{{ $stat['label'] }}</div>
                <div class="stat-value">{{ $stat['value'] }}</div>
                <div class="stat-delta delta-{{ $stat['delta']['tone'] }}">
                    @if($stat['delta']['tone'] === 'positive')
                        <i class="fas fa-arrow-up" aria-hidden="true"></i>
                    @elseif($stat['delta']['tone'] === 'danger')
                        <i class="fas fa-arrow-down" aria-hidden="true"></i>
                    @endif
                    {{ $stat['delta']['text'] }}
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="dash-row is-three">
    <section class="panel">
        <div class="panel-head">
            <div class="panel-title"><i class="fas fa-chart-column"></i>Student Trend</div>
            <div class="legend">
                <span class="legend-item"><span class="legend-dot is-students"></span>Students</span>
                <span class="legend-item"><span class="legend-dot is-schools"></span>Schools</span>
            </div>
        </div>

        <div class="panel-body">
            @if($trendTotal === 0)
                <p class="empty-note">No report cards yet, so there is nothing to chart.</p>
            @else
                <div class="trend">
                    @foreach($trend as $point)
                        <div class="trend-group">
                            <div class="trend-cols">
                                <div class="trend-col is-students" style="height: {{ max($point['studentsPct'], 2) }}%">
                                    <span class="trend-figure">{{ $point['students'] }}</span>
                                </div>
                                <div class="trend-col is-schools" style="height: {{ max($point['schoolsPct'], 2) }}%">
                                    <span class="trend-figure">{{ $point['schools'] }}</span>
                                </div>
                            </div>
                            <div class="trend-year">{{ $point['year'] }}</div>
                        </div>
                    @endforeach
                </div>
                <p class="panel-note">Students graded in each academic year, and the schools they came from.</p>
            @endif
        </div>
    </section>

    <section class="panel">
        <div class="panel-head">
            <div class="panel-title"><i class="fas fa-chart-pie"></i>GPA Distribution</div>
            @if($academicYear)<span class="panel-tag">{{ $academicYear }}</span>@endif
        </div>

        <div class="panel-body is-centred">
            @if($gpaTotal === 0)
                <p class="empty-note">No report cards for this year yet.</p>
            @else
                <div class="gpa-split">
                    @php
                        $circumference = 2 * M_PI * 54;
                        $offset = 0;
                    @endphp
                    <div class="gpa-donut">
                        <svg viewBox="0 0 128 128" aria-hidden="true">
                            <circle class="gpa-donut-track" cx="64" cy="64" r="54"></circle>
                            @foreach($gpaBands as $index => $band)
                                @php $length = $circumference * $band['count'] / $gpaTotal; @endphp
                                <circle class="gpa-donut-seg band-{{ $index + 1 }}" cx="64" cy="64" r="54"
                                        stroke-dasharray="{{ $length }} {{ $circumference - $length }}"
                                        stroke-dashoffset="{{ -$offset }}"></circle>
                                @php $offset += $length; @endphp
                            @endforeach
                        </svg>
                        <div class="gpa-donut-centre">
                            <strong>{{ number_format($gpaTotal) }}</strong>
                            <span>Students</span>
                        </div>
                    </div>

                    <ul class="gpa-legend">
                        @foreach($gpaBands as $index => $band)
                            <li>
                                <span class="legend-dot band-{{ $index + 1 }}"></span>
                                <span class="gpa-legend-label">{{ $band['label'] }}</span>
                                <span class="gpa-legend-pct">{{ $band['pct'] }}%</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </section>

    <section class="panel">
        <div class="panel-head">
            <div class="panel-title"><i class="fas fa-bolt"></i>Quick Actions</div>
        </div>

        <div class="panel-body">
            @if($quickActions)
                <div class="quick-grid">
                    @foreach($quickActions as [$icon, $tone, $label, $url, $modal])
                        @if($url)
                            <a class="quick-tile {{ $tone }}" href="{{ $url }}">
                                <i class="fas {{ $icon }}" aria-hidden="true"></i>
                                <span>{{ $label }}</span>
                            </a>
                        @else
                            <button type="button" class="quick-tile {{ $tone }}" data-add-{{ $modal }}>
                                <i class="fas {{ $icon }}" aria-hidden="true"></i>
                                <span>{{ $label }}</span>
                            </button>
                        @endif
                    @endforeach
                </div>
            @else
                <p class="empty-note">You do not have permission to add anything yet.</p>
            @endif
        </div>
    </section>
</div>

<div class="dash-row is-two">
    <section class="panel">
        <div class="panel-head">
            <div class="panel-title"><i class="fas fa-school"></i>{{ $isAdmin ? 'Recently Added Schools' : 'My School' }}</div>
            @can('schools.viewAny')
                <a href="{{ route('schools.index') }}">View all</a>
            @endcan
        </div>

        <div class="dash-table-wrap">
            <table class="dash-table">
                <thead>
                    <tr>
                        <th class="is-num">#</th>
                        <th>School Name</th>
                        <th>Address</th>
                        <th>Students</th>
                        <th>Avg GPA</th>
                        <th>Status</th>
                        <th>Added On</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schoolRows as $index => $school)
                        <tr>
                            <td class="is-num">{{ $index + 1 }}</td>
                            <td>
                                @can('schools.viewAny')
                                    <a href="{{ route('schools.show', $school['id']) }}">{{ $school['name'] }}</a>
                                @else
                                    <span class="cell-strong">{{ $school['name'] }}</span>
                                @endcan
                            </td>
                            <td>{{ $school['address'] }}</td>
                            <td>{{ $school['students'] }}</td>
                            <td>{{ $school['gpa'] }}</td>
                            <td><span class="pill pill-{{ $school['statusTone'] }}">{{ $school['status'] }}</span></td>
                            <td>{{ $school['added'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-note">No schools yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel">
        <div class="panel-head">
            <div class="panel-title"><i class="fas fa-user-graduate"></i>Recent Students</div>
            @can('students.viewAny')
                <a href="{{ route('students.index') }}">View all</a>
            @endcan
        </div>

        <div class="dash-table-wrap">
            <table class="dash-table">
                <thead>
                    <tr>
                        <th class="is-num">#</th>
                        <th>Name</th>
                        <th>School</th>
                        <th>Class</th>
                        <th>Added On</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($studentRows as $index => $student)
                        <tr>
                            <td class="is-num">{{ $index + 1 }}</td>
                            <td>
                                @can('students.viewAny')
                                    <a href="{{ route('students.show', $student['id']) }}">{{ $student['name'] }}</a>
                                @else
                                    <span class="cell-strong">{{ $student['name'] }}</span>
                                @endcan
                            </td>
                            <td>{{ $student['school'] }}</td>
                            <td>{{ $student['class'] }}</td>
                            <td>{{ $student['added'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-note">No students yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

<section class="panel">
    <div class="panel-head">
        <div class="panel-title"><i class="fas fa-clock-rotate-left"></i>Recent Activity</div>
    </div>

    <div class="panel-body">
        <div class="feed">
            @forelse($activities as $item)
                <div class="feed-row">
                    <span class="feed-icon {{ $item['tone'] }}"><i class="fas {{ $item['icon'] }}"></i></span>
                    <div class="feed-main">
                        <span class="feed-title">{{ $item['title'] }}</span>
                        <span class="feed-text">{{ $item['text'] }}</span>
                    </div>
                    <span class="feed-time">{{ $item['ago'] }}</span>
                </div>
            @empty
                <p class="empty-note">Nothing has happened yet.</p>
            @endforelse
        </div>
    </div>
</section>

{{-- Both short forms live on this page so a quick action never has to leave it. --}}
@can('schools.create')
    @include('schools._form_modal')
@endcan

@can('subjects.create')
    @include('subjects._form_modal')
@endcan
@endsection

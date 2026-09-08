@extends('layouts.app')

@section('title', 'Dashboard - GPA Management System')
@section('page-title', 'Dashboard')

@section('content')
    <div class="welcome-row">
        <div>
            <div class="welcome-title">Welcome back, {{ Str::before(auth()->user()->name, ' ') }}</div>
            <div class="welcome-sub">
                {{ now()->format('l, F j, Y') }}@if($academicYear) &middot; Academic Year {{ $academicYear }}@endif
            </div>
        </div>
        @if($isAdmin)
            <a class="btn-primary-flat" href="{{ route('schools.create') }}">
                <i class="fas fa-plus"></i>Add School
            </a>
        @else
            <a class="btn-primary-flat" href="{{ route('reports.create') }}">
                <i class="fas fa-plus"></i>New Report Card
            </a>
        @endif
    </div>

    <div class="stat-grid">
        @foreach($stats as $stat)
            <div class="card stat-card">
                <div class="stat-label">{{ $stat['label'] }}</div>
                <div class="stat-value">{{ $stat['value'] }}</div>
                <div class="stat-delta delta-{{ $stat['delta']['tone'] }}">{{ $stat['delta']['text'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="dash-grid">
        <section class="panel">
            <div class="panel-head">
                <div class="panel-title">{{ $isAdmin ? 'Recently Added Schools' : 'My School' }}</div>
                @if($isAdmin)
                    <a href="{{ route('schools.index') }}">View all</a>
                @endif
            </div>

            <div class="grid-table-head">
                <div>SCHOOL NAME</div>
                <div>ADDRESS</div>
                <div>STUDENTS</div>
                <div>AVG GPA</div>
                <div>STATUS</div>
            </div>

            @forelse($schoolRows as $school)
                <div class="grid-table-row">
                    <div class="cell-strong">{{ $school['name'] }}</div>
                    <div>{{ $school['address'] }}</div>
                    <div>{{ $school['students'] }}</div>
                    <div>{{ $school['gpa'] }}</div>
                    <div>
                        <span class="pill pill-{{ $school['statusTone'] }}">{{ $school['status'] }}</span>
                    </div>
                </div>
            @empty
                <div class="panel-body empty-note">No schools yet.</div>
            @endforelse
        </section>

        <div class="dash-side">
            <section class="panel">
                <div class="panel-body">
                    <div class="panel-title mb-3">GPA Distribution</div>
                    @if($gpaBands[0]['count'] === 0 && collect($gpaBands)->sum('count') === 0)
                        <div class="empty-note">No report cards yet.</div>
                    @else
                    <div class="bar-list">
                        @foreach($gpaBands as $band)
                            <div>
                                <div class="bar-head">
                                    <span>{{ $band['label'] }}</span>
                                    <strong>{{ $band['pct'] }}%</strong>
                                </div>
                                <div class="bar-track">
                                    <div class="bar-fill" style="width: {{ $band['pct'] }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </section>

            <section class="panel">
                <div class="panel-body">
                    <div class="panel-title mb-3">Needs Attention</div>
                    <div class="attention-list">
                        @forelse($attention as $item)
                            <div class="attention-row">
                                <div>
                                    <div class="attention-title">{{ $item['title'] }}</div>
                                    <div class="attention-sub">{{ $item['subtitle'] }}</div>
                                </div>
                                <span class="pill pill-warning">{{ $item['tag'] }}</span>
                            </div>
                        @empty
                            <div class="empty-note">Nothing needs attention.</div>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection

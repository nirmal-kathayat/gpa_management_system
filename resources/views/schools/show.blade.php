@extends('layouts.app')

@section('title', $school->name . ' - GPA Management System')
@section('page-title', 'School')

@php
    // Only the facts that have been filled in are shown, so the header does not
    // fill up with dashes on a school that was added with just a name.
    $meta = array_filter([
        ['icon' => 'fa-graduation-cap', 'label' => 'School Code', 'value' => $school->code],
        ['icon' => 'fa-building-columns', 'label' => 'Established', 'value' => $school->established],
        ['icon' => 'fa-globe', 'label' => 'School Type', 'value' => $school->type],
        ['icon' => 'fa-phone', 'label' => 'Phone', 'value' => $school->phone],
        ['icon' => 'fa-location-dot', 'label' => 'Address', 'value' => $school->address],
        ['icon' => 'fa-users', 'label' => 'Total Students', 'value' => $studentCount],
        ['icon' => 'fa-envelope', 'label' => 'Email', 'value' => $school->email],
    ], fn ($item) => $item['value'] !== null && $item['value'] !== '');
@endphp

@section('content')
<nav class="crumbs" aria-label="Breadcrumb">
    <a href="{{ route('schools.index') }}">Schools</a>
    <i class="fas fa-chevron-right" aria-hidden="true"></i>
    <span>{{ $school->name }}</span>
</nav>

<div class="detail-head">
    <div>
        <h2 class="detail-title">
            {{ $school->name }}
            <span class="pill pill-positive">Active</span>
        </h2>
        <p class="detail-sub">View complete details of the school including information, statistics and students.</p>
    </div>
    @can('students.create')
        <div class="toolbar-actions">
            <a class="btn-primary-flat" href="{{ route('students.create') }}?school_id={{ $school->id }}">
                <i class="fas fa-user-graduate"></i>Add Student
            </a>
        </div>
    @endcan
</div>

<div class="panel school-hero">
    @if($school->logo)
        <img class="school-hero-mark" src="{{ asset($school->logo) }}" alt="">
    @endif

    <div class="school-hero-main">
        <h3 class="school-hero-name">{{ $school->name }}</h3>
        @if($school->tagline)
            <p class="school-hero-tagline">{{ $school->tagline }}</p>
        @endif

        <div class="meta-grid">
            @foreach($meta as $item)
                <div class="meta-item">
                    <span class="meta-icon"><i class="fas {{ $item['icon'] }}"></i></span>
                    <div>
                        <div class="meta-label">{{ $item['label'] }}</div>
                        <div class="meta-value">{{ $item['value'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @if($school->logo)
        <img class="school-hero-crest" src="{{ asset($school->logo) }}" alt="{{ $school->name }} crest">
    @endif
</div>

<div class="info-grid">
    <div class="info-col">
        <section class="panel info-card">
            <div class="info-card-head">
                <span class="info-card-icon"><i class="fas fa-circle-info"></i></span>
                <h3 class="info-card-title">About School</h3>
            </div>
            <div class="info-card-body">
                @if($school->about)
                    <p class="info-text">{{ $school->about }}</p>
                @else
                    <p class="info-text is-muted">No description yet. Add one by editing the school.</p>
                @endif
            </div>
        </section>

        <div class="mini-stats">
            <div class="panel mini-stat">
                <span class="mini-stat-icon"><i class="fas fa-users"></i></span>
                <div>
                    <div class="mini-stat-value">{{ $studentCount }}</div>
                    <div class="mini-stat-label">Total Students</div>
                </div>
            </div>
            <div class="panel mini-stat">
                <span class="mini-stat-icon is-positive"><i class="fas fa-graduation-cap"></i></span>
                <div>
                    <div class="mini-stat-value">{{ $classCount }}</div>
                    <div class="mini-stat-label">{{ \Illuminate\Support\Str::plural('Class', $classCount) }}</div>
                </div>
            </div>
            <div class="panel mini-stat">
                <span class="mini-stat-icon is-soft"><i class="fas fa-layer-group"></i></span>
                <div>
                    <div class="mini-stat-value">{{ $sectionCount }}</div>
                    <div class="mini-stat-label">{{ \Illuminate\Support\Str::plural('Section', $sectionCount) }}</div>
                </div>
            </div>
        </div>
    </div>

    <section class="panel info-card">
        <div class="info-card-head">
            <span class="info-card-icon"><i class="fas fa-location-dot"></i></span>
            <h3 class="info-card-title">Location</h3>
        </div>
        <div class="info-card-body">
            <div class="location-box">
                <span class="location-pin"><i class="fas fa-location-dot"></i></span>
                <div class="location-name">{{ $school->address }}</div>
            </div>
        </div>
    </section>

    <section class="panel info-card">
        <div class="info-card-head">
            <span class="info-card-icon"><i class="fas fa-phone"></i></span>
            <h3 class="info-card-title">Contact Information</h3>
        </div>
        <div class="info-card-body">
            <dl class="contact-list">
                @foreach([
                    ['fa-phone', 'Phone', $school->phone],
                    ['fa-envelope', 'Email', $school->email],
                    ['fa-location-dot', 'Address', $school->address],
                    ['fa-globe', 'School Type', $school->type],
                    ['fa-calendar-days', 'Established', $school->established],
                ] as [$icon, $label, $value])
                    <div class="contact-row">
                        <dt><i class="fas {{ $icon }}"></i>{{ $label }}</dt>
                        <dd>{{ $value ?: '—' }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>
</div>

@can('students.viewAny')
    <section class="panel">
        <div class="panel-head">
            <span class="panel-title">
                <i class="fas fa-users me-2"></i>Students ({{ $studentCount }})
            </span>
            <a href="{{ route('students.index') }}">View in Students</a>
        </div>
        <div class="panel-body">
            <div id="school-students-grid" class="cq-grid"></div>
        </div>
    </section>
@endcan
@endsection

@push('scripts')
<script>
    $(function () {
        new TableHelper({
            containerId: 'school-students-grid',
            // Scoped server-side, so a search inside the grid stays in this school.
            apiUrl: '{{ route('students.list') }}?school_id={{ $school->id }}',
            perPage: 10,
            pagination: true,
            enableCheckbox: false,
            autoInitDatePickers: false,
            emptyMessage: 'No students in this school yet',
            search: { placeholder: 'Search students…' },
            enableSortColumns: ['name', 'class', 'roll_number'],

            columns: [
                { name: 'S.no', isSerialNo: true, width: '60px', align: 'center' },
                { name: 'Name', field: 'name' },
                { name: 'Class', field: 'class' },
                { name: 'Section', field: 'section' },
                { name: 'Roll No.', field: 'roll_number' },
                {
                    name: 'Action',
                    type: 'actions',
                    actions: [
                        { type: 'view', showLabel: false, title: 'View', url: '/students/{id}' }
                    ]
                }
            ]
        });
    });
</script>
@endpush

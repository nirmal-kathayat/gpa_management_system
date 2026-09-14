@extends('layouts.app')

@section('title', 'Years & Classes - GPA Management System')
@section('page-title', 'Years & Classes')

@section('content')
<div class="toolbar">
    <div>
        <h2 class="toolbar-title">Years &amp; Classes</h2>
        <p class="toolbar-sub">The academic years the system runs on, and the classes and sections each school has.</p>
    </div>
</div>

<div class="structure-grid">
    {{-- ---- Academic years ---------------------------------------------- --}}
    <section class="panel">
        <div class="panel-head">
            <h3 class="panel-title"><i class="fas fa-calendar-days"></i>Academic Years</h3>
            <span class="panel-tag">Current: {{ $years->firstWhere('is_current', true)?->year ?? '—' }}</span>
        </div>
        <div class="panel-body">
            @can('structure.create')
                <form class="structure-add" action="{{ route('structure.years.store') }}" method="POST" data-validate>
                    @csrf
                    <div class="form-field">
                        <label class="form-label" for="year">Add a year</label>
                        <div class="structure-add-row">
                            <input type="text" id="year" name="year" required placeholder="e.g. {{ (int) ($years->first()?->year ?? 2081) + 1 }}"
                                   inputmode="numeric" pattern="\d{4}" maxlength="4" value="{{ old('year') }}"
                                   class="form-input @error('year') is-invalid @enderror">
                            <label class="check-row">
                                <input type="checkbox" class="check" name="make_current" value="1" {{ old('make_current') ? 'checked' : '' }}>
                                Make current
                            </label>
                            <button type="submit" class="btn-primary-flat"><i class="fas fa-plus"></i>Add</button>
                        </div>
                        @error('year')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                </form>
            @endcan

            @if($years->isEmpty())
                <div class="empty-state">
                    <i class="fas fa-calendar-days"></i>
                    <p>No academic years yet. Add the year in progress to start.</p>
                </div>
            @else
                <table class="record-table">
                    <thead>
                        <tr>
                            <th>Year</th>
                            <th>Report cards</th>
                            <th>Status</th>
                            <th class="cell-actions">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($years as $year)
                            <tr>
                                <td><strong>{{ $year->year }}</strong></td>
                                <td>{{ number_format($year->reports_count) }}</td>
                                <td>
                                    @if($year->is_current)
                                        <span class="pill pill-positive">Current</span>
                                    @else
                                        <span class="pill pill-muted">{{ $year->year > ($years->firstWhere('is_current', true)?->year ?? '') ? 'Upcoming' : 'Past' }}</span>
                                    @endif
                                </td>
                                <td class="cell-actions">
                                    @unless($year->is_current)
                                        @can('structure.update')
                                            <form action="{{ route('structure.years.current', $year) }}" method="POST" class="inline-form"
                                                  data-confirm="Every year picker will start on {{ $year->year }} from now on."
                                                  data-confirm-title="Make {{ $year->year }} the current year?"
                                                  data-confirm-label="Make current" data-confirm-tone="primary">
                                                @csrf @method('PUT')
                                                <button type="submit" class="btn-ghost btn-tiny">Make current</button>
                                            </form>
                                        @endcan
                                        @can('structure.delete')
                                            @if($year->reports_count === 0)
                                                <form action="{{ route('structure.years.destroy', $year) }}" method="POST" class="inline-form"
                                                      data-confirm="{{ $year->year }} will be removed from the list. It has no report cards."
                                                      data-confirm-title="Remove {{ $year->year }}?">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn-ghost btn-tiny is-danger" title="Remove"><i class="fas fa-trash"></i></button>
                                                </form>
                                            @endif
                                        @endcan
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </section>

    {{-- ---- Classes and sections ---------------------------------------- --}}
    <section class="panel">
        <div class="panel-head">
            <h3 class="panel-title"><i class="fas fa-sitemap"></i>Classes &amp; Sections</h3>
            @if($schools->count() > 1)
                <form method="GET" action="{{ route('structure.index') }}" class="inline-form">
                    <select name="school_id" class="form-input form-input-sm" onchange="this.form.submit()" aria-label="School">
                        @foreach($schools as $school)
                            <option value="{{ $school->id }}" {{ $school->id === $schoolId ? 'selected' : '' }}>{{ $school->name }}</option>
                        @endforeach
                    </select>
                </form>
            @else
                <span class="panel-tag">{{ $schools->first()?->name }}</span>
            @endif
        </div>
        <div class="panel-body">
            @can('structure.create')
                <button type="button" class="btn-primary-flat structure-add-class" id="addClassBtn">
                    <i class="fas fa-plus"></i>Add Class
                </button>
            @endcan

            @if($classes->isEmpty())
                <div class="empty-state">
                    <i class="fas fa-sitemap"></i>
                    <p>No classes set up for this school yet.</p>
                </div>
            @else
                <table class="record-table">
                    <thead>
                        <tr>
                            <th>Class</th>
                            <th>Sections</th>
                            <th>Students</th>
                            <th class="cell-actions">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($classes as $class)
                            @php $counts = $studentsByClass->get($class->name, collect()); @endphp
                            <tr>
                                <td><strong>{{ $class->name }}</strong></td>
                                <td>
                                    @foreach($class->sections as $section)
                                        <span class="pill pill-muted section-pill" title="{{ $counts->get($section, 0) }} students">{{ $section }}</span>
                                    @endforeach
                                </td>
                                <td>{{ number_format($counts->sum()) }}</td>
                                <td class="cell-actions">
                                    @can('structure.update')
                                        <button type="button" class="btn-ghost btn-tiny" data-edit-class
                                                data-id="{{ $class->id }}" data-name="{{ $class->name }}"
                                                data-sections="{{ implode(', ', $class->sections) }}">Edit</button>
                                    @endcan
                                    @can('structure.delete')
                                        @if($counts->sum() === 0)
                                            <form action="{{ route('structure.classes.destroy', $class) }}" method="POST" class="inline-form"
                                                  data-confirm="Class {{ $class->name }} will be removed. It has no students."
                                                  data-confirm-title="Remove class {{ $class->name }}?">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn-ghost btn-tiny is-danger" title="Remove"><i class="fas fa-trash"></i></button>
                                            </form>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </section>
</div>

@canany(['structure.create', 'structure.update'])
    @include('structure._class_modal')
@endcanany
@endsection

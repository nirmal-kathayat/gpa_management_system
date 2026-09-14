@extends('layouts.app')

@section('title', 'Promotion - GPA Management System')
@section('page-title', 'Promotion')

@section('content')
<form class="form-card is-full" action="{{ route('promotion.index') }}" method="GET" data-validate>
    <div class="form-card-head is-iconed">
        <span class="card-icon"><i class="fas fa-graduation-cap"></i></span>
        <div>
            <h2 class="form-card-title">Promote a Class</h2>
            <p class="form-card-sub">Move a class up a year, or mark its last year as left. Report cards keep the class they were issued under.</p>
        </div>
    </div>

    <div class="form-card-body">
        <div class="form-grid">
            @include('partials.class-picker')
        </div>
        <p class="form-hint">The academic year decides which result is shown beside each student.</p>
    </div>

    <div class="form-card-foot">
        <button type="submit" class="btn-primary-flat"><i class="fas fa-users"></i>Show Class</button>
    </div>
</form>

@if($students !== null)
    @php
        $failed = $students->filter(fn ($row) => $row['report']?->result_status === 'FAILED')->count();
        $mapForSchool = $classMap[$filters['school_id']] ?? [];
    @endphp

    <form class="form-card is-full promotion" action="{{ route('promotion.store') }}" method="POST"
          data-confirm="" data-confirm-title="Promote?" data-confirm-label="Promote" data-confirm-tone="primary"
          data-class-map='@json($mapForSchool)'
          data-target-counts='@json($targetCounts)'>
        @csrf
        @foreach(['school_id', 'class', 'section', 'academic_year'] as $key)
            <input type="hidden" name="{{ $key }}" value="{{ $filters[$key] }}">
        @endforeach

        <div class="form-card-head ledger-head">
            <div>
                <h2 class="form-card-title">Class {{ $filters['class'] }} {{ $filters['section'] }}</h2>
                <p class="form-card-sub">
                    {{ $students->count() }} {{ Str::plural('student', $students->count()) }} enrolled
                    @if($failed)
                        &nbsp;·&nbsp; <span class="text-danger">{{ $failed }} failed in {{ $filters['academic_year'] }}</span> — unticked below, to repeat the year
                    @endif
                </p>
            </div>
            <span class="ledger-progress" data-count>0 selected</span>
        </div>

        @if($students->isEmpty())
            <div class="form-card-body">
                <div class="empty-state">
                    <i class="fas fa-user-graduate"></i>
                    <p>Nobody is enrolled in class {{ $filters['class'] }} {{ $filters['section'] }}.</p>
                </div>
            </div>
        @else
            <div class="form-card-body">
                <div class="marks-table-wrap">
                    <table class="marks-table promotion-table">
                        <thead>
                            <tr>
                                <th class="c-check"><input type="checkbox" class="check" data-check-all aria-label="Select all"></th>
                                <th>Roll</th>
                                <th class="c-name">Student</th>
                                <th>GPA {{ $filters['academic_year'] }}</th>
                                <th>Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $row)
                                @php
                                    $report = $row['report'];
                                    $status = $report?->result_status;
                                    $isFail = $status === 'FAILED';
                                @endphp
                                <tr class="{{ $isFail ? 'is-failed' : '' }}">
                                    <td class="c-check">
                                        <input type="checkbox" class="check" name="student_ids[]" value="{{ $row['student']->id }}"
                                               {{ in_array($row['student']->id, old('student_ids', $isFail ? [] : [$row['student']->id])) ? 'checked' : '' }}>
                                    </td>
                                    <td class="ledger-roll">{{ $row['student']->roll_number }}</td>
                                    <td class="marks-subject c-name">{{ $row['student']->name }}</td>
                                    <td class="ledger-total">{{ $report ? number_format((float) $report->final_gpa, 2) : '—' }}</td>
                                    <td>
                                        @if(! $report)
                                            <span class="pill pill-muted">No card</span>
                                        @elseif($isFail)
                                            <span class="pill pill-danger">Failed</span>
                                        @elseif($status === 'PENDING')
                                            <span class="pill pill-warning">Pending</span>
                                        @else
                                            <span class="pill pill-positive">{{ Str::of($status)->after('PASSED WITH ')->title()->replace('Passed', 'Passed') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @error('student_ids')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <p class="form-card-section"><span>Move them to</span></p>

            <div class="form-card-body">
                <div class="form-grid">
                    <div class="form-field is-third">
                        <label class="form-label" for="to_class">Class <span class="req">*</span></label>
                        @php $toClass = old('to_class', $suggested?->name ?? \App\Http\Controllers\PromotionController::LEAVE); @endphp
                        <select id="to_class" name="to_class" required class="form-input @error('to_class') is-invalid @enderror">
                            {{-- Keys, so a numeric class name comes back as an int: compared as strings. --}}
                            @foreach(array_keys($mapForSchool) as $name)
                                <option value="{{ $name }}" {{ (string) $toClass === (string) $name ? 'selected' : '' }}>Class {{ $name }}</option>
                            @endforeach
                            <option value="{{ \App\Http\Controllers\PromotionController::LEAVE }}" {{ $toClass === \App\Http\Controllers\PromotionController::LEAVE ? 'selected' : '' }}>
                                — Leave school (graduated / left) —
                            </option>
                        </select>
                        @error('to_class')
                            <p class="form-error">{{ $message }}</p>
                        @else
                            <p class="form-hint" data-target-note>&nbsp;</p>
                        @enderror
                    </div>

                    <div class="form-field is-third" data-section-field>
                        <label class="form-label" for="to_section">Section <span class="req">*</span></label>
                        <select id="to_section" name="to_section" data-current="{{ old('to_section', $filters['section']) }}"
                                class="form-input @error('to_section') is-invalid @enderror">
                        </select>
                        @error('to_section')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-field is-third" data-roll-field>
                        <label class="form-label">Roll numbers</label>
                        @php $rollMode = old('roll_mode', 'keep'); @endphp
                        <label class="check-row"><input type="radio" class="check" name="roll_mode" value="keep" {{ $rollMode === 'keep' ? 'checked' : '' }}> Keep as they are</label>
                        <label class="check-row"><input type="radio" class="check" name="roll_mode" value="name" {{ $rollMode === 'name' ? 'checked' : '' }}> Renumber by name</label>
                        <label class="check-row"><input type="radio" class="check" name="roll_mode" value="rank" {{ $rollMode === 'rank' ? 'checked' : '' }}> Renumber by {{ $filters['academic_year'] }} rank</label>
                        <p class="form-hint">Renumbering carries on after anyone already in the target section.</p>
                    </div>
                </div>
            </div>

            <div class="form-card-foot ledger-foot">
                <p class="form-hint">Students left unticked stay in class {{ $filters['class'] }} {{ $filters['section'] }}.</p>
                <button type="submit" class="btn-primary-flat" data-submit>
                    <i class="fas fa-arrow-up"></i><span data-submit-label>Promote</span>
                </button>
            </div>
        @endif
    </form>
@endif
@endsection

@push('scripts')
<script>
    (function () {
        const form = document.querySelector('form.promotion');
        if (!form) return;

        const LEAVE = @json(\App\Http\Controllers\PromotionController::LEAVE);
        const classMap = JSON.parse(form.dataset.classMap);
        const counts = JSON.parse(form.dataset.targetCounts);
        const boxes = Array.from(form.querySelectorAll('input[name="student_ids[]"]'));
        const all = form.querySelector('[data-check-all]');
        const toClass = document.getElementById('to_class');
        const toSection = document.getElementById('to_section');
        const sectionField = form.querySelector('[data-section-field]');
        const rollField = form.querySelector('[data-roll-field]');
        const note = form.querySelector('[data-target-note]');
        const submit = form.querySelector('[data-submit]');

        function selected() {
            return boxes.filter(b => b.checked).length;
        }

        function refresh() {
            const n = selected();
            const leaving = toClass.value === LEAVE;
            form.querySelector('[data-count]').textContent = n + ' selected';
            form.querySelector('[data-submit-label]').textContent = leaving
                ? 'Mark ' + n + ' as left'
                : 'Promote ' + n + ' to class ' + toClass.value + ' ' + (toSection.value || '');
            submit.disabled = n === 0;
            all.checked = n === boxes.length;
            all.indeterminate = n > 0 && n < boxes.length;

            const target = leaving ? null : counts[toClass.value + '|' + toSection.value];
            if (note) {
                note.textContent = leaving
                    ? 'They stay on record with their report cards, marked as no longer enrolled.'
                    : (target ? 'Class ' + toClass.value + ' ' + toSection.value + ' already has ' + target + ' students.' : 'Class ' + toClass.value + ' ' + (toSection.value || '') + ' is empty.');
            }

            // The confirmation says exactly what is about to happen.
            form.dataset.confirmTitle = leaving ? 'Mark as left?' : 'Promote to class ' + toClass.value + ' ' + toSection.value + '?';
            form.dataset.confirmLabel = leaving ? 'Mark as left' : 'Promote';
            form.dataset.confirm = leaving
                ? n + ' student' + (n === 1 ? '' : 's') + ' will be marked as having left the school.'
                : n + ' student' + (n === 1 ? '' : 's') + ' will move to class ' + toClass.value + ' ' + toSection.value + '. Their report cards stay as they are.';
        }

        function sections() {
            const leaving = toClass.value === LEAVE;
            sectionField.hidden = leaving;
            rollField.hidden = leaving;
            toSection.required = !leaving;

            const values = classMap[toClass.value] || [];
            const current = toSection.dataset.current;
            toSection.innerHTML = '';
            values.forEach(v => {
                const o = document.createElement('option');
                o.value = v; o.textContent = v; o.selected = v === current;
                toSection.appendChild(o);
            });
            if (!toSection.value && values.length) toSection.value = values[0];
            refresh();
        }

        boxes.forEach(b => b.addEventListener('change', refresh));
        all.addEventListener('change', () => { boxes.forEach(b => { b.checked = all.checked; }); refresh(); });
        toClass.addEventListener('change', () => { toSection.dataset.current = ''; sections(); });
        toSection.addEventListener('change', refresh);
        sections();
    })();
</script>
@endpush

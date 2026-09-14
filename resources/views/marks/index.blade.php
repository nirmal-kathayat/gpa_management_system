@extends('layouts.app')

@section('title', 'Marks Entry - GPA Management System')
@section('page-title', 'Marks Entry')

@section('content')
{{--
    Two cards. The first picks what is being entered; the second is the ledger
    for it, one row per student. The picker is a GET form so a filled ledger
    has a URL that can be reloaded or shared.
--}}
<form class="form-card is-roomy ledger-picker" action="{{ route('marks.index') }}" method="GET" data-validate>
    <div class="form-card-head is-iconed">
        <span class="card-icon"><i class="fas fa-pen-to-square"></i></span>
        <div>
            <h2 class="form-card-title">Enter Marks by Class</h2>
            <p class="form-card-sub">One subject, one exam, every student in the class on one page.</p>
        </div>
    </div>

    <div class="form-card-body">
        <div class="form-grid">
            @if($schools->count() > 1)
                <div class="form-field is-third">
                    <label class="form-label" for="school_id">School <span class="req">*</span></label>
                    <select id="school_id" name="school_id" required class="form-input" data-picker data-placeholder="Choose a school">
                        <option value=""></option>
                        @foreach($schools as $school)
                            <option value="{{ $school->id }}" {{ $filters['school_id'] == $school->id ? 'selected' : '' }}>{{ $school->name }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <input type="hidden" id="school_id" name="school_id" value="{{ $filters['school_id'] }}">
            @endif

            <div class="form-field is-third">
                <label class="form-label" for="class">Class <span class="req">*</span></label>
                <select id="class" name="class" required class="form-input" data-picker data-placeholder="Choose a class" data-current="{{ $filters['class'] }}">
                    <option value=""></option>
                </select>
            </div>

            <div class="form-field is-third">
                <label class="form-label" for="section">Section <span class="req">*</span></label>
                <select id="section" name="section" required class="form-input" data-picker data-placeholder="Choose a section" data-current="{{ $filters['section'] }}">
                    <option value=""></option>
                </select>
            </div>

            <div class="form-field is-third">
                <label class="form-label" for="academic_year">Academic Year <span class="req">*</span></label>
                <input type="text" id="academic_year" name="academic_year" required placeholder="e.g. 2081"
                       inputmode="numeric" pattern="\d{4}" maxlength="4" list="academicYears"
                       value="{{ $filters['academic_year'] ?? $years->first() }}" class="form-input">
                <datalist id="academicYears">
                    @foreach($years as $year)
                        <option value="{{ $year }}"></option>
                    @endforeach
                </datalist>
            </div>

            <div class="form-field is-third">
                <label class="form-label" for="subject_id">Subject <span class="req">*</span></label>
                <select id="subject_id" name="subject_id" required class="form-input" data-picker data-placeholder="Choose a subject">
                    <option value=""></option>
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}" {{ $filters['subject_id'] == $subject->id ? 'selected' : '' }}>
                            {{ $subject->name }} (/{{ $subject->full_marks }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-field is-third">
                <label class="form-label" for="exam_type">Exam <span class="req">*</span></label>
                <select id="exam_type" name="exam_type" required class="form-input" data-picker data-placeholder="Choose an exam">
                    <option value=""></option>
                    @foreach($exams as $key => $label)
                        <option value="{{ $key }}" {{ $filters['exam_type'] === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="form-card-foot">
        <button type="submit" class="btn-primary-flat"><i class="fas fa-table-list"></i>Open Ledger</button>
    </div>
</form>

@if($filters['complete'] && $ledger)
    @php
        $subject = $ledger['subject'];
        $students = $ledger['students'];
        $marks = $ledger['marks'];
    @endphp

    <form class="form-card is-roomy ledger" action="{{ route('marks.store') }}" method="POST" data-validate
          data-full-marks="{{ $subject->full_marks }}"
          data-pass-marks="{{ $subject->pass_marks }}"
          data-bands='@json($ledger['bands'])'>
        @csrf
        @foreach(['school_id', 'class', 'section', 'academic_year', 'subject_id', 'exam_type'] as $key)
            <input type="hidden" name="{{ $key }}" value="{{ $filters[$key] }}">
        @endforeach

        <div class="form-card-head ledger-head">
            <div>
                <h2 class="form-card-title">
                    Class {{ $filters['class'] }} {{ $filters['section'] }}
                    &nbsp;·&nbsp; {{ $subject->name }}
                    &nbsp;·&nbsp; {{ $ledger['exam'] }}
                </h2>
                <p class="form-card-sub">
                    Academic year {{ $filters['academic_year'] }}
                    &nbsp;·&nbsp; {{ $students->count() }} {{ Str::plural('student', $students->count()) }}
                    &nbsp;·&nbsp; Full marks {{ $subject->full_marks }}, pass marks {{ $subject->pass_marks }}
                </p>
            </div>
            <span class="ledger-progress" data-progress>
                {{ $marks->count() }} / {{ $students->count() }} entered
            </span>
        </div>

        <div class="form-card-body">
            @if($students->isEmpty())
                <div class="empty-state">
                    <i class="fas fa-user-graduate"></i>
                    <p>No students in class {{ $filters['class'] }} {{ $filters['section'] }} at this school.</p>
                </div>
            @else
                <div class="marks-table-wrap">
                    <table class="marks-table ledger-table">
                        <thead>
                            <tr>
                                <th>Roll</th>
                                <th>Student</th>
                                <th>Theory</th>
                                <th>Practical</th>
                                <th>Total</th>
                                <th>Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $student)
                                @php
                                    $mark = $marks->get($student->id);
                                    $inClass = $student->class === $filters['class'] && $student->section === $filters['section'] && $student->is_active;
                                @endphp
                                <tr data-student="{{ $student->id }}">
                                    <td class="ledger-roll">{{ $student->roll_number }}</td>
                                    <td class="marks-subject">
                                        {{ $student->name }}
                                        @unless($inClass)
                                            {{-- Holds a card for this class and year but is not in it now. --}}
                                            <span class="marks-inactive" title="Now in class {{ $student->class }} {{ $student->section }}{{ $student->is_active ? '' : ', no longer enrolled' }}. Listed because they have a report card for this class and year.">Moved</span>
                                        @endunless
                                    </td>
                                    <td>
                                        <input type="number" min="0" max="{{ $subject->full_marks }}" step="0.01" inputmode="decimal"
                                               name="marks[{{ $student->id }}][th]" data-part="th"
                                               value="{{ old('marks.'.$student->id.'.th', $mark?->theory_marks !== null ? $mark->theory_marks + 0 : '') }}"
                                               class="form-input marks-input @error('marks.'.$student->id.'.th') is-invalid @enderror">
                                    </td>
                                    <td>
                                        <input type="number" min="0" max="{{ $subject->full_marks }}" step="0.01" inputmode="decimal"
                                               name="marks[{{ $student->id }}][pr]" data-part="pr"
                                               value="{{ old('marks.'.$student->id.'.pr', $mark?->practical_marks !== null ? $mark->practical_marks + 0 : '') }}"
                                               class="form-input marks-input @error('marks.'.$student->id.'.pr') is-invalid @enderror">
                                    </td>
                                    <td class="ledger-total" data-total>{{ $mark ? $mark->total_marks + 0 : '—' }}</td>
                                    <td class="ledger-grade" data-grade>
                                        @if($mark)
                                            <span class="grade-pill {{ $mark->grade_point > 0 ? '' : 'is-fail' }}">{{ $mark->letter_grade }}</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- A cell cannot hold its own message, so mark errors are listed here. --}}
                @php
                    $markErrors = collect($errors->getMessages())
                        ->filter(fn ($messages, $key) => str_starts_with($key, 'marks'))
                        ->flatten()->unique();
                @endphp
                @foreach($markErrors as $message)
                    <p class="form-error">{{ $message }}</p>
                @endforeach
            @endif
        </div>

        @if($students->isNotEmpty())
            <div class="form-card-foot ledger-foot">
                <p class="form-hint">
                    <kbd>Enter</kbd> moves down the column. Leave both boxes empty to record no mark.
                </p>
                @can('marks.update')
                    <button type="submit" class="btn-primary-flat"><i class="fas fa-floppy-disk"></i>Save Marks</button>
                @else
                    <span class="form-hint">You can view this ledger but not change it.</span>
                @endcan
            </div>
        @endif
    </form>
@elseif($filters['complete'])
    <div class="form-card is-roomy">
        <div class="form-card-body">
            <div class="empty-state">
                <i class="fas fa-book"></i>
                <p>That subject is no longer available.</p>
            </div>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script>
    (function () {
        // ---- The picker: class and section follow the chosen school. -------
        const classMap = @json($classMap);
        const schoolEl = document.getElementById('school_id');
        const classEl = document.getElementById('class');
        const sectionEl = document.getElementById('section');

        // Every picker is a Select2, so a long list of schools or subjects
        // can be typed into. The placeholder is the empty first option.
        $('[data-picker]').each(function () {
            $(this).select2({
                width: '100%',
                placeholder: this.dataset.placeholder,
                allowClear: false,
                // Short lists do not need a search box.
                minimumResultsForSearch: this.id === 'class' || this.id === 'section' || this.id === 'exam_type' ? Infinity : 0,
            }).on('select2:select', function () {
                // Select2 hides the <select>, so the validator's input listener
                // never sees the fix; clear its message here.
                $(this).removeClass('is-invalid').closest('.form-field').find('.form-error.is-client').remove();
            });
        });

        function fill(select, values, current) {
            select.innerHTML = '<option value=""></option>';

            values.forEach(function (value) {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = value;
                option.selected = value === current;
                select.appendChild(option);
            });

            // Redraws the Select2 from the new options without firing the
            // change handlers below, which would cascade in a loop.
            $(select).trigger('change.select2');
        }

        function classes() {
            const school = classMap[schoolEl.value] || {};
            fill(classEl, Object.keys(school), classEl.dataset.current);
            sections();
        }

        function sections() {
            const school = classMap[schoolEl.value] || {};
            fill(sectionEl, school[classEl.value] || [], sectionEl.dataset.current);
        }

        // Select2 raises its change through jQuery, which a native listener
        // never hears, so these are bound the jQuery way.
        $(schoolEl).on('change', function () {
            classEl.dataset.current = '';
            sectionEl.dataset.current = '';
            classes();
        });
        $(classEl).on('change', function () {
            sectionEl.dataset.current = '';
            sections();
        });
        classes();

        // ---- The ledger: live totals and grades, Enter moves down. ---------
        const ledger = document.querySelector('form.ledger');
        if (!ledger) return;

        const fullMarks = parseFloat(ledger.dataset.fullMarks);
        const passMarks = parseFloat(ledger.dataset.passMarks);
        const bands = JSON.parse(ledger.dataset.bands);
        const rows = Array.from(ledger.querySelectorAll('tbody tr'));

        // The same rule as GradeCalculator: a band covers its whole numbers
        // and the fractions above them, up to the next band.
        function gradeFor(total) {
            const pct = Math.max(0, Math.min(100, total / fullMarks * 100));
            const band = bands.find(b => pct >= b.from && pct < b.to + 1);
            if (!band) return { letter: 'NG', fail: true };
            return { letter: band.letter, fail: band.failing || (!isNaN(passMarks) && total < passMarks) };
        }

        function value(input) {
            return input.value.trim() === '' ? null : parseFloat(input.value);
        }

        function update(row) {
            const th = row.querySelector('[data-part="th"]');
            const pr = row.querySelector('[data-part="pr"]');
            const totalEl = row.querySelector('[data-total]');
            const gradeEl = row.querySelector('[data-grade]');
            const a = value(th), b = value(pr);

            if (a === null && b === null) {
                totalEl.textContent = '—';
                gradeEl.textContent = '—';
                th.classList.remove('is-invalid');
                pr.classList.remove('is-invalid');
                return false;
            }

            const total = (a || 0) + (b || 0);
            const over = total > fullMarks || (a !== null && a < 0) || (b !== null && b < 0);
            th.classList.toggle('is-invalid', over);
            pr.classList.toggle('is-invalid', over);

            totalEl.textContent = parseFloat(total.toFixed(2));

            if (over) {
                gradeEl.innerHTML = '<span class="grade-pill is-fail">&gt; ' + fullMarks + '</span>';
                return true;
            }

            const grade = gradeFor(total);
            gradeEl.innerHTML = '<span class="grade-pill' + (grade.fail ? ' is-fail' : '') + '">' + grade.letter + '</span>';
            return true;
        }

        function progress() {
            const entered = rows.filter(row => {
                const a = value(row.querySelector('[data-part="th"]'));
                const b = value(row.querySelector('[data-part="pr"]'));
                return a !== null || b !== null;
            }).length;
            ledger.querySelector('[data-progress]').textContent = entered + ' / ' + rows.length + ' entered';
        }

        rows.forEach(row => {
            row.querySelectorAll('.marks-input').forEach(input => {
                input.addEventListener('input', () => { update(row); progress(); });

                // Enter goes to the same box on the next row, the way a
                // ledger is filled; the form is submitted from its button.
                input.addEventListener('keydown', event => {
                    if (event.key !== 'Enter') return;
                    event.preventDefault();
                    const index = rows.indexOf(row);
                    const next = rows[index + (event.shiftKey ? -1 : 1)];
                    if (next) {
                        const target = next.querySelector('[data-part="' + input.dataset.part + '"]');
                        target.focus();
                        target.select();
                    }
                });
            });
            update(row);
        });
        progress();

        // Nothing over the limit leaves the page.
        ledger.addEventListener('submit', event => {
            const bad = rows.map(update).length && ledger.querySelector('.marks-input.is-invalid');
            if (bad) {
                event.preventDefault();
                bad.focus();
                if (window.toast) window.toast.error('A mark is over the subject\'s full marks.');
            }
        });
    })();
</script>
@endpush

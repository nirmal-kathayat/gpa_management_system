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
    @php $subject = $ledger['subject']; @endphp

    {{--
        The ledger is a TableHelper grid so a large class pages, searches and
        sorts like every other list. Each row's two boxes are inputs; what is
        typed is kept in a map keyed by student id, so it survives paging and
        is posted in one go from the Save button.
    --}}
    <div class="form-card is-roomy ledger" id="ledger"
         data-rows-url="{{ route('marks.rows', Arr::except($filters, 'complete')) }}"
         data-save-url="{{ route('marks.store') }}"
         data-filters='@json(Arr::except($filters, 'complete'))'
         data-full-marks="{{ $subject->full_marks }}"
         data-pass-marks="{{ $subject->pass_marks }}"
         data-students="{{ $ledger['students'] }}"
         data-entered="{{ $ledger['entered'] }}"
         data-bands='@json($ledger['bands'])'>

        <div class="form-card-head ledger-head">
            <div>
                <h2 class="form-card-title">
                    Class {{ $filters['class'] }} {{ $filters['section'] }}
                    &nbsp;·&nbsp; {{ $subject->name }}
                    &nbsp;·&nbsp; {{ $ledger['exam'] }}
                </h2>
                <p class="form-card-sub">
                    Academic year {{ $filters['academic_year'] }}
                    &nbsp;·&nbsp; {{ $ledger['students'] }} {{ Str::plural('student', $ledger['students']) }}
                    &nbsp;·&nbsp; Full marks {{ $subject->full_marks }}, pass marks {{ $subject->pass_marks }}
                </p>
            </div>
            <span class="ledger-progress" data-progress>
                {{ $ledger['entered'] }} / {{ $ledger['students'] }} entered
            </span>
        </div>

        <div class="form-card-body">
            <div id="ledger-grid" class="cq-grid ledger-grid"></div>
        </div>

        <div class="form-card-foot ledger-foot">
            <p class="form-hint">
                <kbd>Enter</kbd> moves down the column. Leave both boxes empty to record no mark.
                <span class="ledger-unsaved" data-unsaved hidden></span>
            </p>
            @can('marks.update')
                <button type="button" class="btn-primary-flat" data-save disabled>
                    <i class="fas fa-floppy-disk"></i>Save Marks
                </button>
            @else
                <span class="form-hint">You can view this ledger but not change it.</span>
            @endcan
        </div>
    </div>
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

        // ---- The ledger grid ------------------------------------------------
        const ledger = document.getElementById('ledger');
        if (!ledger) return;

        const canSave = !!ledger.querySelector('[data-save]');
        const fullMarks = parseFloat(ledger.dataset.fullMarks);
        const passMarks = parseFloat(ledger.dataset.passMarks);
        const bands = JSON.parse(ledger.dataset.bands);
        const filters = JSON.parse(ledger.dataset.filters);
        const studentCount = parseInt(ledger.dataset.students, 10);
        let enteredOnServer = parseInt(ledger.dataset.entered, 10);

        // What has been typed and not yet saved, by student id. The grid
        // re-renders its rows on every page or search, so this is the truth
        // and the inputs are drawn from it.
        const edits = {};
        // What the server has, by student id, for rows seen so far - so the
        // progress count can be kept right as boxes are filled or emptied.
        const saved = {};

        const esc = (s) => String(s ?? '').replace(/[&<>"']/g, c =>
            ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

        // The same rule as GradeCalculator: a band covers its whole numbers
        // and the fractions above them, up to the next band.
        function gradeFor(total) {
            const pct = Math.max(0, Math.min(100, total / fullMarks * 100));
            const band = bands.find(b => pct >= b.from && pct < b.to + 1);
            if (!band) return { letter: 'NG', fail: true };
            return { letter: band.letter, fail: band.failing || (!isNaN(passMarks) && total < passMarks) };
        }

        function valueOf(row, part) {
            const edit = edits[row.id];
            if (edit) return edit[part];
            return row[part];
        }

        function num(v) {
            return v === null || v === undefined || v === '' ? null : parseFloat(v);
        }

        // Total and grade for a row from whatever it currently holds.
        function result(row) {
            const a = num(valueOf(row, 'th')), b = num(valueOf(row, 'pr'));
            if (a === null && b === null) return null;
            const total = (a || 0) + (b || 0);
            const over = total > fullMarks || (a !== null && a < 0) || (b !== null && b < 0);
            return { total, over, grade: over ? null : gradeFor(total) };
        }

        function pill(r) {
            if (!r) return '—';
            if (r.over) return '<span class="grade-pill is-fail">&gt; ' + fullMarks + '</span>';
            return '<span class="grade-pill' + (r.grade.fail ? ' is-fail' : '') + '">' + esc(r.grade.letter) + '</span>';
        }

        function box(row, part) {
            const v = valueOf(row, part);
            const r = result(row);
            return '<input type="number" min="0" max="' + fullMarks + '" step="0.01" inputmode="decimal"'
                + ' class="form-input marks-input' + (r && r.over ? ' is-invalid' : '') + '"'
                + ' data-student="' + row.id + '" data-part="' + part + '"'
                + ' value="' + esc(v === null || v === undefined ? '' : v) + '"'
                + (canSave ? '' : ' disabled') + '>';
        }

        const grid = new TableHelper({
            containerId: 'ledger-grid',
            apiUrl: ledger.dataset.rowsUrl,
            perPage: 10,
            perPageOptions: [10, 25, 50, 100],
            pagination: true,
            enableCheckbox: false,
            stickyHeader: false,
            autoInitDatePickers: false,
            emptyMessage: 'No students in class ' + esc(filters.class) + ' ' + esc(filters.section) + ' at this school.',
            search: { placeholder: 'Find a student by name or roll…' },
            enableSortColumns: ['roll_number', 'name'],

            columns: [
                { name: 'Roll', field: 'roll_number', width: '70px', align: 'center', render: (r) => esc(r.roll_number) },
                {
                    name: 'Student', field: 'name',
                    render: (r) => esc(r.name) + (r.moved
                        ? ' <span class="marks-inactive" title="Now in class ' + esc(r.now) + '. Listed because they have a report card for this class and year.">Moved</span>'
                        : '')
                },
                { name: 'Theory', field: 'th', width: '120px', align: 'center', render: (r) => box(r, 'th') },
                { name: 'Practical', field: 'pr', width: '120px', align: 'center', render: (r) => box(r, 'pr') },
                {
                    name: 'Total', field: 'total', width: '90px', align: 'center', class: 'ledger-total',
                    render: (r) => { const x = result(r); return x ? parseFloat(x.total.toFixed(2)) : '—'; }
                },
                {
                    name: 'Grade', field: 'grade', width: '90px', align: 'center', class: 'ledger-grade',
                    render: (r) => pill(result(r))
                }
            ],

            filters: {
                autoGenerateColumnFilters: false,
                columnFilters: [
                    { field: 'roll_number', type: 'text', param: 'roll_number', placeholder: 'Roll' },
                    { field: 'name', type: 'text', param: 'name', placeholder: 'Name' },
                    {
                        field: 'grade', type: 'select', param: 'entered', allowBlank: true,
                        options: [{ value: '0', label: 'Missing' }, { value: '1', label: 'Entered' }]
                    }
                ]
            },

            onDataLoaded: (rows) => {
                rows.forEach(r => { saved[r.id] = r.th !== null || r.pr !== null; });
                // Rendering happens right after this hook; the focus request
                // left by Enter on the last row is honoured once it has.
                setTimeout(focusPending, 0);
            }
        });

        // ---- Typing ---------------------------------------------------------
        const container = document.getElementById('ledger-grid');

        function rowOf(input) {
            return grid.gridData.find(r => String(r.id) === input.dataset.student);
        }

        function redraw(input) {
            const row = rowOf(input);
            const tr = input.closest('tr');
            if (!row || !tr) return;
            const r = result(row);
            tr.querySelector('.ledger-total').innerHTML = r ? parseFloat(r.total.toFixed(2)) : '—';
            tr.querySelector('.ledger-grade').innerHTML = pill(r);
            tr.querySelectorAll('.marks-input').forEach(i => i.classList.toggle('is-invalid', !!(r && r.over)));
        }

        function progress() {
            // Rows with an unsaved edit count by the edit; the rest by the server.
            let entered = enteredOnServer;
            Object.keys(edits).forEach(id => {
                const has = num(edits[id].th) !== null || num(edits[id].pr) !== null;
                if (has && !saved[id]) entered++;
                if (!has && saved[id]) entered--;
            });
            ledger.querySelector('[data-progress]').textContent = entered + ' / ' + studentCount + ' entered';

            const pending = Object.keys(edits).length;
            const note = ledger.querySelector('[data-unsaved]');
            note.hidden = pending === 0;
            note.textContent = pending === 1 ? '1 unsaved change.' : pending + ' unsaved changes.';

            const save = ledger.querySelector('[data-save]');
            if (save) save.disabled = pending === 0;
        }

        container.addEventListener('input', (event) => {
            const input = event.target;
            if (!input.matches('.marks-input')) return;

            const row = rowOf(input);
            if (!row) return;

            const edit = edits[row.id] || (edits[row.id] = { th: row.th, pr: row.pr });
            edit[input.dataset.part] = input.value.trim() === '' ? null : input.value;

            // Typed back to what the server has - nothing to save for this row.
            if (num(edit.th) === num(row.th) && num(edit.pr) === num(row.pr)) {
                delete edits[row.id];
            }

            redraw(input);
            progress();
        });

        // Enter goes to the same box on the next row, the way a ledger is
        // filled; on the last row of a page it turns the page.
        let pendingFocus = null;

        function focusPending() {
            if (!pendingFocus) return;
            const inputs = container.querySelectorAll('.marks-input[data-part="' + pendingFocus + '"]');
            const target = inputs[0];
            pendingFocus = null;
            if (target) { target.focus(); target.select(); }
        }

        container.addEventListener('keydown', (event) => {
            const input = event.target;
            if (!input.matches('.marks-input') || event.key !== 'Enter') return;
            event.preventDefault();

            const column = Array.from(container.querySelectorAll('.marks-input[data-part="' + input.dataset.part + '"]'));
            const index = column.indexOf(input);
            const next = column[index + (event.shiftKey ? -1 : 1)];

            if (next) {
                next.focus();
                next.select();
            } else if (!event.shiftKey && grid.currentPage < grid.totalPages) {
                pendingFocus = input.dataset.part;
                grid.loadTable(grid.currentPage + 1);
            }
        });

        // ---- Saving ---------------------------------------------------------
        const saveButton = ledger.querySelector('[data-save]');

        if (saveButton) {
            saveButton.addEventListener('click', () => {
                const over = Object.keys(edits).find(id => {
                    const a = num(edits[id].th) || 0, b = num(edits[id].pr) || 0;
                    return a + b > fullMarks || a < 0 || b < 0;
                });
                if (over) {
                    window.toast.error('A mark is over the subject\'s full marks (' + fullMarks + ').');
                    const box = container.querySelector('.marks-input[data-student="' + over + '"]');
                    if (box) box.focus();
                    return;
                }

                const marks = {};
                Object.keys(edits).forEach(id => {
                    marks[id] = { th: edits[id].th ?? '', pr: edits[id].pr ?? '' };
                });

                saveButton.disabled = true;

                TableHelper.ajax(ledger.dataset.saveUrl, {
                    method: 'POST',
                    useJQuery: false,
                    showErrorAlert: false,
                    data: Object.assign({}, filters, { marks }),
                })
                .then(response => {
                    if (!response || response.success !== true) throw response;

                    Object.keys(edits).forEach(id => { delete edits[id]; });
                    enteredOnServer = null; // refreshed from the rows below
                    window.toast.success(response.message);
                    grid.refresh();
                    refreshCount();
                })
                .catch(err => {
                    saveButton.disabled = false;
                    const body = (err && err.responseJSON) || err || {};
                    const errors = body.errors ? Object.values(body.errors).flat() : null;
                    window.toast.error(errors && errors.length ? errors[0] : body.message || 'The marks could not be saved.');
                });
            });
        }

        // The header count is the whole class, not the page, so it comes from
        // the server after a save.
        function refreshCount() {
            fetch(ledger.dataset.rowsUrl + '&per_page=1&entered=1', { credentials: 'same-origin' })
                .then(r => r.json())
                .then(r => { enteredOnServer = r.total || 0; progress(); });
        }

        // Typed marks that were never saved should not be lost to a stray click.
        window.addEventListener('beforeunload', (event) => {
            if (Object.keys(edits).length) {
                event.preventDefault();
                event.returnValue = '';
            }
        });

        progress();
    })();
</script>
@endpush

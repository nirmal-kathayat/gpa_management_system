{{--
    Shared by reports/create and reports/edit. $report and $existingMarks are set
    only when editing.
--}}
@php
    $report = $report ?? null;
    $existingMarks = $existingMarks ?? [];

    // After a failed submit the picker has to come back on whatever was chosen.
    $chosenId = old('student_id', $report?->student_id ?? ($selectedStudent ?? null)?->id);
    $chosenStudent = $chosenId
        ? ($report?->student_id == $chosenId ? $report->student : \App\Models\Student::find($chosenId))
        : null;

    $terms = [
        'first_terminal' => 'First Terminal',
        'second_terminal' => 'Second Terminal',
        'final_terminal' => 'Final Terminal',
        'pre_board' => 'Pre-Board',
    ];

    $behaviours = [
        'class_response' => 'Class Response',
        'discipline' => 'Discipline',
        'leadership' => 'Leadership',
        'neatness' => 'Neatness',
        'punctuality' => 'Punctuality',
        'regularity' => 'Regularity',
        'social_conduct' => 'Social Conduct',
        'sports_game' => 'Sports / Games',
    ];

    $behaviourGrades = ['A+', 'A', 'B+', 'B', 'C+', 'C', 'D'];
@endphp

<div class="form-card-head">
    <h2 class="form-card-title">{{ $title }}</h2>
    <p class="form-card-sub">{{ $subtitle }}</p>
</div>

<div class="form-card-body">
    <div class="form-grid">
        <div class="form-field">
            <label class="form-label" for="student_id">Student <span class="req">*</span></label>
            {{-- Students are searched over AJAX, so only the chosen one is rendered. --}}
            <select id="student_id" name="student_id" required data-student-select
                    class="form-input @error('student_id') is-invalid @enderror">
                <option value=""></option>
                @if($chosenStudent)
                    <option value="{{ $chosenStudent->id }}" selected>
                        {{ \App\Http\Controllers\StudentController::studentLabel($chosenStudent) }}
                    </option>
                @endif
            </select>
            @error('student_id')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-field">
            <label class="form-label" for="academic_year">Academic Year <span class="req">*</span></label>
            @php
                $formYears = \App\Models\AcademicYear::ordered()->get();
                $chosenYear = old('academic_year', $report?->academic_year ?? \App\Models\AcademicYear::current());
            @endphp
            <select id="academic_year" name="academic_year" required
                    class="form-input @error('academic_year') is-invalid @enderror">
                <option value="">Select year</option>
                @foreach($formYears as $formYear)
                    <option value="{{ $formYear->year }}" {{ $chosenYear === $formYear->year ? 'selected' : '' }}>
                        {{ $formYear->year }}{{ $formYear->is_current ? ' (current)' : '' }}
                    </option>
                @endforeach
                {{-- A card issued under a year since removed from the list still shows it. --}}
                @if($chosenYear && ! $formYears->contains('year', $chosenYear))
                    <option value="{{ $chosenYear }}" selected>{{ $chosenYear }}</option>
                @endif
            </select>
            @error('academic_year')
                <p class="form-error">{{ $message }}</p>
            @else
                <p class="form-hint">Years are managed under <a href="{{ route('structure.index') }}">Years &amp; Classes</a>.</p>
            @enderror
        </div>

        {{--
            Written on the card itself. They are filled from the chosen student
            and can be corrected, so a card issued for class 9 still says class 9
            after the student moves up.
        --}}
        @php
            $snapshot = fn ($field) => old($field, $report?->$field ?? $chosenStudent?->$field);
        @endphp
        <div class="form-field is-third">
            <label class="form-label" for="class">Class <span class="req">*</span></label>
            <input type="text" id="class" name="class" required maxlength="50" data-from-student="class"
                   value="{{ $snapshot('class') }}"
                   class="form-input @error('class') is-invalid @enderror">
            @error('class')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-field is-third">
            <label class="form-label" for="section">Section <span class="req">*</span></label>
            <input type="text" id="section" name="section" required maxlength="10" data-from-student="section"
                   value="{{ $snapshot('section') }}"
                   class="form-input @error('section') is-invalid @enderror">
            @error('section')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-field is-third">
            <label class="form-label" for="roll_number">Roll No. <span class="req">*</span></label>
            <input type="number" id="roll_number" name="roll_number" required min="1" data-from-student="roll_number"
                   value="{{ $snapshot('roll_number') }}"
                   class="form-input @error('roll_number') is-invalid @enderror">
            @error('roll_number')
                <p class="form-error">{{ $message }}</p>
            @else
                <p class="form-hint">As the student was enrolled that year.</p>
            @enderror
        </div>
    </div>
</div>

<p class="form-card-section"><span>Subject marks</span></p>

<div class="form-card-body">
    <div class="marks-table-wrap">
        <table class="marks-table">
            <thead>
                <tr>
                    <th rowspan="2">Subject</th>
                    @foreach($terms as $term)
                        <th colspan="2">{{ $term }}</th>
                    @endforeach
                </tr>
                <tr>
                    @foreach($terms as $term)
                        <th>Theory</th>
                        <th>Practical</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($subjects as $index => $subject)
                    <tr>
                        <td class="marks-subject">
                            {{ $subject->name }}
                            <span class="marks-full">/ {{ $subject->full_marks }}</span>
                            @unless($subject->is_active)
                                {{-- Kept on the card because it already has marks. --}}
                                <span class="marks-inactive" title="This subject is no longer active, but this report card already has marks for it.">Inactive</span>
                            @endunless
                            <input type="hidden" name="marks[{{ $index }}][subject_id]" value="{{ $subject->id }}">
                        </td>
                        @foreach($terms as $key => $term)
                            @foreach(['th' => 'theory', 'pr' => 'practical'] as $suffix => $stored)
                                <td>
                                    <input type="number" min="0" max="{{ $subject->full_marks }}" step="0.01"
                                           name="marks[{{ $index }}][{{ $key }}_{{ $suffix }}]"
                                           value="{{ old('marks.'.$index.'.'.$key.'_'.$suffix, $existingMarks[$subject->id][$key][$stored] ?? '') }}"
                                           class="form-input marks-input @error('marks.'.$index.'.'.$key.'_'.$suffix) is-invalid @enderror">
                                </td>
                            @endforeach
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="marks-empty">
                            No subjects yet. <a href="{{ route('subjects.create') }}">Add one</a> before creating a report.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- A cell cannot hold its own message, so mark errors are listed here. --}}
    @php
        $markErrors = collect($errors->getMessages())
            ->filter(fn ($messages, $key) => str_starts_with($key, 'marks.'))
            ->flatten()->unique();
    @endphp
    @foreach($markErrors as $message)
        <p class="form-error">{{ $message }}</p>
    @endforeach
</div>

<p class="form-card-section"><span>Attendance</span></p>

<div class="form-card-body">
    <div class="form-grid">
        <div class="form-field">
            <label class="form-label" for="attendance_days">Days Present</label>
            <input type="number" id="attendance_days" name="attendance_days" min="0"
                   value="{{ old('attendance_days', $report?->attendance_days) }}"
                   class="form-input @error('attendance_days') is-invalid @enderror">
            @error('attendance_days')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-field">
            <label class="form-label" for="total_days">Total Days</label>
            <input type="number" id="total_days" name="total_days" min="0"
                   value="{{ old('total_days', $report?->total_days) }}"
                   class="form-input @error('total_days') is-invalid @enderror">
            @error('total_days')
                <p class="form-error">{{ $message }}</p>
            @else
                <p class="form-hint">Days present cannot be more than this.</p>
            @enderror
        </div>
    </div>
</div>

<p class="form-card-section"><span>Behavioural assessment</span></p>

<div class="form-card-body">
    <div class="form-grid">
        @foreach($behaviours as $key => $label)
            <div class="form-field is-third">
                <label class="form-label" for="{{ $key }}">{{ $label }}</label>
                @php
                    // Every grade is required, so a new report starts on the same
                    // defaults the old create form used rather than a blank option.
                    $current = old($key, $report?->$key ?? ($key === 'sports_game' ? 'B' : 'A'));
                @endphp
                <select id="{{ $key }}" name="{{ $key }}" required
                        class="form-input @error($key) is-invalid @enderror">
                    @foreach($behaviourGrades as $grade)
                        <option value="{{ $grade }}" {{ $current === $grade ? 'selected' : '' }}>{{ $grade }}</option>
                    @endforeach
                </select>
                @error($key)<p class="form-error">{{ $message }}</p>@enderror
            </div>
        @endforeach

        <div class="form-field is-wide">
            <label class="form-label" for="remarks">Remarks</label>
            <textarea id="remarks" name="remarks" rows="3" placeholder="Additional comments"
                      class="form-input @error('remarks') is-invalid @enderror">{{ old('remarks', $report?->remarks) }}</textarea>
            @error('remarks')<p class="form-error">{{ $message }}</p>@enderror
        </div>
    </div>
</div>

<div class="form-card-foot">
    <a href="{{ route('reports.index') }}" class="btn-ghost">Cancel</a>
    <button type="submit" class="btn-primary-flat">{{ $submitLabel }}</button>
</div>

@push('scripts')
<script>
    $(function () {
        $('[data-student-select]').select2({
            width: '100%',
            placeholder: 'Search a student by name, roll or symbol number',
            allowClear: false,
            minimumInputLength: 0,
            ajax: {
                url: '{{ route('students.options') }}',
                dataType: 'json',
                delay: 250,
                data: (params) => ({ q: params.term, page: params.page || 1 }),
                processResults: (data) => data,
                cache: true,
            },
            templateResult: function (item) {
                if (!item.id) return item.text;

                const row = document.createElement('div');
                row.textContent = item.text;

                if (item.school || item.inactive) {
                    const meta = document.createElement('div');
                    meta.className = 'select2-student-meta';
                    meta.textContent = item.school || '';
                    if (item.inactive) {
                        const left = document.createElement('span');
                        left.className = 'is-left';
                        left.textContent = (item.school ? ' · ' : '') + 'No longer enrolled';
                        meta.appendChild(left);
                    }
                    row.appendChild(meta);
                }

                return row;
            },
            language: {
                inputTooShort: () => 'Type to search students',
                searching: () => 'Searching…',
                noResults: () => 'No students found',
            },
        });

        // Select2 hides the original <select>, so the shared validator cannot
        // focus it; point it at the control the user can actually see.
        $('[data-student-select]').on('select2:select', function (event) {
            $(this).removeClass('is-invalid').closest('.form-field').find('.form-error.is-client').remove();

            // Picking a student writes their current class onto the card; the
            // fields stay editable for a card being issued for an earlier year.
            const student = event.params.data;
            $('[data-from-student]').each(function () {
                const value = student[this.dataset.fromStudent];
                if (value !== undefined && value !== null) {
                    this.value = value;
                }
            });
        });
    });
</script>
@endpush

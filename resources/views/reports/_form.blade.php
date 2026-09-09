{{--
    Shared by reports/create and reports/edit. $report and $existingMarks are set
    only when editing.
--}}
@php
    $report = $report ?? null;
    $existingMarks = $existingMarks ?? [];

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
            <select id="student_id" name="student_id" required
                    class="form-input @error('student_id') is-invalid @enderror">
                <option value="">Select student</option>
                @foreach($students as $student)
                    <option value="{{ $student->id }}"
                        {{ (string) old('student_id', $report?->student_id) === (string) $student->id ? 'selected' : '' }}>
                        {{ $student->name }} &mdash; {{ $student->class }} {{ $student->section }} (Roll {{ $student->roll_number }})
                    </option>
                @endforeach
            </select>
            @error('student_id')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-field">
            <label class="form-label" for="academic_year">Academic Year <span class="req">*</span></label>
            <input type="text" id="academic_year" name="academic_year" required placeholder="e.g. 2081"
                   value="{{ old('academic_year', $report?->academic_year ?? date('Y')) }}"
                   class="form-input @error('academic_year') is-invalid @enderror">
            @error('academic_year')<p class="form-error">{{ $message }}</p>@enderror
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
                            <input type="hidden" name="marks[{{ $index }}][subject_id]" value="{{ $subject->id }}">
                        </td>
                        @foreach($terms as $key => $term)
                            @foreach(['th' => 'theory', 'pr' => 'practical'] as $suffix => $stored)
                                <td>
                                    <input type="number" min="0" max="100" step="0.01"
                                           name="marks[{{ $index }}][{{ $key }}_{{ $suffix }}]"
                                           value="{{ old('marks.'.$index.'.'.$key.'_'.$suffix, $existingMarks[$subject->id][$key][$stored] ?? '') }}"
                                           class="form-input marks-input">
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
            @error('total_days')<p class="form-error">{{ $message }}</p>@enderror
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

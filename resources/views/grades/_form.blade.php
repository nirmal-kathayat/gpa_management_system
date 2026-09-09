{{-- Shared by grades/create and grades/edit. $grade is set only when editing. --}}
@php $grade = $grade ?? null; @endphp

<div class="form-card-head">
    <h2 class="form-card-title">{{ $title }}</h2>
    <p class="form-card-sub">{{ $subtitle }}</p>
</div>

<div class="form-card-body">
    <div class="form-grid">
        <div class="form-field">
            <label class="form-label" for="letter_grade">Letter Grade <span class="req">*</span></label>
            <input type="text" id="letter_grade" name="letter_grade" required autofocus
                   value="{{ old('letter_grade', $grade?->letter_grade) }}" placeholder="e.g. A+, B, C+"
                   class="form-input @error('letter_grade') is-invalid @enderror">
            @error('letter_grade')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-field">
            <label class="form-label" for="grade_point">Grade Point <span class="req">*</span></label>
            <input type="number" id="grade_point" name="grade_point" min="0" max="4" step="0.1" required
                   value="{{ old('grade_point', $grade?->grade_point) }}" placeholder="e.g. 4.0"
                   class="form-input @error('grade_point') is-invalid @enderror">
            @error('grade_point')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-field">
            <label class="form-label" for="marks_from">Marks From <span class="req">*</span></label>
            <input type="number" id="marks_from" name="marks_from" min="0" max="100" required
                   value="{{ old('marks_from', $grade?->marks_from) }}"
                   class="form-input @error('marks_from') is-invalid @enderror">
            @error('marks_from')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-field">
            <label class="form-label" for="marks_to">Marks To <span class="req">*</span></label>
            <input type="number" id="marks_to" name="marks_to" min="0" max="100" required
                   value="{{ old('marks_to', $grade?->marks_to) }}"
                   class="form-input @error('marks_to') is-invalid @enderror">
            @error('marks_to')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-field is-wide">
            <label class="form-label" for="remarks">Remarks <span class="req">*</span></label>
            <input type="text" id="remarks" name="remarks" required
                   value="{{ old('remarks', $grade?->remarks) }}" placeholder="e.g. Outstanding"
                   class="form-input @error('remarks') is-invalid @enderror">
            @error('remarks')<p class="form-error">{{ $message }}</p>@enderror
        </div>
    </div>
</div>

<div class="form-card-foot">
    <a href="{{ route('grades.index') }}" class="btn-ghost">Cancel</a>
    <button type="submit" class="btn-primary-flat">{{ $submitLabel }}</button>
</div>

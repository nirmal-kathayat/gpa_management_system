{{-- Shared by subjects/create and subjects/edit. $subject is set only when editing. --}}
@php $subject = $subject ?? null; @endphp

<div class="form-card-head">
    <h2 class="form-card-title">{{ $title }}</h2>
    <p class="form-card-sub">{{ $subtitle }}</p>
</div>

<div class="form-card-body">
    <div class="form-grid">
        <div class="form-field">
            <label class="form-label" for="name">Subject Name <span class="req">*</span></label>
            <input type="text" id="name" name="name" value="{{ old('name', $subject?->name) }}" required autofocus
                   placeholder="e.g. English" class="form-input @error('name') is-invalid @enderror">
            @error('name')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-field">
            <label class="form-label" for="code">Subject Code <span class="req">*</span></label>
            <input type="text" id="code" name="code" value="{{ old('code', $subject?->code) }}" required
                   placeholder="e.g. ENG1" class="form-input @error('code') is-invalid @enderror">
            @error('code')
                <p class="form-error">{{ $message }}</p>
            @else
                <p class="form-hint">A unique short identifier for this subject.</p>
            @enderror
        </div>

        <div class="form-field">
            <label class="form-label" for="full_marks">Full Marks <span class="req">*</span></label>
            <input type="number" id="full_marks" name="full_marks" min="1" max="200" required
                   value="{{ old('full_marks', $subject?->full_marks ?? 100) }}"
                   class="form-input @error('full_marks') is-invalid @enderror">
            @error('full_marks')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-field">
            <label class="form-label" for="pass_marks">Pass Marks <span class="req">*</span></label>
            <input type="number" id="pass_marks" name="pass_marks" min="1" max="100" required
                   value="{{ old('pass_marks', $subject?->pass_marks ?? 32) }}"
                   class="form-input @error('pass_marks') is-invalid @enderror">
            @error('pass_marks')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-field is-wide">
            <label class="check-row">
                <input type="checkbox" class="check" name="is_active" value="1"
                       {{ old('is_active', $subject?->is_active ?? true) ? 'checked' : '' }}>
                Active subject
            </label>
            <p class="form-hint">Inactive subjects stay on existing report cards but cannot be added to new ones.</p>
        </div>
    </div>
</div>

<div class="form-card-foot">
    <a href="{{ route('subjects.index') }}" class="btn-ghost">Cancel</a>
    <button type="submit" class="btn-primary-flat">{{ $submitLabel }}</button>
</div>

{{-- Shared by students/create and students/edit. $student is set only when editing. --}}
@php $student = $student ?? null; @endphp

<div class="form-card-head">
    <h2 class="form-card-title">{{ $title }}</h2>
    <p class="form-card-sub">{{ $subtitle }}</p>
</div>

<div class="form-card-body">
    <div class="form-grid">
        <div class="form-field">
            <label class="form-label" for="name">Name <span class="req">*</span></label>
            <input type="text" id="name" name="name" value="{{ old('name', $student?->name) }}" required autofocus
                   placeholder="Enter full name" class="form-input @error('name') is-invalid @enderror">
            @error('name')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-field">
            <label class="form-label" for="school_id">School <span class="req">*</span></label>
            <select id="school_id" name="school_id" required
                    class="form-input @error('school_id') is-invalid @enderror">
                <option value="">Select school</option>
                @foreach($schools as $school)
                    <option value="{{ $school->id }}"
                        {{ (string) old('school_id', $student?->school_id) === (string) $school->id ? 'selected' : '' }}>
                        {{ $school->name }}
                    </option>
                @endforeach
            </select>
            @error('school_id')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-field is-third">
            <label class="form-label" for="class">Class <span class="req">*</span></label>
            <input type="text" id="class" name="class" value="{{ old('class', $student?->class) }}" required
                   placeholder="e.g. 10" class="form-input @error('class') is-invalid @enderror">
            @error('class')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-field is-third">
            <label class="form-label" for="section">Section <span class="req">*</span></label>
            <input type="text" id="section" name="section" value="{{ old('section', $student?->section) }}" required
                   placeholder="e.g. A" class="form-input @error('section') is-invalid @enderror">
            @error('section')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-field is-third">
            <label class="form-label" for="roll_number">Roll Number <span class="req">*</span></label>
            <input type="number" id="roll_number" name="roll_number" required
                   value="{{ old('roll_number', $student?->roll_number) }}"
                   class="form-input @error('roll_number') is-invalid @enderror">
            @error('roll_number')<p class="form-error">{{ $message }}</p>@enderror
        </div>
    </div>
</div>

<p class="form-card-section">Family and contact</p>

<div class="form-card-body">
    <div class="form-grid">
        <div class="form-field">
            <label class="form-label" for="father_name">Father's Name</label>
            <input type="text" id="father_name" name="father_name"
                   value="{{ old('father_name', $student?->father_name) }}"
                   class="form-input @error('father_name') is-invalid @enderror">
            @error('father_name')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-field">
            <label class="form-label" for="mother_name">Mother's Name</label>
            <input type="text" id="mother_name" name="mother_name"
                   value="{{ old('mother_name', $student?->mother_name) }}"
                   class="form-input @error('mother_name') is-invalid @enderror">
            @error('mother_name')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-field">
            <label class="form-label" for="date_of_birth">Date of Birth</label>
            <input type="date" id="date_of_birth" name="date_of_birth"
                   value="{{ old('date_of_birth', $student?->date_of_birth?->format('Y-m-d')) }}"
                   class="form-input @error('date_of_birth') is-invalid @enderror">
            @error('date_of_birth')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-field">
            <label class="form-label" for="phone">Phone</label>
            <input type="text" id="phone" name="phone" value="{{ old('phone', $student?->phone) }}"
                   placeholder="Enter phone number" class="form-input @error('phone') is-invalid @enderror">
            @error('phone')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-field is-wide">
            <label class="form-label" for="address">Address</label>
            <textarea id="address" name="address" rows="3" placeholder="Enter complete address"
                      class="form-input @error('address') is-invalid @enderror">{{ old('address', $student?->address) }}</textarea>
            @error('address')<p class="form-error">{{ $message }}</p>@enderror
        </div>
    </div>
</div>

<div class="form-card-foot">
    <a href="{{ route('students.index') }}" class="btn-ghost">Cancel</a>
    <button type="submit" class="btn-primary-flat">{{ $submitLabel }}</button>
</div>

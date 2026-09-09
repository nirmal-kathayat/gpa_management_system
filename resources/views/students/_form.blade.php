{{-- Shared by students/create and students/edit. $student is set only when editing. --}}
@php $student = $student ?? null; @endphp

<div class="form-card-head">
    <h2 class="form-card-title">{{ $title }}</h2>
    <p class="form-card-sub">{{ $subtitle }}</p>
</div>

<div class="form-card-body">
    <div class="form-grid">
        <div class="form-field is-third">
            <label class="form-label" for="name">Name <span class="req">*</span></label>
            <input type="text" id="name" name="name" value="{{ old('name', $student?->name) }}" required autofocus
                   placeholder="Enter full name" class="form-input @error('name') is-invalid @enderror">
            @error('name')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-field is-third">
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
            <label class="form-label" for="symbol_number">Symbol Number</label>
            <input type="text" id="symbol_number" name="symbol_number"
                   value="{{ old('symbol_number', $student?->symbol_number) }}"
                   placeholder="Board exam symbol no."
                   class="form-input @error('symbol_number') is-invalid @enderror">
            @error('symbol_number')<p class="form-error">{{ $message }}</p>@enderror
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

        <div class="form-field is-third">
            <label class="form-label" for="gender">Gender</label>
            <select id="gender" name="gender" class="form-input @error('gender') is-invalid @enderror">
                <option value="">Select gender</option>
                @foreach(['Male', 'Female', 'Other'] as $gender)
                    <option value="{{ $gender }}"
                        {{ old('gender', $student?->gender) === $gender ? 'selected' : '' }}>{{ $gender }}</option>
                @endforeach
            </select>
            @error('gender')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-field is-third">
            <label class="form-label" for="date_of_birth">Date of Birth</label>
            <input type="date" id="date_of_birth" name="date_of_birth"
                   value="{{ old('date_of_birth', $student?->date_of_birth?->format('Y-m-d')) }}"
                   class="form-input @error('date_of_birth') is-invalid @enderror">
            @error('date_of_birth')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-field is-third">
            <label class="form-label" for="date_of_admission">Date of Admission</label>
            <input type="date" id="date_of_admission" name="date_of_admission"
                   value="{{ old('date_of_admission', $student?->date_of_admission?->format('Y-m-d')) }}"
                   class="form-input @error('date_of_admission') is-invalid @enderror">
            @error('date_of_admission')<p class="form-error">{{ $message }}</p>@enderror
        </div>
    </div>
</div>

<p class="form-card-section"><span>Family and contact</span></p>

<div class="form-card-body">
    <div class="form-grid">
        <div class="form-field is-third">
            <label class="form-label" for="father_name">Father's Name</label>
            <input type="text" id="father_name" name="father_name"
                   value="{{ old('father_name', $student?->father_name) }}"
                   class="form-input @error('father_name') is-invalid @enderror">
            @error('father_name')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-field is-third">
            <label class="form-label" for="mother_name">Mother's Name</label>
            <input type="text" id="mother_name" name="mother_name"
                   value="{{ old('mother_name', $student?->mother_name) }}"
                   class="form-input @error('mother_name') is-invalid @enderror">
            @error('mother_name')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-field is-third">
            <label class="form-label" for="guardian_name">Guardian's Name</label>
            <input type="text" id="guardian_name" name="guardian_name"
                   value="{{ old('guardian_name', $student?->guardian_name) }}"
                   placeholder="If not a parent"
                   class="form-input @error('guardian_name') is-invalid @enderror">
            @error('guardian_name')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-field is-third">
            <label class="form-label" for="guardian_phone">Guardian's Phone</label>
            <input type="text" id="guardian_phone" name="guardian_phone"
                   value="{{ old('guardian_phone', $student?->guardian_phone) }}"
                   placeholder="Who the school calls first"
                   class="form-input @error('guardian_phone') is-invalid @enderror">
            @error('guardian_phone')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-field is-third">
            <label class="form-label" for="phone">Phone</label>
            <input type="text" id="phone" name="phone" value="{{ old('phone', $student?->phone) }}"
                   placeholder="Enter phone number" class="form-input @error('phone') is-invalid @enderror">
            @error('phone')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-field is-third">
            <label class="form-label" for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email', $student?->email) }}"
                   placeholder="Enter email address" class="form-input @error('email') is-invalid @enderror">
            @error('email')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-field is-wide">
            <label class="form-label" for="address">Address</label>
            <textarea id="address" name="address" rows="2" placeholder="Enter complete address"
                      class="form-input @error('address') is-invalid @enderror">{{ old('address', $student?->address) }}</textarea>
            @error('address')<p class="form-error">{{ $message }}</p>@enderror
        </div>
    </div>
</div>

<p class="form-card-section"><span>Photo and status</span></p>

<div class="form-card-body">
    <div class="form-grid">
        <div class="form-field is-third">
            <label class="form-label" for="photo">Student Photo</label>
            @if($student?->photo)
                <div class="form-file-current">
                    <img src="{{ asset($student->photo) }}" alt="Current photo" width="40" height="40">
                    <span>Current photo. Choosing a file replaces it.</span>
                </div>
            @endif
            <input type="file" id="photo" name="photo" accept="image/*"
                   class="form-input @error('photo') is-invalid @enderror">
            <p class="form-hint">Used on the report card. JPEG or PNG, up to 2MB.</p>
            @error('photo')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-field is-third">
            <label class="form-label">Status</label>
            <label class="check-row">
                <input type="checkbox" class="check" name="is_active" value="1"
                       {{ old('is_active', $student?->is_active ?? true) ? 'checked' : '' }}>
                Currently enrolled
            </label>
            <p class="form-hint">Clear this when a student leaves; their reports are kept.</p>
        </div>
    </div>
</div>

<div class="form-card-foot">
    <a href="{{ route('students.index') }}" class="btn-ghost">Cancel</a>
    <button type="submit" class="btn-primary-flat">{{ $submitLabel }}</button>
</div>

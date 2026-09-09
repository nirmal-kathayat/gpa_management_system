{{-- Shared by schools/create and schools/edit. $school is set only when editing. --}}
@php $school = $school ?? null; @endphp

<div class="form-card-head">
    <h2 class="form-card-title">{{ $title }}</h2>
    <p class="form-card-sub">{{ $subtitle }}</p>
</div>

<div class="form-card-body">
    <div class="form-grid">
        <div class="form-field is-wide">
            <label class="form-label" for="name">School Name <span class="req">*</span></label>
            <input type="text" id="name" name="name" value="{{ old('name', $school?->name) }}" required autofocus
                   placeholder="Enter school name"
                   class="form-input @error('name') is-invalid @enderror">
            @error('name')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-field is-wide">
            <label class="form-label" for="address">Address <span class="req">*</span></label>
            <textarea id="address" name="address" rows="3" required
                      placeholder="Enter complete address"
                      class="form-input @error('address') is-invalid @enderror">{{ old('address', $school?->address) }}</textarea>
            @error('address')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-field">
            <label class="form-label" for="phone">Phone</label>
            <input type="text" id="phone" name="phone" value="{{ old('phone', $school?->phone) }}"
                   placeholder="Enter phone number"
                   class="form-input @error('phone') is-invalid @enderror">
            @error('phone')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-field">
            <label class="form-label" for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email', $school?->email) }}"
                   placeholder="Enter email address"
                   class="form-input @error('email') is-invalid @enderror">
            @error('email')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-field is-wide">
            <label class="form-label" for="logo">School Logo</label>
            @if($school?->logo)
                <div class="form-file-current">
                    <img src="{{ asset($school->logo) }}" alt="Current logo" width="40" height="40">
                    <span>Current logo. Choosing a file replaces it.</span>
                </div>
            @endif
            <input type="file" id="logo" name="logo" accept="image/*"
                   class="form-input @error('logo') is-invalid @enderror">
            <p class="form-hint">JPEG, PNG, JPG or GIF, up to 2MB.</p>
            @error('logo')<p class="form-error">{{ $message }}</p>@enderror
        </div>
    </div>
</div>

<div class="form-card-foot">
    <a href="{{ route('schools.index') }}" class="btn-ghost">Cancel</a>
    <button type="submit" class="btn-primary-flat">{{ $submitLabel }}</button>
</div>

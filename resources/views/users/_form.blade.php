{{--
    Shared by users/create and users/edit.
    $user is null when creating; $isSelf marks the row of the person signed in.
--}}
@php
    $isEdit = isset($user);
    $isSelf = $isSelf ?? false;
    $currentRole = old('role', $isEdit ? $user->role_name : null);
@endphp

<div class="panel">
    <div class="panel-head">
        <span class="panel-title">Account details</span>
    </div>
    <div class="panel-body">
        <div class="form-grid">
            <div class="form-field">
                <label class="form-label" for="name">Full name <span class="req">*</span></label>
                <input type="text" id="name" name="name" required autofocus
                       class="form-input @error('name') is-invalid @enderror"
                       value="{{ old('name', $isEdit ? $user->name : '') }}">
                @error('name')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-field">
                <label class="form-label" for="username">Username <span class="req">*</span></label>
                <input type="text" id="username" name="username" required autocomplete="off"
                       class="form-input @error('username') is-invalid @enderror"
                       value="{{ old('username', $isEdit ? $user->username : '') }}">
                @error('username')
                    <p class="form-error">{{ $message }}</p>
                @else
                    <p class="form-hint">Used to sign in. Letters, numbers, dashes and underscores.</p>
                @enderror
            </div>

            <div class="form-field">
                <label class="form-label" for="email">Email <span class="req">*</span></label>
                <input type="email" id="email" name="email" required
                       class="form-input @error('email') is-invalid @enderror"
                       value="{{ old('email', $isEdit ? $user->email : '') }}">
                @error('email')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-field">
                <label class="form-label" for="phone">Phone</label>
                <input type="text" id="phone" name="phone"
                       class="form-input @error('phone') is-invalid @enderror"
                       value="{{ old('phone', $isEdit ? $user->phone : '') }}">
                @error('phone')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-field is-wide">
                <label class="form-label" for="address">Address</label>
                <textarea id="address" name="address" rows="2"
                          class="form-input @error('address') is-invalid @enderror">{{ old('address', $isEdit ? $user->address : '') }}</textarea>
                @error('address')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <p class="form-section-title">{{ $isEdit ? 'Change password' : 'Password' }}</p>

            <div class="form-field">
                <label class="form-label" for="password">
                    Password @unless($isEdit)<span class="req">*</span>@endunless
                </label>
                <input type="password" id="password" name="password" autocomplete="new-password"
                       class="form-input @error('password') is-invalid @enderror" @unless($isEdit) required @endunless>
                @error('password')
                    <p class="form-error">{{ $message }}</p>
                @else
                    <p class="form-hint">
                        {{ $isEdit ? 'Leave blank to keep the current password.' : 'At least 8 characters.' }}
                    </p>
                @enderror
            </div>

            <div class="form-field">
                <label class="form-label" for="password_confirmation">Confirm password</label>
                <input type="password" id="password_confirmation" name="password_confirmation"
                       autocomplete="new-password" class="form-input" @unless($isEdit) required @endunless>
            </div>
        </div>
    </div>
</div>

<div class="panel">
    <div class="panel-head">
        <span class="panel-title">Access</span>
    </div>
    <div class="panel-body">
        <div class="form-grid">
            <div class="form-field">
                <label class="form-label" for="role">Role <span class="req">*</span></label>
                <select id="role" name="role" required
                        class="form-input @error('role') is-invalid @enderror" @if($isSelf) disabled @endif>
                    <option value="">Select a role</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}" {{ $currentRole === $role->name ? 'selected' : '' }}>
                            {{ \Illuminate\Support\Str::headline($role->name) }}
                        </option>
                    @endforeach
                </select>
                @if($isSelf)
                    {{-- A disabled select submits nothing, and the controller ignores this value anyway. --}}
                    <input type="hidden" name="role" value="{{ $currentRole }}">
                @endif
                @error('role')
                    <p class="form-error">{{ $message }}</p>
                @else
                    <p class="form-hint">
                        @if($isSelf)
                            You cannot change your own role.
                        @else
                            Decides what this person can reach.
                            <a href="{{ route('roles.index') }}">Manage roles</a>
                        @endif
                    </p>
                @enderror
            </div>

            <div class="form-field">
                <label class="form-label" for="school_id">School</label>
                <select id="school_id" name="school_id" class="form-input @error('school_id') is-invalid @enderror">
                    <option value="">All schools (administrator)</option>
                    @foreach($schools as $school)
                        <option value="{{ $school->id }}"
                            {{ (string) old('school_id', $isEdit ? $user->school_id : '') === (string) $school->id ? 'selected' : '' }}>
                            {{ $school->name }}
                        </option>
                    @endforeach
                </select>
                @error('school_id')
                    <p class="form-error">{{ $message }}</p>
                @else
                    <p class="form-hint">Non-admins only see records belonging to their school.</p>
                @enderror
            </div>

            <div class="form-field is-wide">
                <label class="check-row">
                    <input type="checkbox" class="check" name="is_active" value="1"
                           {{ old('is_active', $isEdit ? $user->is_active : true) ? 'checked' : '' }}
                           @if($isSelf) disabled @endif>
                    Account is active
                </label>
                <p class="form-hint">
                    {{ $isSelf ? 'You cannot deactivate your own account.' : 'Inactive accounts are signed out and cannot log in.' }}
                </p>
            </div>
        </div>
    </div>
</div>

<div class="btn-row">
    <button type="submit" class="btn-primary-flat">
        <i class="fas fa-check"></i>{{ $submitLabel }}
    </button>
    <a href="{{ route('users.index') }}" class="btn-ghost">Cancel</a>
</div>

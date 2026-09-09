@extends('layouts.app')

@section('title', 'My Profile - GPA Management System')

@section('content')
<form class="form-card" action="{{ route('users.profile.update') }}" method="POST">
    @csrf
    @method('PUT')

    <div class="form-card-head">
        <h2 class="form-card-title">My Profile</h2>
        <p class="form-card-sub">Update your own details and password.</p>
    </div>

    <div class="form-card-body">
        <div class="form-grid">
            <div class="form-field">
                <label class="form-label" for="name">Full Name <span class="req">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                       class="form-input @error('name') is-invalid @enderror">
                @error('name')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-field">
                <label class="form-label" for="email">Email Address <span class="req">*</span></label>
                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                       class="form-input @error('email') is-invalid @enderror">
                @error('email')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-field">
                <label class="form-label" for="phone">Phone</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone', $user->phone) }}"
                       class="form-input @error('phone') is-invalid @enderror">
                @error('phone')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-field">
                <label class="form-label" for="address">Address</label>
                <input type="text" id="address" name="address" value="{{ old('address', $user->address) }}"
                       class="form-input @error('address') is-invalid @enderror">
                @error('address')<p class="form-error">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    <p class="form-card-section"><span>Change password</span></p>

    <div class="form-card-body">
        <div class="form-grid">
            <div class="form-field is-third">
                <label class="form-label" for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" autocomplete="current-password"
                       class="form-input @error('current_password') is-invalid @enderror">
                @error('current_password')
                    <p class="form-error">{{ $message }}</p>
                @else
                    <p class="form-hint">Only needed if you are setting a new one.</p>
                @enderror
            </div>

            <div class="form-field is-third">
                <label class="form-label" for="password">New Password</label>
                <input type="password" id="password" name="password" autocomplete="new-password"
                       class="form-input @error('password') is-invalid @enderror">
                @error('password')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-field is-third">
                <label class="form-label" for="password_confirmation">Confirm New Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation"
                       autocomplete="new-password" class="form-input">
            </div>
        </div>
    </div>

    <div class="form-card-foot">
        <a href="{{ route('dashboard') }}" class="btn-ghost">Back to Dashboard</a>
        <button type="submit" class="btn-primary-flat">Update Profile</button>
    </div>
</form>

<div class="form-card">
    <div class="form-card-head">
        <h2 class="form-card-title">Account</h2>
        <p class="form-card-sub">Set by an administrator; you cannot change these yourself.</p>
    </div>

    <div class="form-card-body">
        <div class="detail-grid">
            <div class="detail-item">
                <div class="detail-label">Role</div>
                <div class="detail-value">{{ \Illuminate\Support\Str::headline($user->role_name ?? 'No role') }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">School</div>
                <div class="detail-value">{{ $user->school->name ?? 'All schools' }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Status</div>
                <div class="detail-value">
                    <span class="pill {{ $user->is_active ? 'pill-positive' : 'pill-danger' }}">
                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Member Since</div>
                <div class="detail-value">{{ $user->created_at->format('d M Y') }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Last Signed In</div>
                <div class="detail-value">{{ $user->last_login_at?->format('d M Y, g:i a') ?: 'Never' }}</div>
            </div>
        </div>
    </div>
</div>
@endsection

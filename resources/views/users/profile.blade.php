@extends('layouts.app')

@section('title', 'My Profile - GPA Management System')
@section('page-title', 'My Profile')

@php
    $initials = \Illuminate\Support\Str::of($user->name)->explode(' ')->take(2)
        ->map(fn ($word) => \Illuminate\Support\Str::substr($word, 0, 1))->implode('');

    $contacts = [
        ['fa-envelope', 'Email', $user->email],
        ['fa-phone', 'Phone', $user->phone],
        ['fa-location-dot', 'Address', $user->address],
    ];

    $account = [
        ['fa-user-shield', 'Role', \Illuminate\Support\Str::headline($user->role_name ?? 'No role')],
        ['fa-building-columns', 'School', $user->school->name ?? 'All schools'],
        ['fa-circle', 'Status', null],
        ['fa-calendar-days', 'Member Since', $user->created_at->format('d M Y')],
        ['fa-clock', 'Last Signed In', $user->last_login_at?->format('d M Y, g:i A') ?: 'Never'],
    ];
@endphp

@section('content')
<div class="detail-head">
    <div>
        <h2 class="detail-title">My Profile</h2>
        <p class="detail-sub">Manage your personal information and account settings.</p>
    </div>
    <nav class="crumbs is-trail" aria-label="Breadcrumb">
        <a href="{{ route('dashboard') }}" aria-label="Dashboard"><i class="fas fa-house"></i></a>
        <span class="crumbs-slash">/</span>
        <span>My Profile</span>
    </nav>
</div>

<div class="panel profile-hero">
    <div class="profile-mark">{{ $initials }}</div>

    <div class="profile-identity">
        <h3 class="profile-name">{{ $user->name }}</h3>
        <p class="profile-role">{{ \Illuminate\Support\Str::headline($user->role_name ?? 'No role') }}</p>
        <span class="pill is-dotted {{ $user->is_active ? 'pill-positive' : 'pill-danger' }}">
            <span class="pill-dot"></span>{{ $user->is_active ? 'Active' : 'Inactive' }}
        </span>
    </div>

    <div class="profile-contacts">
        @foreach($contacts as [$icon, $label, $value])
            <div class="profile-contact">
                <span class="profile-contact-icon"><i class="fas {{ $icon }}"></i></span>
                <div>
                    <div class="profile-contact-value">{{ $value ?: '—' }}</div>
                    <div class="profile-contact-label">{{ $label }}</div>
                </div>
            </div>
        @endforeach
    </div>
</div>

{{-- One form over two cards: the password is optional, so it posts with the
     rest of the details rather than on its own. --}}
<form class="profile-form" action="{{ route('users.profile.update') }}" method="POST">
    @csrf
    @method('PUT')

    <div class="form-card is-full">
        <div class="form-card-head is-iconed">
            <span class="card-icon"><i class="fas fa-user"></i></span>
            <div>
                <h2 class="form-card-title">Personal Information</h2>
                <p class="form-card-sub">Update your basic information. Keep your details up to date.</p>
            </div>
        </div>

        <div class="form-card-body">
            <div class="form-grid">
                <div class="form-field">
                    <label class="form-label" for="name">Full Name <span class="req">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                           placeholder="Enter your full name"
                           class="form-input @error('name') is-invalid @enderror">
                    @error('name')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label" for="email">Email Address <span class="req">*</span></label>
                    <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                           placeholder="Enter your email address"
                           class="form-input @error('email') is-invalid @enderror">
                    @error('email')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label" for="phone">Phone</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone', $user->phone) }}"
                           placeholder="Enter your phone number"
                           class="form-input @error('phone') is-invalid @enderror">
                    @error('phone')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label" for="address">Address</label>
                    <input type="text" id="address" name="address" value="{{ old('address', $user->address) }}"
                           placeholder="Enter your address"
                           class="form-input @error('address') is-invalid @enderror">
                    @error('address')<p class="form-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>
    </div>

    <div class="form-card is-full">
        <div class="form-card-head is-iconed">
            <span class="card-icon"><i class="fas fa-lock"></i></span>
            <div>
                <h2 class="form-card-title">Change Password</h2>
                <p class="form-card-sub">Set a new password only if you want to change it.</p>
            </div>
        </div>

        <div class="form-card-body">
            <div class="form-grid">
                @php
                    $passwords = [
                        ['current_password', 'Current Password', 'Enter current password', 'current-password'],
                        ['password', 'New Password', 'Enter new password', 'new-password'],
                        ['password_confirmation', 'Confirm New Password', 'Confirm new password', 'new-password'],
                    ];
                @endphp

                @foreach($passwords as [$field, $label, $placeholder, $autocomplete])
                    <div class="form-field is-third">
                        <label class="form-label" for="{{ $field }}">{{ $label }}</label>
                        <div class="input-affix">
                            <input type="password" id="{{ $field }}" name="{{ $field }}"
                                   autocomplete="{{ $autocomplete }}" placeholder="{{ $placeholder }}"
                                   class="form-input @error($field) is-invalid @enderror">
                            <button type="button" class="input-eye" data-reveal="{{ $field }}"
                                    aria-label="Show password">
                                <i class="fas fa-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                        @error($field)<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div>

            <p class="form-note">
                <i class="fas fa-circle-info" aria-hidden="true"></i>
                Use a strong password with at least 8 characters. Leave these blank to keep your current one.
            </p>
        </div>

        <div class="form-card-foot">
            <a href="{{ route('dashboard') }}" class="btn-ghost">Back to Dashboard</a>
            <button type="submit" class="btn-primary-flat">
                <i class="fas fa-floppy-disk" aria-hidden="true"></i>Update Profile
            </button>
        </div>
    </div>
</form>

<div class="form-card is-full">
    <div class="form-card-head is-iconed">
        <span class="card-icon"><i class="fas fa-gear"></i></span>
        <div>
            <h2 class="form-card-title">Account Information</h2>
            <p class="form-card-sub">Some account details are set by the administrator and cannot be changed here.</p>
        </div>
    </div>

    <div class="form-card-body">
        <div class="account-grid">
            @foreach($account as [$icon, $label, $value])
                <div class="account-item">
                    <span class="account-icon {{ $label === 'Status' ? 'is-status' : '' }}">
                        <i class="fas {{ $icon }}" aria-hidden="true"></i>
                    </span>
                    <div>
                        <div class="account-label">{{ $label }}</div>
                        <div class="account-value">
                            @if($label === 'Status')
                                <span class="pill {{ $user->is_active ? 'pill-positive' : 'pill-danger' }}">
                                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            @else
                                {{ $value }}
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Each password box can be revealed on its own.
    document.querySelectorAll('.input-eye').forEach(function (button) {
        button.addEventListener('click', function () {
            const input = document.getElementById(button.dataset.reveal);
            const shown = input.type === 'text';

            input.type = shown ? 'password' : 'text';
            button.querySelector('i').className = shown ? 'fas fa-eye' : 'fas fa-eye-slash';
            button.setAttribute('aria-label', shown ? 'Show password' : 'Hide password');
            input.focus();
        });
    });
</script>
@endpush

{{--
    The toast stack, plus any flash message the last request left behind.
    public/assets/js/toast.js gives each one its close button and timer, and can
    add more at runtime with toast.success('...').
--}}
@php
    $toastIcons = [
        'success' => 'fa-circle-check',
        'error' => 'fa-circle-exclamation',
        'warning' => 'fa-triangle-exclamation',
        'info' => 'fa-circle-info',
    ];
@endphp

<div class="toast-stack no-print" id="toastStack" aria-live="polite">
    @foreach($toastIcons as $type => $icon)
        @if(session($type))
            <div class="toast-item toast-{{ $type }}" role="{{ $type === 'error' ? 'alert' : 'status' }}">
                <i class="fas {{ $icon }} toast-icon" aria-hidden="true"></i>
                <span class="toast-text">{{ session($type) }}</span>
                <button type="button" class="toast-close" aria-label="Dismiss">
                    <i class="fas fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
        @endif
    @endforeach
</div>

{{--
    Shared by roles/create and roles/edit.
    $groups/$abilities come from App\Support\Permissions, $granted is the list of
    permission names that should start ticked.
--}}
@php
    $allNames = collect($groups)->flatMap(fn ($modules) => collect($modules)->flatMap(
        fn ($module, $name) => collect($module['abilities'])->map(fn ($a) => "{$name}.{$a}")
    ))->values();
@endphp

<div class="form-card-head">
    <h2 class="form-card-title">{{ $title }}</h2>
    <p class="form-card-sub">{{ $subtitle }}</p>
</div>

<div class="form-card-body">
    <div class="form-grid">
            <div class="form-field">
                <label class="form-label" for="name">Role name <span class="req">*</span></label>
                <input type="text" id="name" name="name" class="form-input @error('name') is-invalid @enderror"
                       value="{{ old('name', isset($role) ? \Illuminate\Support\Str::headline($role->name) : '') }}"
                       placeholder="e.g. Head Teacher" required autofocus>
                @error('name')
                    <p class="form-error">{{ $message }}</p>
                @else
                    <p class="form-hint">Saved in lower case with hyphens, shown as typed.</p>
                @enderror
            </div>
    </div>
</div>

<div class="form-card-section">
    <span>Permissions</span>
    <label class="check-row">
        <input type="checkbox" class="check" id="permAll">
        Select everything
    </label>
</div>

<div class="form-card-body">
        <div class="perm-summary">
            <span class="perm-summary-text">
                <strong id="permCount">{{ count($granted) }}</strong> of {{ $allNames->count() }} permissions selected
            </span>
            <span class="perm-summary-text">Tick a module heading to select all of its actions.</span>
        </div>

        @foreach($groups as $groupName => $modules)
            <div class="perm-group">
                <h3 class="perm-group-title">{{ $groupName }}</h3>
                <div class="perm-grid">
                    @foreach($modules as $moduleName => $module)
                        <div class="perm-card">
                            <div class="perm-card-head">
                                <span class="perm-card-icon"><i class="fas {{ $module['icon'] }}"></i></span>
                                <span class="perm-card-name">{{ $module['label'] }}</span>
                                <span class="perm-card-count">0/{{ count($module['abilities']) }}</span>
                                <input type="checkbox" class="check perm-card-toggle"
                                       aria-label="Select all {{ $module['label'] }} permissions">
                            </div>
                            <div class="perm-card-body">
                                @foreach($module['abilities'] as $ability)
                                    @php $permission = "{$moduleName}.{$ability}"; @endphp
                                    <label class="perm-check" for="perm-{{ $moduleName }}-{{ $ability }}">
                                        <input type="checkbox" class="check perm-box"
                                               id="perm-{{ $moduleName }}-{{ $ability }}"
                                               name="permissions[]" value="{{ $permission }}"
                                               {{ in_array($permission, $granted, true) ? 'checked' : '' }}>
                                        {{ $abilities[$ability] }}
                                        <span class="perm-code">{{ $permission }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

    @error('permissions.*')
        <p class="form-error">{{ $message }}</p>
    @enderror
</div>

<div class="form-card-foot">
    <a href="{{ route('roles.index') }}" class="btn-ghost">Cancel</a>
    <button type="submit" class="btn-primary-flat">{{ $submitLabel }}</button>
</div>

@push('scripts')
<script>
    (function () {
        const boxes = Array.from(document.querySelectorAll('.perm-box'));
        const master = document.getElementById('permAll');
        const counter = document.getElementById('permCount');

        // Keeps a card's own tick, its "n/m" label and the page counter honest
        // whichever of the three checkboxes was clicked.
        const cards = Array.from(document.querySelectorAll('.perm-card')).map(function (card) {
            const state = {
                toggle: card.querySelector('.perm-card-toggle'),
                count: card.querySelector('.perm-card-count'),
                boxes: Array.from(card.querySelectorAll('.perm-box')),
            };

            state.sync = function () {
                const on = state.boxes.filter(b => b.checked).length;
                state.toggle.checked = on === state.boxes.length;
                state.toggle.indeterminate = on > 0 && on < state.boxes.length;
                state.count.textContent = on + '/' + state.boxes.length;
            };

            state.toggle.addEventListener('change', function () {
                state.boxes.forEach(b => { b.checked = state.toggle.checked; });
                syncAll();
            });

            state.boxes.forEach(b => b.addEventListener('change', syncAll));

            return state;
        });

        function syncAll() {
            cards.forEach(c => c.sync());
            const on = boxes.filter(b => b.checked).length;
            counter.textContent = on;
            master.checked = on === boxes.length;
            master.indeterminate = on > 0 && on < boxes.length;
        }

        master.addEventListener('change', function () {
            boxes.forEach(b => { b.checked = master.checked; });
            syncAll();
        });

        syncAll();
    })();
</script>
@endpush

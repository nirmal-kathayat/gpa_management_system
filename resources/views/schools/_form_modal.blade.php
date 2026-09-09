{{--
    The one Add / Edit School form, in a Bootstrap modal.

    Create posts to schools.store. Edit switches the same form over from the grid
    row - the row already carries every field, so no extra request is needed - and
    adds the _method=PUT and the id to the action.
--}}
@php
    // A failed submit comes back here, and /schools/{id}/edit redirects here, so
    // work out which school - if any - the form should open on.
    $editing = old('school_id')
        ? \App\Models\School::find(old('school_id'))
        : (request()->filled('edit') ? \App\Models\School::find(request('edit')) : null);

    $reopen = $errors->any() || request()->boolean('add') || $editing;
@endphp

<div class="modal fade form-modal" id="schoolFormModal" tabindex="-1"
     aria-labelledby="schoolFormTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form class="form-card" id="schoolForm" method="POST"
                  action="{{ $editing ? route('schools.update', $editing) : route('schools.store') }}"
                  enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_method" id="schoolFormMethod" value="{{ $editing ? 'PUT' : 'POST' }}">
                <input type="hidden" name="school_id" id="schoolFormId" value="{{ $editing?->id }}">

                <div class="form-card-head">
                    <h2 class="form-card-title" id="schoolFormTitle">
                        {{ $editing ? 'Edit ' . $editing->name : 'Add New School' }}
                    </h2>
                    <p class="form-card-sub" id="schoolFormSubtitle">
                        {{ $editing ? 'Update the details of this school.' : 'Fill in the details below to create a new school.' }}
                    </p>
                    <button type="button" class="form-card-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fas fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="form-card-body">
                    <div class="form-grid">
                        <div class="form-field is-wide">
                            <label class="form-label" for="school_name">School Name <span class="req">*</span></label>
                            <input type="text" id="school_name" name="name" value="{{ old('name', $editing?->name) }}" required
                                   placeholder="Enter school name"
                                   class="form-input @error('name') is-invalid @enderror">
                            @error('name')<p class="form-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-field">
                            <label class="form-label" for="school_code">School Code</label>
                            <input type="text" id="school_code" name="code" value="{{ old('code', $editing?->code) }}"
                                   placeholder="e.g. JSS001"
                                   class="form-input @error('code') is-invalid @enderror">
                            @error('code')<p class="form-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-field">
                            <label class="form-label" for="school_tagline">Tagline</label>
                            <input type="text" id="school_tagline" name="tagline" value="{{ old('tagline', $editing?->tagline) }}"
                                   placeholder="e.g. Quality Education for a Better Tomorrow"
                                   class="form-input @error('tagline') is-invalid @enderror">
                            @error('tagline')<p class="form-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-field">
                            <label class="form-label" for="school_established">Established</label>
                            <input type="text" id="school_established" name="established"
                                   value="{{ old('established', $editing?->established) }}" placeholder="e.g. 2008 B.S."
                                   class="form-input @error('established') is-invalid @enderror">
                            @error('established')<p class="form-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-field">
                            <label class="form-label" for="school_type">School Type</label>
                            <input type="text" id="school_type" name="type" value="{{ old('type', $editing?->type) }}"
                                   placeholder="e.g. Community"
                                   class="form-input @error('type') is-invalid @enderror">
                            @error('type')<p class="form-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-field is-wide">
                            <label class="form-label" for="school_address">Address <span class="req">*</span></label>
                            <textarea id="school_address" name="address" rows="3" required
                                      placeholder="Enter complete address"
                                      class="form-input @error('address') is-invalid @enderror">{{ old('address', $editing?->address) }}</textarea>
                            @error('address')<p class="form-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-field">
                            <label class="form-label" for="school_phone">Phone</label>
                            <input type="text" id="school_phone" name="phone" value="{{ old('phone', $editing?->phone) }}"
                                   placeholder="Enter phone number"
                                   class="form-input @error('phone') is-invalid @enderror">
                            @error('phone')<p class="form-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-field">
                            <label class="form-label" for="school_email">Email</label>
                            <input type="email" id="school_email" name="email" value="{{ old('email', $editing?->email) }}"
                                   placeholder="Enter email address"
                                   class="form-input @error('email') is-invalid @enderror">
                            @error('email')<p class="form-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-field is-wide">
                            <label class="form-label" for="school_about">About</label>
                            <textarea id="school_about" name="about" rows="3"
                                      placeholder="A short description shown on the school's page"
                                      class="form-input @error('about') is-invalid @enderror">{{ old('about', $editing?->about) }}</textarea>
                            @error('about')<p class="form-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-field is-wide">
                            <label class="form-label" for="school_logo">School Logo</label>
                            <div class="form-file-current" id="schoolFormLogo" {{ $editing?->logo ? '' : 'hidden' }}>
                                <img src="{{ $editing?->logo ? asset($editing->logo) : '' }}" alt="Current logo" width="40" height="40">
                                <span>Current logo. Choosing a file replaces it.</span>
                            </div>
                            <input type="file" id="school_logo" name="logo" accept="image/*"
                                   class="form-input @error('logo') is-invalid @enderror">
                            <p class="form-hint">JPEG, PNG, JPG or GIF, up to 2MB.</p>
                            @error('logo')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                <div class="form-card-foot">
                    <button type="button" class="btn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-primary-flat" id="schoolFormSubmit">
                        {{ $editing ? 'Update School' : 'Create School' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        const modal = new bootstrap.Modal(document.getElementById('schoolFormModal'));
        const form = document.getElementById('schoolForm');
        const logo = document.getElementById('schoolFormLogo');

        // row is null when adding.
        function show(row) {
            const editing = Boolean(row);

            form.action = editing ? '/schools/' + row.id : '{{ route('schools.store') }}';
            document.getElementById('schoolFormMethod').value = editing ? 'PUT' : 'POST';
            document.getElementById('schoolFormId').value = editing ? row.id : '';
            document.getElementById('schoolFormTitle').textContent = editing ? 'Edit ' + row.name : 'Add New School';
            document.getElementById('schoolFormSubtitle').textContent = editing
                ? 'Update the details of this school.'
                : 'Fill in the details below to create a new school.';
            document.getElementById('schoolFormSubmit').textContent = editing ? 'Update School' : 'Create School';

            const value = (key) => (editing && row[key] && row[key] !== '—' ? row[key] : '');
            ['name', 'code', 'tagline', 'established', 'type', 'about', 'address', 'phone', 'email']
                .forEach((key) => { document.getElementById('school_' + key).value = value(key); });
            document.getElementById('school_logo').value = '';

            logo.hidden = !(editing && row.logo);
            if (editing && row.logo) logo.querySelector('img').src = '/' + row.logo;

            // Clear anything left over from the last time it was open.
            form.querySelectorAll('.form-input.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            form.querySelectorAll('.form-error.is-client').forEach(el => el.remove());

            modal.show();
        }

        document.addEventListener('click', function (event) {
            if (event.target.closest('#addSchoolBtn')) {
                event.preventDefault();
                show(null);
                return;
            }

            // The grid's edit action carries the whole row, so no fetch is needed.
            const edit = event.target.closest('#schools-grid button[data-action-type="edit"]');
            if (edit) {
                event.preventDefault();
                event.stopImmediatePropagation();
                show(JSON.parse(edit.dataset.rowData));
            }
        }, true);

        @if($reopen)
            modal.show();
        @endif
    })();
</script>
@endpush

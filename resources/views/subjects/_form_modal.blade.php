{{--
    The one Add / Edit Subject form, in a Bootstrap modal.

    Create posts to subjects.store. Edit switches the same form over from the
    grid row - the row already carries every field, so no extra request is
    needed - and adds the _method=PUT and the id to the action.
--}}
@php
    // A failed submit comes back here, and /subjects/{id}/edit redirects here,
    // so work out which subject - if any - the form should open on.
    $editing = old('subject_id')
        ? \App\Models\Subject::find(old('subject_id'))
        : (request()->filled('edit') ? \App\Models\Subject::find(request('edit')) : null);

    $reopen = $errors->any() || request()->boolean('add') || $editing;
@endphp

<div class="modal fade form-modal" id="subjectFormModal" tabindex="-1"
     aria-labelledby="subjectFormTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form class="form-card" id="subjectForm" method="POST"
                  action="{{ $editing ? route('subjects.update', $editing) : route('subjects.store') }}">
                @csrf
                <input type="hidden" name="_method" id="subjectFormMethod" value="{{ $editing ? 'PUT' : 'POST' }}">
                <input type="hidden" name="subject_id" id="subjectFormId" value="{{ $editing?->id }}">

                <div class="form-card-head">
                    <h2 class="form-card-title" id="subjectFormTitle">
                        {{ $editing ? 'Edit ' . $editing->name : 'Add New Subject' }}
                    </h2>
                    <p class="form-card-sub" id="subjectFormSubtitle">
                        {{ $editing ? 'Update the details of this subject.' : 'Fill in the details below to create a new subject.' }}
                    </p>
                    <button type="button" class="form-card-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fas fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="form-card-body">
                    <div class="form-grid">
                        <div class="form-field">
                            <label class="form-label" for="subject_name">Subject Name <span class="req">*</span></label>
                            <input type="text" id="subject_name" name="name" required
                                   value="{{ old('name', $editing?->name) }}" placeholder="e.g. English"
                                   class="form-input @error('name') is-invalid @enderror">
                            @error('name')<p class="form-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-field">
                            <label class="form-label" for="subject_code">Subject Code <span class="req">*</span></label>
                            <input type="text" id="subject_code" name="code" required
                                   value="{{ old('code', $editing?->code) }}" placeholder="e.g. ENG1"
                                   class="form-input @error('code') is-invalid @enderror">
                            @error('code')
                                <p class="form-error">{{ $message }}</p>
                            @else
                                <p class="form-hint">A unique short identifier for this subject.</p>
                            @enderror
                        </div>

                        <div class="form-field">
                            <label class="form-label" for="subject_full_marks">Full Marks <span class="req">*</span></label>
                            <input type="number" id="subject_full_marks" name="full_marks" min="1" max="200" required
                                   value="{{ old('full_marks', $editing?->full_marks ?? 100) }}"
                                   class="form-input @error('full_marks') is-invalid @enderror">
                            @error('full_marks')<p class="form-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-field">
                            <label class="form-label" for="subject_pass_marks">Pass Marks <span class="req">*</span></label>
                            <input type="number" id="subject_pass_marks" name="pass_marks" min="1" max="100" required
                                   value="{{ old('pass_marks', $editing?->pass_marks ?? 32) }}"
                                   class="form-input @error('pass_marks') is-invalid @enderror">
                            @error('pass_marks')<p class="form-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-field is-wide">
                            <label class="check-row">
                                <input type="checkbox" class="check" id="subject_is_active" name="is_active" value="1"
                                       {{ old('is_active', $editing?->is_active ?? true) ? 'checked' : '' }}>
                                Active subject
                            </label>
                            <p class="form-hint">
                                Inactive subjects stay on existing report cards but cannot be added to new ones.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="form-card-foot">
                    <button type="button" class="btn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-primary-flat" id="subjectFormSubmit">
                        {{ $editing ? 'Update Subject' : 'Create Subject' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        const modal = new bootstrap.Modal(document.getElementById('subjectFormModal'));
        const form = document.getElementById('subjectForm');

        // row is null when adding.
        function show(row) {
            const editing = Boolean(row);

            form.action = editing ? '/subjects/' + row.id : '{{ route('subjects.store') }}';
            document.getElementById('subjectFormMethod').value = editing ? 'PUT' : 'POST';
            document.getElementById('subjectFormId').value = editing ? row.id : '';
            document.getElementById('subjectFormTitle').textContent = editing ? 'Edit ' + row.name : 'Add New Subject';
            document.getElementById('subjectFormSubtitle').textContent = editing
                ? 'Update the details of this subject.'
                : 'Fill in the details below to create a new subject.';
            document.getElementById('subjectFormSubmit').textContent = editing ? 'Update Subject' : 'Create Subject';

            document.getElementById('subject_name').value = editing ? row.name : '';
            document.getElementById('subject_code').value = editing ? row.code : '';
            document.getElementById('subject_full_marks').value = editing ? row.full_marks : 100;
            document.getElementById('subject_pass_marks').value = editing ? row.pass_marks : 32;
            document.getElementById('subject_is_active').checked = editing ? Boolean(row.is_active) : true;

            // Clear anything left over from the last time it was open.
            form.querySelectorAll('.form-input.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            form.querySelectorAll('.form-error.is-client').forEach(el => el.remove());

            modal.show();
        }

        document.addEventListener('click', function (event) {
            if (event.target.closest('#addSubjectBtn')) {
                event.preventDefault();
                show(null);
                return;
            }

            // The grid's edit action carries the whole row, so no fetch is needed.
            const edit = event.target.closest('#subjects-grid button[data-action-type="edit"]');
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

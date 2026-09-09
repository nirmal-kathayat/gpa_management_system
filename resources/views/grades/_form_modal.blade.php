{{--
    The one Add / Edit Grade form, in a Bootstrap modal.

    Edit switches the same form over from the grid row, which already carries
    every field, so opening it costs no extra request.
--}}
@php
    $editing = old('grade_id')
        ? \App\Models\GradeSystem::find(old('grade_id'))
        : (request()->filled('edit') ? \App\Models\GradeSystem::find(request('edit')) : null);

    $reopen = $errors->any() || request()->boolean('add') || $editing;
@endphp

<div class="modal fade form-modal" id="gradeFormModal" tabindex="-1"
     aria-labelledby="gradeFormTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form class="form-card" id="gradeForm" method="POST"
                  action="{{ $editing ? route('grades.update', $editing) : route('grades.store') }}">
                @csrf
                <input type="hidden" name="_method" id="gradeFormMethod" value="{{ $editing ? 'PUT' : 'POST' }}">
                <input type="hidden" name="grade_id" id="gradeFormId" value="{{ $editing?->id }}">

                <div class="form-card-head">
                    <h2 class="form-card-title" id="gradeFormTitle">
                        {{ $editing ? 'Edit grade '.$editing->letter_grade : 'Add New Grade' }}
                    </h2>
                    <p class="form-card-sub" id="gradeFormSubtitle">
                        {{ $editing
                            ? 'Update this band of the grading scale.'
                            : 'Define a letter grade and the percentage range it covers.' }}
                    </p>
                    <button type="button" class="form-card-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fas fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="form-card-body">
                    <div class="form-grid">
                        <div class="form-field">
                            <label class="form-label" for="grade_letter">Letter Grade <span class="req">*</span></label>
                            <input type="text" id="grade_letter" name="letter_grade" required maxlength="5"
                                   value="{{ old('letter_grade', $editing?->letter_grade) }}" placeholder="e.g. A+"
                                   class="form-input @error('letter_grade') is-invalid @enderror">
                            @error('letter_grade')<p class="form-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-field">
                            <label class="form-label" for="grade_point">Grade Point <span class="req">*</span></label>
                            <input type="number" id="grade_point" name="grade_point" min="0" max="4" step="0.1" required
                                   value="{{ old('grade_point', $editing?->grade_point) }}" placeholder="e.g. 4.0"
                                   class="form-input @error('grade_point') is-invalid @enderror">
                            @error('grade_point')
                                <p class="form-error">{{ $message }}</p>
                            @else
                                <p class="form-hint">On the 4.0 scale.</p>
                            @enderror
                        </div>

                        <div class="form-field">
                            <label class="form-label" for="grade_marks_from">Percentage From <span class="req">*</span></label>
                            <input type="number" id="grade_marks_from" name="marks_from" min="0" max="100" required
                                   value="{{ old('marks_from', $editing?->marks_from) }}"
                                   class="form-input @error('marks_from') is-invalid @enderror">
                            @error('marks_from')<p class="form-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-field">
                            <label class="form-label" for="grade_marks_to">Percentage To <span class="req">*</span></label>
                            <input type="number" id="grade_marks_to" name="marks_to" min="0" max="100" required
                                   value="{{ old('marks_to', $editing?->marks_to) }}"
                                   class="form-input @error('marks_to') is-invalid @enderror">
                            @error('marks_to')
                                <p class="form-error">{{ $message }}</p>
                            @else
                                <p class="form-hint">Marks are converted to a percentage of each subject's full marks.</p>
                            @enderror
                        </div>

                        <div class="form-field is-wide">
                            <label class="form-label" for="grade_description">Description</label>
                            <input type="text" id="grade_description" name="description"
                                   value="{{ old('description', $editing?->description) }}"
                                   placeholder="e.g. Outstanding"
                                   class="form-input @error('description') is-invalid @enderror">
                            @error('description')<p class="form-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-field is-wide">
                            <label class="check-row">
                                <input type="checkbox" class="check" id="grade_is_failing" name="is_failing" value="1"
                                       {{ old('is_failing', $editing?->is_failing ?? false) ? 'checked' : '' }}>
                                This grade is a failure
                            </label>
                            <p class="form-hint">
                                A subject in this band does not count toward the GPA, and the report card is marked failed.
                            </p>
                        </div>

                        <div class="form-field is-wide">
                            <label class="check-row">
                                <input type="checkbox" class="check" id="grade_is_active" name="is_active" value="1"
                                       {{ old('is_active', $editing?->is_active ?? true) ? 'checked' : '' }}>
                                Active
                            </label>
                            <p class="form-hint">Inactive bands are ignored when grading, but stay on past report cards.</p>
                        </div>
                    </div>
                </div>

                <div class="form-card-foot">
                    <button type="button" class="btn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-primary-flat" id="gradeFormSubmit">
                        {{ $editing ? 'Update Grade' : 'Create Grade' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        const modal = new bootstrap.Modal(document.getElementById('gradeFormModal'));
        const form = document.getElementById('gradeForm');

        // row is null when adding.
        function show(row) {
            const editing = Boolean(row);

            form.action = editing ? '/grades/' + row.id : '{{ route('grades.store') }}';
            document.getElementById('gradeFormMethod').value = editing ? 'PUT' : 'POST';
            document.getElementById('gradeFormId').value = editing ? row.id : '';
            document.getElementById('gradeFormTitle').textContent =
                editing ? 'Edit grade ' + row.letter_grade : 'Add New Grade';
            document.getElementById('gradeFormSubtitle').textContent = editing
                ? 'Update this band of the grading scale.'
                : 'Define a letter grade and the percentage range it covers.';
            document.getElementById('gradeFormSubmit').textContent = editing ? 'Update Grade' : 'Create Grade';

            document.getElementById('grade_letter').value = editing ? row.letter_grade : '';
            document.getElementById('grade_point').value = editing ? row.grade_point : '';
            document.getElementById('grade_marks_from').value = editing ? row.marks_from : '';
            document.getElementById('grade_marks_to').value = editing ? row.marks_to : '';
            document.getElementById('grade_description').value = editing ? (row.description ?? '') : '';
            document.getElementById('grade_is_failing').checked = editing ? Boolean(row.is_failing) : false;
            document.getElementById('grade_is_active').checked = editing ? Boolean(row.is_active) : true;

            form.querySelectorAll('.form-input.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            form.querySelectorAll('.form-error.is-client').forEach(el => el.remove());

            modal.show();
        }

        document.addEventListener('click', function (event) {
            if (event.target.closest('#addGradeBtn')) {
                event.preventDefault();
                show(null);
                return;
            }

            const edit = event.target.closest('#grades-grid button[data-action-type="edit"]');
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

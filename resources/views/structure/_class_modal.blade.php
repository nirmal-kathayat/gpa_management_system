{{-- The one Add / Edit Class form, in a modal. Edit fills it from the row's data attributes. --}}
@php
    $editing = old('class_id') ? \App\Models\SchoolClass::find(old('class_id')) : null;
    $reopen = $errors->has('name') || $errors->has('sections');
@endphp

<div class="modal fade form-modal" id="classFormModal" tabindex="-1" aria-labelledby="classFormTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form class="form-card" id="classForm" method="POST"
                  action="{{ $editing ? route('structure.classes.update', $editing) : route('structure.classes.store') }}">
                @csrf
                <input type="hidden" name="_method" id="classFormMethod" value="{{ $editing ? 'PUT' : 'POST' }}">
                <input type="hidden" name="class_id" id="classFormId" value="{{ $editing?->id }}">
                <input type="hidden" name="school_id" value="{{ $schoolId }}">

                <div class="form-card-head">
                    <h2 class="form-card-title" id="classFormTitle">{{ $editing ? 'Edit class '.$editing->name : 'Add Class' }}</h2>
                    <p class="form-card-sub">A class and the sections it runs. Students are filed under these.</p>
                    <button type="button" class="form-card-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fas fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="form-card-body">
                    <div class="form-grid">
                        <div class="form-field">
                            <label class="form-label" for="class_name">Class <span class="req">*</span></label>
                            <input type="text" id="class_name" name="name" required maxlength="50" placeholder="e.g. 10"
                                   value="{{ old('name', $editing?->name) }}"
                                   class="form-input @error('name') is-invalid @enderror">
                            @error('name')
                                <p class="form-error">{{ $message }}</p>
                            @else
                                <p class="form-hint">Renaming moves every student in it to the new name.</p>
                            @enderror
                        </div>

                        <div class="form-field">
                            <label class="form-label" for="class_sections">Sections <span class="req">*</span></label>
                            <input type="text" id="class_sections" name="sections" required maxlength="200" placeholder="e.g. A, B, C"
                                   value="{{ old('sections', $editing ? implode(', ', $editing->sections) : '') }}"
                                   class="form-input @error('sections') is-invalid @enderror">
                            @error('sections')
                                <p class="form-error">{{ $message }}</p>
                            @else
                                <p class="form-hint">Separated by commas. A section with students cannot be dropped.</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="form-card-foot">
                    <button type="button" class="btn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-primary-flat" id="classFormSubmit">{{ $editing ? 'Update Class' : 'Add Class' }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        const modal = new bootstrap.Modal(document.getElementById('classFormModal'));
        const form = document.getElementById('classForm');

        function show(row) {
            const editing = Boolean(row);

            form.action = editing ? '{{ url('structure/classes') }}/' + row.id : '{{ route('structure.classes.store') }}';
            document.getElementById('classFormMethod').value = editing ? 'PUT' : 'POST';
            document.getElementById('classFormId').value = editing ? row.id : '';
            document.getElementById('classFormTitle').textContent = editing ? 'Edit class ' + row.name : 'Add Class';
            document.getElementById('classFormSubmit').textContent = editing ? 'Update Class' : 'Add Class';
            document.getElementById('class_name').value = editing ? row.name : '';
            document.getElementById('class_sections').value = editing ? row.sections : 'A';

            form.querySelectorAll('.form-input.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            form.querySelectorAll('.form-error.is-client').forEach(el => el.remove());

            modal.show();
        }

        document.getElementById('addClassBtn')?.addEventListener('click', () => show(null));
        document.querySelectorAll('[data-edit-class]').forEach(button => {
            button.addEventListener('click', () => show(button.dataset));
        });

        @if($reopen)
            modal.show();
        @endif
    })();
</script>
@endpush

{{--
    The four fields that name a class: school (when there is more than one to
    choose from), class, section and academic year. Class and section follow
    the chosen school from $classMap. A page adds its own fields after this
    inside the same .form-grid; every select marked data-picker becomes a
    Select2 when the script below runs.

    Expects: $schools, $classMap, $filters.
--}}
@if($schools->count() > 1)
    <div class="form-field is-third">
        <label class="form-label" for="school_id">School <span class="req">*</span></label>
        <select id="school_id" name="school_id" required class="form-input" data-picker data-placeholder="Choose a school">
            <option value=""></option>
            @foreach($schools as $school)
                <option value="{{ $school->id }}" {{ $filters['school_id'] == $school->id ? 'selected' : '' }}>{{ $school->name }}</option>
            @endforeach
        </select>
    </div>
@else
    <input type="hidden" id="school_id" name="school_id" value="{{ $filters['school_id'] }}">
@endif

<div class="form-field is-third">
    <label class="form-label" for="class">Class <span class="req">*</span></label>
    <select id="class" name="class" required class="form-input" data-picker data-placeholder="Choose a class" data-current="{{ $filters['class'] }}">
        <option value=""></option>
    </select>
</div>

<div class="form-field is-third">
    <label class="form-label" for="section">Section <span class="req">*</span></label>
    <select id="section" name="section" required class="form-input" data-picker data-placeholder="Choose a section" data-current="{{ $filters['section'] }}">
        <option value=""></option>
    </select>
</div>

<div class="form-field is-third">
    <label class="form-label" for="academic_year">Academic Year <span class="req">*</span></label>
    @php $pickerYears = \App\Models\AcademicYear::ordered()->get(); @endphp
    <select id="academic_year" name="academic_year" required class="form-input" data-picker data-placeholder="Choose a year">
        <option value=""></option>
        @foreach($pickerYears as $pickerYear)
            <option value="{{ $pickerYear->year }}" {{ ($filters['academic_year'] ?? \App\Models\AcademicYear::current()) === $pickerYear->year ? 'selected' : '' }}>
                {{ $pickerYear->year }}{{ $pickerYear->is_current ? ' (current)' : '' }}
            </option>
        @endforeach
    </select>
</div>

@push('scripts')
<script>
    (function () {
        const classMap = @json($classMap);
        const schoolEl = document.getElementById('school_id');
        const classEl = document.getElementById('class');
        const sectionEl = document.getElementById('section');

        // Every picker is a Select2, so a long list of schools or subjects
        // can be typed into. The placeholder is the empty first option.
        $('[data-picker]').each(function () {
            $(this).select2({
                width: '100%',
                placeholder: this.dataset.placeholder,
                allowClear: false,
                // Short lists do not need a search box.
                minimumResultsForSearch: ['class', 'section', 'exam_type', 'academic_year'].includes(this.id) ? Infinity : 0,
            }).on('select2:select', function () {
                // Select2 hides the <select>, so the validator's input listener
                // never sees the fix; clear its message here.
                $(this).removeClass('is-invalid').closest('.form-field').find('.form-error.is-client').remove();
            });
        });

        function fill(select, values, current) {
            select.innerHTML = '<option value=""></option>';

            values.forEach(function (value) {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = value;
                option.selected = value === current;
                select.appendChild(option);
            });

            // Redraws the Select2 from the new options without firing the
            // change handlers below, which would cascade in a loop.
            $(select).trigger('change.select2');
        }

        function classes() {
            const school = classMap[schoolEl.value] || {};
            fill(classEl, Object.keys(school), classEl.dataset.current);
            sections();
        }

        function sections() {
            const school = classMap[schoolEl.value] || {};
            fill(sectionEl, school[classEl.value] || [], sectionEl.dataset.current);
        }

        // Select2 raises its change through jQuery, which a native listener
        // never hears, so these are bound the jQuery way.
        $(schoolEl).on('change', function () {
            classEl.dataset.current = '';
            sectionEl.dataset.current = '';
            classes();
        });
        $(classEl).on('change', function () {
            sectionEl.dataset.current = '';
            sections();
        });
        classes();
    })();
</script>
@endpush

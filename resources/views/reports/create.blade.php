@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card">
            <div class="card-header">
                <h4>Create Student Report</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('reports.store') }}" method="POST">
                    @csrf

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="student_id" class="form-label">Student *</label>
                            <select class="form-select @error('student_id') is-invalid @enderror"
                                id="student_id" name="student_id" required onchange="updateSelectedStudent()">
                                <option value="">Select Student</option>
                                @foreach($students as $student)
                                <option value="{{ $student->id }}"
                                    data-name="{{ $student->name }}"
                                    {{ old('student_id') == $student->id ? 'selected' : '' }}>
                                    {{ $student->name }} - {{ $student->class }} {{ $student->section }} (Roll: {{ $student->roll_number }})
                                </option>
                                @endforeach
                            </select>
                            @error('student_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="academic_year" class="form-label">Academic Year *</label>
                            <input type="text" class="form-control @error('academic_year') is-invalid @enderror"
                                id="academic_year" name="academic_year" value="{{ old('academic_year', '2024') }}"
                                placeholder="e.g., 2024" required>
                            @error('academic_year')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div id="selected-student-info" class="alert alert-info" style="display: none;">
                        <strong>Creating report for:</strong> <span id="selected-student-name"></span>
                    </div>

                    <h5>Subject Marks</h5>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th colspan="2">1st Terminal</th>
                                    <th colspan="2">2nd Terminal</th>
                                    <th colspan="2">Final Terminal</th>
                                    <th colspan="2">Pre-Board</th>
                                </tr>
                                <tr>
                                    <th></th>
                                    <th>Theory</th>
                                    <th>Practical</th>
                                    <th>Theory</th>
                                    <th>Practical</th>
                                    <th>Theory</th>
                                    <th>Practical</th>
                                    <th>Theory</th>
                                    <th>Practical</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($subjects as $index => $subject)
                                <tr>
                                    <td>{{ $subject->name }}</td>
                                    <input type="hidden" name="marks[{{ $index }}][subject_id]" value="{{ $subject->id }}">

                                    <td><input type="number" class="form-control form-control-sm" name="marks[{{ $index }}][first_terminal_th]" min="0" max="100" step="0.01"></td>
                                    <td><input type="number" class="form-control form-control-sm" name="marks[{{ $index }}][first_terminal_pr]" min="0" max="100" step="0.01"></td>

                                    <td><input type="number" class="form-control form-control-sm" name="marks[{{ $index }}][second_terminal_th]" min="0" max="100" step="0.01"></td>
                                    <td><input type="number" class="form-control form-control-sm" name="marks[{{ $index }}][second_terminal_pr]" min="0" max="100" step="0.01"></td>

                                    <td><input type="number" class="form-control form-control-sm" name="marks[{{ $index }}][final_terminal_th]" min="0" max="100" step="0.01"></td>
                                    <td><input type="number" class="form-control form-control-sm" name="marks[{{ $index }}][final_terminal_pr]" min="0" max="100" step="0.01"></td>

                                    <td><input type="number" class="form-control form-control-sm" name="marks[{{ $index }}][pre_board_th]" min="0" max="100" step="0.01"></td>
                                    <td><input type="number" class="form-control form-control-sm" name="marks[{{ $index }}][pre_board_pr]" min="0" max="100" step="0.01"></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <h5>Attendance</h5>
                            <div class="row mb-3">
                                <div class="col-6">
                                    <label for="attendance_days" class="form-label">Days Present</label>
                                    <input type="number" class="form-control" id="attendance_days" name="attendance_days" min="0">
                                </div>
                                <div class="col-6">
                                    <label for="total_days" class="form-label">Total Days</label>
                                    <input type="number" class="form-control" id="total_days" name="total_days" min="0">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h5>Behavioral Assessment</h5>
                            <div class="row">
                                <div class="col-6 mb-2">
                                    <label for="class_response" class="form-label">Class Response</label>
                                    <select class="form-select form-select-sm" name="class_response" required>
                                        <option value="A">A</option>
                                        <option value="B">B</option>
                                        <option value="C">C</option>
                                    </select>
                                </div>
                                <div class="col-6 mb-2">
                                    <label for="discipline" class="form-label">Discipline</label>
                                    <select class="form-select form-select-sm" name="discipline" required>
                                        <option value="A">A</option>
                                        <option value="B">B</option>
                                        <option value="C">C</option>
                                    </select>
                                </div>
                                <div class="col-6 mb-2">
                                    <label for="leadership" class="form-label">Leadership</label>
                                    <select class="form-select form-select-sm" name="leadership" required>
                                        <option value="A">A</option>
                                        <option value="B">B</option>
                                        <option value="C">C</option>
                                    </select>
                                </div>
                                <div class="col-6 mb-2">
                                    <label for="neatness" class="form-label">Neatness</label>
                                    <select class="form-select form-select-sm" name="neatness" required>
                                        <option value="A">A</option>
                                        <option value="B">B</option>
                                        <option value="C">C</option>
                                    </select>
                                </div>
                                <div class="col-6 mb-2">
                                    <label for="punctuality" class="form-label">Punctuality</label>
                                    <select class="form-select form-select-sm" name="punctuality" required>
                                        <option value="A">A</option>
                                        <option value="B">B</option>
                                        <option value="C">C</option>
                                    </select>
                                </div>
                                <div class="col-6 mb-2">
                                    <label for="regularity" class="form-label">Regularity</label>
                                    <select class="form-select form-select-sm" name="regularity" required>
                                        <option value="A">A</option>
                                        <option value="B">B</option>
                                        <option value="C">C</option>
                                    </select>
                                </div>
                                <div class="col-6 mb-2">
                                    <label for="social_conduct" class="form-label">Social Conduct</label>
                                    <select class="form-select form-select-sm" name="social_conduct" required>
                                        <option value="A">A</option>
                                        <option value="B">B</option>
                                        <option value="C">C</option>
                                    </select>
                                </div>
                                <div class="col-6 mb-2">
                                    <label for="sports_game" class="form-label">Sports/Game</label>
                                    <select class="form-select form-select-sm" name="sports_game" required>
                                        <option value="A">A</option>
                                        <option value="B" selected>B</option>
                                        <option value="C">C</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="remarks" class="form-label">Remarks</label>
                        <textarea class="form-control" id="remarks" name="remarks" rows="3" placeholder="Additional comments or remarks"></textarea>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('reports.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Create Report</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const studentSelect = document.getElementById('student_id');
        const form = document.querySelector('form');

        // Debug: Log selected student when changed
        studentSelect.addEventListener('change', function() {
            console.log('Selected student ID:', this.value);
            console.log('Selected student text:', this.options[this.selectedIndex].text);
        });

        // Debug: Log form data before submission
        form.addEventListener('submit', function(e) {
            const formData = new FormData(this);
            console.log('Form submission - Student ID:', formData.get('student_id'));

            // Ensure student is selected
            if (!formData.get('student_id')) {
                e.preventDefault();
                alert('Please select a student before submitting the form.');
                return false;
            }
        });
    });

    function updateSelectedStudent() {
        const select = document.getElementById('student_id');
        const info = document.getElementById('selected-student-info');
        const nameSpan = document.getElementById('selected-student-name');

        if (select.value) {
            const selectedOption = select.options[select.selectedIndex];
            nameSpan.textContent = selectedOption.text;
            info.style.display = 'block';
        } else {
            info.style.display = 'none';
        }
    }
</script>
@endsection
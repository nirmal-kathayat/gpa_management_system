@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Edit Student Report</h2>
    <a href="{{ route('reports.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i>Back to Reports
    </a>
</div>

<div class="card">
    <div class="card-header bg-warning text-dark">
        <h5 class="mb-0">
            <i class="fas fa-edit me-2"></i>Editing Report for: {{ $report->student->name }} ({{ $report->academic_year }})
        </h5>
    </div>
    <div class="card-body">
        <form action="{{ route('reports.update', $report) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row mb-4">
                <div class="col-md-6">
                    <label for="student_id" class="form-label">Student <span class="text-danger">*</span></label>
                    <select name="student_id" id="student_id" class="form-select @error('student_id') is-invalid @enderror" required>
                        <option value="">Select Student</option>
                        @foreach($students as $student)
                        <option value="{{ $student->id }}" {{ $report->student_id == $student->id ? 'selected' : '' }}>
                            {{ $student->name }} - {{ $student->class }} {{ $student->section }} (Roll: {{ $student->roll_number }})
                        </option>
                        @endforeach
                    </select>
                    @error('student_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="academic_year" class="form-label">Academic Year <span class="text-danger">*</span></label>
                    <input type="text" name="academic_year" id="academic_year"
                        class="form-control @error('academic_year') is-invalid @enderror"
                        value="{{ old('academic_year', $report->academic_year) }}"
                        placeholder="e.g., 2024" required>
                    @error('academic_year')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Marks Section -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Subject Marks</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th rowspan="2" class="align-middle">Subject</th>
                                    <th colspan="2" class="text-center">First Terminal</th>
                                    <th colspan="2" class="text-center">Second Terminal</th>
                                    <th colspan="2" class="text-center">Final Terminal</th>
                                    <th colspan="2" class="text-center">Pre-Board</th>
                                </tr>
                                <tr>
                                    <th class="text-center">Theory</th>
                                    <th class="text-center">Practical</th>
                                    <th class="text-center">Theory</th>
                                    <th class="text-center">Practical</th>
                                    <th class="text-center">Theory</th>
                                    <th class="text-center">Practical</th>
                                    <th class="text-center">Theory</th>
                                    <th class="text-center">Practical</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($subjects as $index => $subject)
                                <tr>
                                    <td class="fw-bold">{{ $subject->name }}</td>
                                    <input type="hidden" name="marks[{{ $index }}][subject_id]" value="{{ $subject->id }}">

                                    <!-- First Terminal -->
                                    <td>
                                        <input type="number" name="marks[{{ $index }}][first_terminal_th]"
                                            class="form-control form-control-sm" min="0" max="100" step="0.01"
                                            value="{{ old('marks.'.$index.'.first_terminal_th', $existingMarks[$subject->id]['first_terminal']['theory'] ?? '') }}">
                                    </td>
                                    <td>
                                        <input type="number" name="marks[{{ $index }}][first_terminal_pr]"
                                            class="form-control form-control-sm" min="0" max="100" step="0.01"
                                            value="{{ old('marks.'.$index.'.first_terminal_pr', $existingMarks[$subject->id]['first_terminal']['practical'] ?? '') }}">
                                    </td>

                                    <!-- Second Terminal -->
                                    <td>
                                        <input type="number" name="marks[{{ $index }}][second_terminal_th]"
                                            class="form-control form-control-sm" min="0" max="100" step="0.01"
                                            value="{{ old('marks.'.$index.'.second_terminal_th', $existingMarks[$subject->id]['second_terminal']['theory'] ?? '') }}">
                                    </td>
                                    <td>
                                        <input type="number" name="marks[{{ $index }}][second_terminal_pr]"
                                            class="form-control form-control-sm" min="0" max="100" step="0.01"
                                            value="{{ old('marks.'.$index.'.second_terminal_pr', $existingMarks[$subject->id]['second_terminal']['practical'] ?? '') }}">
                                    </td>

                                    <!-- Final Terminal -->
                                    <td>
                                        <input type="number" name="marks[{{ $index }}][final_terminal_th]"
                                            class="form-control form-control-sm" min="0" max="100" step="0.01"
                                            value="{{ old('marks.'.$index.'.final_terminal_th', $existingMarks[$subject->id]['final_terminal']['theory'] ?? '') }}">
                                    </td>
                                    <td>
                                        <input type="number" name="marks[{{ $index }}][final_terminal_pr]"
                                            class="form-control form-control-sm" min="0" max="100" step="0.01"
                                            value="{{ old('marks.'.$index.'.final_terminal_pr', $existingMarks[$subject->id]['final_terminal']['practical'] ?? '') }}">
                                    </td>

                                    <!-- Pre-Board -->
                                    <td>
                                        <input type="number" name="marks[{{ $index }}][pre_board_th]"
                                            class="form-control form-control-sm" min="0" max="100" step="0.01"
                                            value="{{ old('marks.'.$index.'.pre_board_th', $existingMarks[$subject->id]['pre_board']['theory'] ?? '') }}">
                                    </td>
                                    <td>
                                        <input type="number" name="marks[{{ $index }}][pre_board_pr]"
                                            class="form-control form-control-sm" min="0" max="100" step="0.01"
                                            value="{{ old('marks.'.$index.'.pre_board_pr', $existingMarks[$subject->id]['pre_board']['practical'] ?? '') }}">
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Attendance and Behavioral Assessment -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Attendance</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <label for="attendance_days" class="form-label">Days Present</label>
                                    <input type="number" name="attendance_days" id="attendance_days"
                                        class="form-control @error('attendance_days') is-invalid @enderror"
                                        value="{{ old('attendance_days', $report->attendance_days) }}" min="0">
                                    @error('attendance_days')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-6">
                                    <label for="total_days" class="form-label">Total Days</label>
                                    <input type="number" name="total_days" id="total_days"
                                        class="form-control @error('total_days') is-invalid @enderror"
                                        value="{{ old('total_days', $report->total_days) }}" min="0">
                                    @error('total_days')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0"><i class="fas fa-user-check me-2"></i>Behavioral Assessment</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-2">
                                @php
                                $behaviors = [
                                'class_response' => 'Class Response',
                                'discipline' => 'Discipline',
                                'leadership' => 'Leadership',
                                'neatness' => 'Neatness',
                                'punctuality' => 'Punctuality',
                                'regularity' => 'Regularity',
                                'social_conduct' => 'Social Conduct',
                                'sports_game' => 'Sports/Games'
                                ];
                                $grades = ['A+' => 'A+', 'A' => 'A', 'B+' => 'B+', 'B' => 'B', 'C+' => 'C+', 'C' => 'C', 'D' => 'D'];
                                @endphp

                                @foreach($behaviors as $key => $label)
                                <div class="col-6 mb-2">
                                    <label for="{{ $key }}" class="form-label small">{{ $label }}</label>
                                    <select name="{{ $key }}" id="{{ $key }}" class="form-select form-select-sm @error($key) is-invalid @enderror" required>
                                        <option value="">Select</option>
                                        @foreach($grades as $value => $text)
                                        <option value="{{ $value }}" {{ old($key, $report->$key) == $value ? 'selected' : '' }}>{{ $text }}</option>
                                        @endforeach
                                    </select>
                                    @error($key)
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Remarks -->
            <div class="mb-4">
                <label for="remarks" class="form-label">Remarks</label>
                <textarea name="remarks" id="remarks" rows="3"
                    class="form-control @error('remarks') is-invalid @enderror"
                    placeholder="Enter any additional remarks or comments...">{{ old('remarks', $report->remarks) }}</textarea>
                @error('remarks')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Submit Buttons -->
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('reports.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times me-1"></i>Cancel
                </a>
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-save me-1"></i>Update Report
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Auto-calculate attendance percentage
    document.addEventListener('DOMContentLoaded', function() {
        const attendanceDays = document.getElementById('attendance_days');
        const totalDays = document.getElementById('total_days');

        function updateAttendancePercentage() {
            const present = parseFloat(attendanceDays.value) || 0;
            const total = parseFloat(totalDays.value) || 0;

            if (total > 0) {
                const percentage = ((present / total) * 100).toFixed(1);
                console.log(`Attendance: ${percentage}%`);
            }
        }

        attendanceDays.addEventListener('input', updateAttendancePercentage);
        totalDays.addEventListener('input', updateAttendancePercentage);
    });
</script>
@endsection
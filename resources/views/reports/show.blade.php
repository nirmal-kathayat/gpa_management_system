@extends('layouts.app')

@section('content')
<div class="no-print mb-3">
    <div class="d-flex justify-content-between align-items-center">
        <h2>Student Report Card</h2>
        <div>
            <a href="{{ route('reports.pdf', $report) }}" class="btn btn-danger">Download PDF</a>
            <button onclick="window.print()" class="btn btn-primary">Print</button>
            <a href="{{ route('reports.index') }}" class="btn btn-secondary">Back</a>
        </div>
    </div>
</div>

<div class="report-card">
    <!-- School Header -->
    <div class="report-header">
        <h4>{{ $report->student->school->name }}</h4>
        <p>{{ $report->student->school->address }}</p>
        <p>Phone: {{ $report->student->school->phone ?? 'N/A' }}</p>
        <h5>MARK SHEET</h5>
        <p>Final Examination - {{ $report->academic_year }}</p>
    </div>

    <!-- Student Information -->
    <table class="report-table mb-3">
        <tr>
            <td><strong>Name:</strong> {{ $report->student->name }}</td>
            <td><strong>Class:</strong> {{ $report->student->class }}</td>
            <td><strong>Section:</strong> {{ $report->student->section }}</td>
            <td><strong>Roll:</strong> {{ $report->student->roll_number }}</td>
        </tr>
    </table>

    <!-- Marks Table -->
    <table class="report-table mb-3">
        <thead>
            <tr>
                <th rowspan="2">S.N.</th>
                <th rowspan="2">Subjects</th>
                <th colspan="2">1ST TERMINAL EXAMINATION</th>
                <th colspan="2">2ND TERMINAL EXAMINATION</th>
                <th colspan="2">FINAL TERMINAL EXAMINATION</th>
                <th colspan="2">PRE-BOARD EXAMINATION</th>
                <th rowspan="2">Total Grade</th>
                <th rowspan="2">Total GP</th>
            </tr>
            <tr>
                <th>TH</th>
                <th>PR</th>
                <th>TH</th>
                <th>PR</th>
                <th>TH</th>
                <th>PR</th>
                <th>TH</th>
                <th>PR</th>
            </tr>
        </thead>
        <tbody>
            @foreach($subjects as $index => $subject)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td style="text-align: left;">{{ $subject->name }}</td>
                
                @php
                    $examTypes = ['first_terminal', 'second_terminal', 'final_terminal', 'pre_board'];
                    $finalGrade = '';
                    $finalGP = '';
                @endphp
                
                @foreach($examTypes as $examType)
                    @php
                        $mark = $marks[$subject->id][$examType] ?? null;
                        if ($examType === 'final_terminal' && $mark) {
                            $finalGrade = $mark->first()->letter_grade ?? '';
                            $finalGP = $mark->first()->grade_point ?? '';
                        }
                    @endphp
                    <td>{{ $mark ? $mark->first()->theory_marks : '' }}</td>
                    <td>{{ $mark ? $mark->first()->practical_marks : '' }}</td>
                @endforeach
                
                <td>{{ $finalGrade }}</td>
                <td>{{ $finalGP }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Summary Section -->
    <div class="row">
        <div class="col-md-6">
            <table class="report-table">
                <tr>
                    <td><strong>GPA Obtained</strong></td>
                    <td>{{ $report->final_gpa }}</td>
                </tr>
                <tr>
                    <td><strong>Final Grade</strong></td>
                    <td>{{ $report->final_grade }}</td>
                </tr>
                <tr>
                    <td><strong>Position</strong></td>
                    <td>{{ $report->position ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td><strong>Attendance</strong></td>
                    <td>{{ $report->attendance_days }}/{{ $report->total_days }}</td>
                </tr>
            </table>
        </div>
        
        <div class="col-md-6">
            <table class="report-table">
                <tr>
                    <td><strong>Class Response</strong></td>
                    <td>{{ $report->class_response }}</td>
                </tr>
                <tr>
                    <td><strong>Discipline</strong></td>
                    <td>{{ $report->discipline }}</td>
                </tr>
                <tr>
                    <td><strong>Leadership</strong></td>
                    <td>{{ $report->leadership }}</td>
                </tr>
                <tr>
                    <td><strong>Neatness</strong></td>
                    <td>{{ $report->neatness }}</td>
                </tr>
                <tr>
                    <td><strong>Punctuality</strong></td>
                    <td>{{ $report->punctuality }}</td>
                </tr>
                <tr>
                    <td><strong>Regularity</strong></td>
                    <td>{{ $report->regularity }}</td>
                </tr>
                <tr>
                    <td><strong>Social Conduct</strong></td>
                    <td>{{ $report->social_conduct }}</td>
                </tr>
                <tr>
                    <td><strong>Sports/Game</strong></td>
                    <td>{{ $report->sports_game }}</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Grading System -->
    <div class="row mt-4">
        <div class="col-md-6">
            <h6>Grading System</h6>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Letter Grade</th>
                        <th>Grade Point</th>
                        <th>Equivalent Marks</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($gradeSystem as $grade)
                    <tr>
                        <td>{{ $grade->letter_grade }}</td>
                        <td>{{ $grade->grade_point }}</td>
                        <td>{{ $grade->marks_from }} to {{ $grade->marks_to }}</td>
                        <td>{{ $grade->remarks }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div class="col-md-6">
            <h6>Division System</h6>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Division</th>
                        <th>Equivalent Marks</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>1st Division</td><td>60 to below 80</td><td>Very Good</td></tr>
                    <tr><td>2nd Division</td><td>50 to below 60</td><td>Good</td></tr>
                    <tr><td>3rd Division</td><td>35 to below 50</td><td>Partially Acceptable</td></tr>
                    <tr><td>Fail</td><td>0 to below 35</td><td>Insufficient</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Remarks -->
    @if($report->remarks)
    <div class="mt-3">
        <strong>Remarks:</strong> {{ $report->remarks }}
    </div>
    @endif

    <!-- Signatures -->
    <div class="row mt-5">
        <div class="col-3 text-center">
            <div style="border-top: 1px solid #000; margin-top: 50px; padding-top: 5px;">
                <small>{{ $report->issue_date->format('Y-m-d') }}</small><br>
                <small>Issue Date</small>
            </div>
        </div>
        <div class="col-3 text-center">
            <div style="border-top: 1px solid #000; margin-top: 50px; padding-top: 5px;">
                <small>Class Teacher</small>
            </div>
        </div>
        <div class="col-3 text-center">
            <div style="border-top: 1px solid #000; margin-top: 50px; padding-top: 5px;">
                <small>Exam Coordinator</small>
            </div>
        </div>
        <div class="col-3 text-center">
            <div style="border-top: 1px solid #000; margin-top: 50px; padding-top: 5px;">
                <small>Principal</small>
            </div>
        </div>
    </div>
</div>
@endsection

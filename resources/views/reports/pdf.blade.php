<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Report Card - {{ $report->student->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        .report-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .report-table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 15px;
        }
        .report-table th, .report-table td {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
        }
        .text-left {
            text-align: left !important;
        }
        .signatures {
            margin-top: 50px;
        }
        .signature-box {
            display: inline-block;
            width: 23%;
            text-align: center;
            margin-right: 2%;
        }
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 40px;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <!-- School Header -->
    <div class="report-header">
        <h3>{{ $report->student->school->name }}</h3>
        <p>{{ $report->student->school->address }}</p>
        <p>Phone: {{ $report->student->school->phone ?? 'N/A' }}</p>
        <h4>MARK SHEET</h4>
        <p>Final Examination - {{ $report->academic_year }}</p>
    </div>

    <!-- Student Information -->
    <table class="report-table">
        <tr>
            <td><strong>Name:</strong> {{ $report->student->name }}</td>
            <td><strong>Class:</strong> {{ $report->student->class }}</td>
            <td><strong>Section:</strong> {{ $report->student->section }}</td>
            <td><strong>Roll:</strong> {{ $report->student->roll_number }}</td>
        </tr>
    </table>

    <!-- Marks Table -->
    <table class="report-table">
        <thead>
            <tr>
                <th rowspan="2">S.N.</th>
                <th rowspan="2">Subjects</th>
                <th colspan="2">1ST TERMINAL</th>
                <th colspan="2">2ND TERMINAL</th>
                <th colspan="2">FINAL TERMINAL</th>
                <th colspan="2">PRE-BOARD</th>
                <th rowspan="2">Grade</th>
                <th rowspan="2">GP</th>
            </tr>
            <tr>
                <th>TH</th><th>PR</th><th>TH</th><th>PR</th><th>TH</th><th>PR</th><th>TH</th><th>PR</th>
            </tr>
        </thead>
        <tbody>
            @foreach($subjects as $index => $subject)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td class="text-left">{{ $subject->name }}</td>
                
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

    <!-- Summary and Grading System -->
    <table class="report-table" style="width: 48%; float: left;">
        <tr><td><strong>GPA Obtained</strong></td><td>{{ $report->final_gpa }}</td></tr>
        <tr><td><strong>Final Grade</strong></td><td>{{ $report->final_grade }}</td></tr>
        <tr><td><strong>Position</strong></td><td>{{ $report->position ?? 'N/A' }}</td></tr>
        <tr><td><strong>Attendance</strong></td><td>{{ $report->attendance_days }}/{{ $report->total_days }}</td></tr>
    </table>

    <table class="report-table" style="width: 48%; float: right;">
        <tr><td><strong>Class Response</strong></td><td>{{ $report->class_response }}</td></tr>
        <tr><td><strong>Discipline</strong></td><td>{{ $report->discipline }}</td></tr>
        <tr><td><strong>Leadership</strong></td><td>{{ $report->leadership }}</td></tr>
        <tr><td><strong>Neatness</strong></td><td>{{ $report->neatness }}</td></tr>
        <tr><td><strong>Punctuality</strong></td><td>{{ $report->punctuality }}</td></tr>
        <tr><td><strong>Regularity</strong></td><td>{{ $report->regularity }}</td></tr>
        <tr><td><strong>Social Conduct</strong></td><td>{{ $report->social_conduct }}</td></tr>
        <tr><td><strong>Sports/Game</strong></td><td>{{ $report->sports_game }}</td></tr>
    </table>

    <div style="clear: both;"></div>

    <!-- Grading System -->
    <h5>Grading System</h5>
    <table class="report-table" style="width: 48%; float: left;">
        <thead>
            <tr><th>Letter Grade</th><th>Grade Point</th><th>Marks</th><th>Remarks</th></tr>
        </thead>
        <tbody>
            @foreach($gradeSystem as $grade)
            <tr>
                <td>{{ $grade->letter_grade }}</td>
                <td>{{ $grade->grade_point }}</td>
                <td>{{ $grade->marks_from }}-{{ $grade->marks_to }}</td>
                <td>{{ $grade->remarks }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="report-table" style="width: 48%; float: right;">
        <thead>
            <tr><th>Division</th><th>Marks</th><th>Remarks</th></tr>
        </thead>
        <tbody>
            <tr><td>1st Division</td><td>60-79</td><td>Very Good</td></tr>
            <tr><td>2nd Division</td><td>50-59</td><td>Good</td></tr>
            <tr><td>3rd Division</td><td>35-49</td><td>Acceptable</td></tr>
            <tr><td>Fail</td><td>0-34</td><td>Insufficient</td></tr>
        </tbody>
    </table>

    <div style="clear: both;"></div>

    @if($report->remarks)
    <p><strong>Remarks:</strong> {{ $report->remarks }}</p>
    @endif

    <!-- Signatures -->
    <div class="signatures">
        <div class="signature-box">
            <div class="signature-line">
                {{ $report->issue_date->format('Y-m-d') }}<br>
                <small>Issue Date</small>
            </div>
        </div>
        <div class="signature-box">
            <div class="signature-line">
                <small>Class Teacher</small>
            </div>
        </div>
        <div class="signature-box">
            <div class="signature-line">
                <small>Exam Coordinator</small>
            </div>
        </div>
        <div class="signature-box">
            <div class="signature-line">
                <small>Principal</small>
            </div>
        </div>
    </div>
</body>
</html>

<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentReport;
use App\Models\StudentMark;
use App\Models\Subject;
use App\Models\GradeSystem;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function index()
    {
        $reports = StudentReport::with('student')->paginate(10);
        return view('reports.index', compact('reports'));
    }

    public function create()
    {
        $students = Student::all();
        $subjects = Subject::where('is_active', true)->get();
        return view('reports.create', compact('students', 'subjects'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'academic_year' => 'required|string',
            'marks' => 'required|array',
            'marks.*.subject_id' => 'required|exists:subjects,id',
            'marks.*.first_terminal_th' => 'nullable|numeric|min:0|max:100',
            'marks.*.first_terminal_pr' => 'nullable|numeric|min:0|max:100',
            'marks.*.second_terminal_th' => 'nullable|numeric|min:0|max:100',
            'marks.*.second_terminal_pr' => 'nullable|numeric|min:0|max:100',
            'marks.*.final_terminal_th' => 'nullable|numeric|min:0|max:100',
            'marks.*.final_terminal_pr' => 'nullable|numeric|min:0|max:100',
            'marks.*.pre_board_th' => 'nullable|numeric|min:0|max:100',
            'marks.*.pre_board_pr' => 'nullable|numeric|min:0|max:100',
            'attendance_days' => 'nullable|integer|min:0',
            'total_days' => 'nullable|integer|min:0',
            'remarks' => 'nullable|string',
            'class_response' => 'required|string|max:2',
            'discipline' => 'required|string|max:2',
            'leadership' => 'required|string|max:2',
            'neatness' => 'required|string|max:2',
            'punctuality' => 'required|string|max:2',
            'regularity' => 'required|string|max:2',
            'social_conduct' => 'required|string|max:2',
            'sports_game' => 'required|string|max:2',
        ]);

        // Calculate GPA and create marks records
        $totalGradePoints = 0;
        $totalSubjects = 0;
        $hasFailedSubject = false;

        foreach ($validated['marks'] as $markData) {
            $examTypes = ['first_terminal', 'second_terminal', 'final_terminal', 'pre_board'];

            foreach ($examTypes as $examType) {
                $theoryKey = $examType . '_th';
                $practicalKey = $examType . '_pr';

                if (isset($markData[$theoryKey]) || isset($markData[$practicalKey])) {
                    $theoryMarks = $markData[$theoryKey] ?? 0;
                    $practicalMarks = $markData[$practicalKey] ?? 0;
                    $totalMarks = $theoryMarks + $practicalMarks;

                    // Convert total marks to percentage if it exceeds 100
                    $percentageMarks = $totalMarks;
                    if ($totalMarks > 100) {
                        $percentageMarks = ($totalMarks / 200) * 100;
                    }

                    $grade = GradeSystem::getGradeByMarks($percentageMarks);

                    // Handle case where no grade is found
                    if (!$grade) {
                        $grade = GradeSystem::where('marks_from', '<=', $percentageMarks)
                            ->orderBy('marks_from', 'desc')
                            ->first();

                        if (!$grade) {
                            $grade = GradeSystem::orderBy('marks_from', 'asc')->first();
                        }

                        if (!$grade) {
                            $letterGrade = 'NG';
                            $gradePoint = 0;
                        } else {
                            $letterGrade = $grade->letter_grade;
                            $gradePoint = $grade->grade_point;
                        }
                    } else {
                        $letterGrade = $grade->letter_grade;
                        $gradePoint = $grade->grade_point;
                    }

                    // Check if this is a failed grade (NG, F, or grade point < 2.0)
                    if ($letterGrade === 'NG' || $letterGrade === 'F' || $gradePoint < 2.0) {
                        $hasFailedSubject = true;
                        $letterGrade = 'NG'; // Force to NG for failed subjects
                        $gradePoint = 0;
                    }

                    StudentMark::create([
                        'student_id' => $validated['student_id'],
                        'subject_id' => $markData['subject_id'],
                        'exam_type' => $examType,
                        'theory_marks' => $theoryMarks,
                        'practical_marks' => $practicalMarks,
                        'total_marks' => $totalMarks,
                        'letter_grade' => $letterGrade,
                        'grade_point' => $gradePoint,
                        'academic_year' => $validated['academic_year']
                    ]);

                    if ($examType === 'final_terminal') {
                        if (!($letterGrade === 'NG' || $letterGrade === 'F' || $gradePoint < 2.0)) {
                            $totalGradePoints += $gradePoint;
                        }
                        $totalSubjects++;
                    }
                }
            }
        }

        // Calculate result based on failure status
        if ($hasFailedSubject) {
            $finalGpa = 0.00;
            $finalGradeLetter = 'NG';
            $resultStatus = 'FAILED';
            $resultRemarks = 'Student has failed in one or more subjects. Needs improvement.';
        } else {
            $finalGpa = $totalSubjects > 0 ? round($totalGradePoints / $totalSubjects, 2) : 0;

            $finalGrade = GradeSystem::where('grade_point', '<=', $finalGpa)
                ->orderBy('grade_point', 'desc')
                ->first();

            if (!$finalGrade) {
                $gpaPercentage = ($finalGpa / 4.0) * 100;
                $finalGrade = GradeSystem::where('marks_from', '<=', $gpaPercentage)
                    ->where('marks_to', '>=', $gpaPercentage)
                    ->first();
            }

            $finalGradeLetter = $finalGrade ? $finalGrade->letter_grade : 'NG';

            if ($finalGpa >= 3.5) {
                $resultStatus = 'PASSED WITH DISTINCTION';
                $resultRemarks = 'Excellent performance. Keep up the good work!';
            } elseif ($finalGpa >= 3.0) {
                $resultStatus = 'PASSED WITH FIRST DIVISION';
                $resultRemarks = 'Very good performance. Well done!';
            } elseif ($finalGpa >= 2.5) {
                $resultStatus = 'PASSED WITH SECOND DIVISION';
                $resultRemarks = 'Good performance. Continue to improve.';
            } elseif ($finalGpa >= 2.0) {
                $resultStatus = 'PASSED WITH THIRD DIVISION';
                $resultRemarks = 'Satisfactory performance. More effort needed.';
            } else {
                $resultStatus = 'FAILED';
                $resultRemarks = 'Needs significant improvement in studies.';
            }
        }

        StudentReport::create([
            'student_id' => $validated['student_id'],
            'academic_year' => $validated['academic_year'],
            'final_gpa' => $finalGpa,
            'final_grade' => $finalGradeLetter,
            'result_status' => $resultStatus,
            'result_remarks' => $resultRemarks,
            'attendance_days' => $validated['attendance_days'],
            'total_days' => $validated['total_days'],
            'remarks' => $validated['remarks'],
            'class_response' => $validated['class_response'],
            'discipline' => $validated['discipline'],
            'leadership' => $validated['leadership'],
            'neatness' => $validated['neatness'],
            'punctuality' => $validated['punctuality'],
            'regularity' => $validated['regularity'],
            'social_conduct' => $validated['social_conduct'],
            'sports_game' => $validated['sports_game'],
            'issue_date' => now()
        ]);

        return redirect()->route('reports.index')->with('success', 'Report created successfully!');
    }

    public function show(StudentReport $report)
    {
        $report->load('student.school');
        $marks = StudentMark::where('student_id', $report->student_id)
            ->where('academic_year', $report->academic_year)
            ->with('subject')
            ->get()
            ->groupBy(['subject_id', 'exam_type']);

        $subjects = Subject::where('is_active', true)->get();
        $gradeSystem = GradeSystem::all();

        return view('reports.show', compact('report', 'marks', 'subjects', 'gradeSystem'));
    }

    public function edit(StudentReport $report)
    {
        $students = Student::all();
        $subjects = Subject::where('is_active', true)->get();

        $marks = StudentMark::where('student_id', $report->student_id)
            ->where('academic_year', $report->academic_year)
            ->get();

        $existingMarks = [];
        foreach ($marks as $mark) {
            $existingMarks[$mark->subject_id][$mark->exam_type] = [
                'theory' => $mark->theory_marks,
                'practical' => $mark->practical_marks
            ];
        }

        return view('reports.edit', compact('report', 'students', 'subjects', 'existingMarks'));
    }

    public function update(Request $request, StudentReport $report)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'academic_year' => 'required|string',
            'marks' => 'required|array',
            'marks.*.subject_id' => 'required|exists:subjects,id',
            'marks.*.first_terminal_th' => 'nullable|numeric|min:0|max:100',
            'marks.*.first_terminal_pr' => 'nullable|numeric|min:0|max:100',
            'marks.*.second_terminal_th' => 'nullable|numeric|min:0|max:100',
            'marks.*.second_terminal_pr' => 'nullable|numeric|min:0|max:100',
            'marks.*.final_terminal_th' => 'nullable|numeric|min:0|max:100',
            'marks.*.final_terminal_pr' => 'nullable|numeric|min:0|max:100',
            'marks.*.pre_board_th' => 'nullable|numeric|min:0|max:100',
            'marks.*.pre_board_pr' => 'nullable|numeric|min:0|max:100',
            'attendance_days' => 'nullable|integer|min:0',
            'total_days' => 'nullable|integer|min:0',
            'remarks' => 'nullable|string',
            'class_response' => 'required|string|max:2',
            'discipline' => 'required|string|max:2',
            'leadership' => 'required|string|max:2',
            'neatness' => 'required|string|max:2',
            'punctuality' => 'required|string|max:2',
            'regularity' => 'required|string|max:2',
            'social_conduct' => 'required|string|max:2',
            'sports_game' => 'required|string|max:2',
        ]);

        try {
            StudentMark::where('student_id', $report->student_id)
                ->where('academic_year', $report->academic_year)
                ->delete();

            $totalGradePoints = 0;
            $totalSubjects = 0;
            $hasFailedSubject = false;

            foreach ($validated['marks'] as $markData) {
                $examTypes = ['first_terminal', 'second_terminal', 'final_terminal', 'pre_board'];

                foreach ($examTypes as $examType) {
                    $theoryKey = $examType . '_th';
                    $practicalKey = $examType . '_pr';

                    if (isset($markData[$theoryKey]) || isset($markData[$practicalKey])) {
                        $theoryMarks = $markData[$theoryKey] ?? 0;
                        $practicalMarks = $markData[$practicalKey] ?? 0;
                        $totalMarks = $theoryMarks + $practicalMarks;

                        $percentageMarks = $totalMarks;
                        if ($totalMarks > 100) {
                            $percentageMarks = ($totalMarks / 200) * 100;
                        }

                        $grade = GradeSystem::getGradeByMarks($percentageMarks);

                        if (!$grade) {
                            $grade = GradeSystem::where('marks_from', '<=', $percentageMarks)
                                ->orderBy('marks_from', 'desc')
                                ->first();

                            if (!$grade) {
                                $grade = GradeSystem::orderBy('marks_from', 'asc')->first();
                            }

                            if (!$grade) {
                                $letterGrade = 'NG';
                                $gradePoint = 0;
                            } else {
                                $letterGrade = $grade->letter_grade;
                                $gradePoint = $grade->grade_point;
                            }
                        } else {
                            $letterGrade = $grade->letter_grade;
                            $gradePoint = $grade->grade_point;
                        }

                        // Check if this is a failed grade
                        if ($letterGrade === 'NG' || $letterGrade === 'F' || $gradePoint < 2.0) {
                            $hasFailedSubject = true;
                            $letterGrade = 'NG';
                            $gradePoint = 0;
                        }

                        StudentMark::create([
                            'student_id' => $validated['student_id'],
                            'subject_id' => $markData['subject_id'],
                            'exam_type' => $examType,
                            'theory_marks' => $theoryMarks,
                            'practical_marks' => $practicalMarks,
                            'total_marks' => $totalMarks,
                            'letter_grade' => $letterGrade,
                            'grade_point' => $gradePoint,
                            'academic_year' => $validated['academic_year']
                        ]);

                        if ($examType === 'final_terminal') {
                            if (!($letterGrade === 'NG' || $letterGrade === 'F' || $gradePoint < 2.0)) {
                                $totalGradePoints += $gradePoint;
                            }
                            $totalSubjects++;
                        }
                    }
                }
            }

            // Calculate result based on failure status
            if ($hasFailedSubject) {
                $finalGpa = 0.00;
                $finalGradeLetter = 'NG';
                $resultStatus = 'FAILED';
                $resultRemarks = 'Student has failed in one or more subjects. Needs improvement.';
            } else {
                $finalGpa = $totalSubjects > 0 ? round($totalGradePoints / $totalSubjects, 2) : 0;

                $finalGrade = GradeSystem::where('grade_point', '<=', $finalGpa)
                    ->orderBy('grade_point', 'desc')
                    ->first();

                if (!$finalGrade) {
                    $gpaPercentage = ($finalGpa / 4.0) * 100;
                    $finalGrade = GradeSystem::where('marks_from', '<=', $gpaPercentage)
                        ->where('marks_to', '>=', $gpaPercentage)
                        ->first();
                }

                $finalGradeLetter = $finalGrade ? $finalGrade->letter_grade : 'NG';

                if ($finalGpa >= 3.5) {
                    $resultStatus = 'PASSED WITH DISTINCTION';
                    $resultRemarks = 'Excellent performance. Keep up the good work!';
                } elseif ($finalGpa >= 3.0) {
                    $resultStatus = 'PASSED WITH FIRST DIVISION';
                    $resultRemarks = 'Very good performance. Well done!';
                } elseif ($finalGpa >= 2.5) {
                    $resultStatus = 'PASSED WITH SECOND DIVISION';
                    $resultRemarks = 'Good performance. Continue to improve.';
                } elseif ($finalGpa >= 2.0) {
                    $resultStatus = 'PASSED WITH THIRD DIVISION';
                    $resultRemarks = 'Satisfactory performance. More effort needed.';
                } else {
                    $resultStatus = 'FAILED';
                    $resultRemarks = 'Needs significant improvement in studies.';
                }
            }

            $report->update([
                'student_id' => $validated['student_id'],
                'academic_year' => $validated['academic_year'],
                'final_gpa' => $finalGpa,
                'final_grade' => $finalGradeLetter,
                'result_status' => $resultStatus,
                'result_remarks' => $resultRemarks,
                'attendance_days' => $validated['attendance_days'],
                'total_days' => $validated['total_days'],
                'remarks' => $validated['remarks'],
                'class_response' => $validated['class_response'],
                'discipline' => $validated['discipline'],
                'leadership' => $validated['leadership'],
                'neatness' => $validated['neatness'],
                'punctuality' => $validated['punctuality'],
                'regularity' => $validated['regularity'],
                'social_conduct' => $validated['social_conduct'],
                'sports_game' => $validated['sports_game'],
            ]);

            return redirect()->route('reports.index')->with('success', 'Report updated successfully!');
        } catch (\Exception $e) {
            \Log::error('Error updating report', [
                'report_id' => $report->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'An error occurred while updating the report. Please try again.');
        }
    }

    public function downloadPdf(StudentReport $report)
    {
        $report->load('student.school');
        $marks = StudentMark::where('student_id', $report->student_id)
            ->where('academic_year', $report->academic_year)
            ->with('subject')
            ->get()
            ->groupBy(['subject_id', 'exam_type']);

        $subjects = Subject::where('is_active', true)->get();
        $gradeSystem = GradeSystem::all();

        $pdf = Pdf::loadView('reports.pdf', compact('report', 'marks', 'subjects', 'gradeSystem'));

        return $pdf->download('report-' . $report->student->name . '-' . $report->academic_year . '.pdf');
    }

    public function destroy(StudentReport $report)
    {
        try {
            if (!auth()->user()->isAdmin()) {
                return redirect()->route('reports.index')->with('error', 'You do not have permission to delete reports.');
            }

            $studentName = $report->student->name;
            $academicYear = $report->academic_year;

            StudentMark::where('student_id', $report->student_id)
                ->where('academic_year', $report->academic_year)
                ->delete();

            $report->delete();

            \Log::info('Report deleted', [
                'report_id' => $report->id,
                'student_name' => $studentName,
                'academic_year' => $academicYear,
                'deleted_by' => auth()->user()->name
            ]);

            return redirect()->route('reports.index')->with('success', "Report for {$studentName} ({$academicYear}) has been deleted successfully.");
        } catch (\Exception $e) {
            \Log::error('Error deleting report', [
                'report_id' => $report->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('reports.index')->with('error', 'An error occurred while deleting the report. Please try again.');
        }
    }
}

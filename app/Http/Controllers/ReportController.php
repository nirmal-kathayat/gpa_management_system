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

        foreach ($validated['marks'] as $markData) {
            $examTypes = ['first_terminal', 'second_terminal', 'final_terminal', 'pre_board'];
            
            foreach ($examTypes as $examType) {
                $theoryKey = $examType . '_th';
                $practicalKey = $examType . '_pr';
                
                if (isset($markData[$theoryKey]) || isset($markData[$practicalKey])) {
                    $theoryMarks = $markData[$theoryKey] ?? 0;
                    $practicalMarks = $markData[$practicalKey] ?? 0;
                    $totalMarks = $theoryMarks + $practicalMarks;
                    
                    $grade = GradeSystem::getGradeByMarks($totalMarks);
                    
                    StudentMark::create([
                        'student_id' => $validated['student_id'],
                        'subject_id' => $markData['subject_id'],
                        'exam_type' => $examType,
                        'theory_marks' => $theoryMarks,
                        'practical_marks' => $practicalMarks,
                        'total_marks' => $totalMarks,
                        'letter_grade' => $grade->letter_grade,
                        'grade_point' => $grade->grade_point,
                        'academic_year' => $validated['academic_year']
                    ]);
                    
                    if ($examType === 'final_terminal') {
                        $totalGradePoints += $grade->grade_point;
                        $totalSubjects++;
                    }
                }
            }
        }

        $finalGpa = $totalSubjects > 0 ? round($totalGradePoints / $totalSubjects, 2) : 0;
        $finalGrade = GradeSystem::where('grade_point', '<=', $finalGpa)
                                 ->orderBy('grade_point', 'desc')
                                 ->first();

        StudentReport::create([
            'student_id' => $validated['student_id'],
            'academic_year' => $validated['academic_year'],
            'final_gpa' => $finalGpa,
            'final_grade' => $finalGrade->letter_grade ?? 'NG',
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
}

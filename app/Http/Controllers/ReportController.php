<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentReport;
use App\Models\StudentMark;
use App\Models\Subject;
use App\Models\GradeSystem;
use App\Support\GradeCalculator;
use App\Support\TableResponse;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    /**
     * JSON rows for the TableHelper grid on the index page.
     */
    public function list(Request $request)
    {
        $query = StudentReport::with('student:id,name,class,section')
            ->whereIn('student_id', auth()->user()->getAccessibleStudents()->select('id'));

        return TableResponse::make($request, $query, [
            'search' => [
                fn ($q, $v) => $q->whereHas('student', fn ($s) => $s->where('name', 'like', '%'.$v.'%')),
                'academic_year',
            ],
            'filters' => [
                'student' => fn ($q, $v) => $q->whereHas('student', fn ($s) => $s->where('name', 'like', '%'.$v.'%')),
                'academic_year' => 'academic_year',
                'final_grade' => 'final_grade',
                'result_status' => ['result_status', 'exact'],
            ],
            'sort' => [
                'academic_year' => 'academic_year',
                'final_gpa' => 'final_gpa',
                'issue_date' => 'issue_date',
            ],
            'default' => ['id', 'desc'],
        ], fn ($report) => [
            'id' => $report->id,
            'student' => $report->student->name ?? '-',
            'class' => trim(($report->student->class ?? '').' - '.($report->student->section ?? ''), ' -'),
            'academic_year' => $report->academic_year,
            'final_gpa' => number_format((float) $report->final_gpa, 2),
            'final_grade' => $report->final_grade,
            'result_status' => $report->result_status ?? 'PASSED',
            'issue_date' => optional($report->issue_date)->format('d M Y') ?: '-',
        ]);
    }

    /**
     * A report belongs to a school through its student.
     */
    protected function authorizeReport(StudentReport $report): void
    {
        $this->authorizeSchool($report->student?->school_id);
    }

    /**
     * Stops a form from being posted with another school's student id.
     */
    protected function authorizeStudent(int $studentId): void
    {
        $this->authorizeSchool(Student::findOrFail($studentId)->school_id);
    }

    public function index()
    {
        // Counted over every accessible report, not just the page the grid shows.
        $base = StudentReport::whereIn('student_id', auth()->user()->getAccessibleStudents()->select('id'));

        return view('reports.index', [
            'totalReports' => (clone $base)->count(),
            'passedCount' => (clone $base)->where(fn ($q) => $q->where('result_status', '!=', 'FAILED')
                ->orWhereNull('result_status'))->count(),
            'failedCount' => (clone $base)->where('result_status', 'FAILED')->count(),
            'averageGpa' => round((float) (clone $base)->avg('final_gpa'), 2),
        ]);
    }

    public function create()
    {
        $students = auth()->user()->getAccessibleStudents()->orderBy('name')->get();
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

        $this->authorizeStudent((int) $validated['student_id']);

        $result = $this->gradeMarks(
            (int) $validated['student_id'],
            $validated['academic_year'],
            $validated['marks']
        );

        $finalGpa = $result['gpa'];
        $finalGradeLetter = $result['grade'];
        $resultStatus = $result['status'];
        $resultRemarks = $result['remarks'];

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

    /**
     * Stores every mark on the form and works out the final result.
     *
     * The scale decides the grade: a mark is turned into a percentage against
     * that subject's own full marks, and a subject counts as failed when its
     * band says so or when the mark is below the subject's pass mark. Only the
     * final terminal counts toward the GPA.
     */
    private function gradeMarks(int $studentId, string $academicYear, array $marks): array
    {
        $calculator = new GradeCalculator();
        $subjects = Subject::whereIn('id', array_column($marks, 'subject_id'))->get()->keyBy('id');

        $examTypes = ['first_terminal', 'second_terminal', 'final_terminal', 'pre_board'];

        $totalGradePoints = 0;
        $totalSubjects = 0;
        $hasFailedSubject = false;

        foreach ($marks as $markData) {
            $subject = $subjects->get($markData['subject_id']);
            $fullMarks = (float) ($subject->full_marks ?? 100);
            $passMarks = $subject?->pass_marks !== null ? (float) $subject->pass_marks : null;

            foreach ($examTypes as $examType) {
                $theoryKey = $examType.'_th';
                $practicalKey = $examType.'_pr';

                if (! isset($markData[$theoryKey]) && ! isset($markData[$practicalKey])) {
                    continue;
                }

                $theoryMarks = (float) ($markData[$theoryKey] ?? 0);
                $practicalMarks = (float) ($markData[$practicalKey] ?? 0);
                $totalMarks = $theoryMarks + $practicalMarks;

                $band = $calculator->forMarks($totalMarks, $fullMarks);
                $failed = $calculator->fails($band, $totalMarks, $passMarks);

                StudentMark::create([
                    'student_id' => $studentId,
                    'subject_id' => $markData['subject_id'],
                    'exam_type' => $examType,
                    'theory_marks' => $theoryMarks,
                    'practical_marks' => $practicalMarks,
                    'total_marks' => $totalMarks,
                    'letter_grade' => $band?->letter_grade ?? 'NG',
                    'grade_point' => $failed ? 0 : ($band?->grade_point ?? 0),
                    'academic_year' => $academicYear,
                ]);

                // Only the final terminal decides the year.
                if ($examType !== 'final_terminal') {
                    continue;
                }

                $totalSubjects++;

                if ($failed) {
                    $hasFailedSubject = true;
                } else {
                    $totalGradePoints += $band->grade_point;
                }
            }
        }

        $gpa = $hasFailedSubject || $totalSubjects === 0
            ? 0.0
            : round($totalGradePoints / $totalSubjects, 2);

        $result = $calculator->resultFor($gpa, $hasFailedSubject);

        return [
            'gpa' => $gpa,
            'grade' => $hasFailedSubject
                ? ($calculator->scale()->firstWhere('is_failing', true)?->letter_grade ?? 'NG')
                : ($calculator->forGradePoint($gpa)?->letter_grade ?? 'NG'),
            'status' => $result['status'],
            'remarks' => $result['remarks'],
        ];
    }

    public function show(StudentReport $report)
    {
        $this->authorizeReport($report);

        $report->load('student.school');
        $marks = StudentMark::where('student_id', $report->student_id)
            ->where('academic_year', $report->academic_year)
            ->with('subject')
            ->get()
            ->groupBy(['subject_id', 'exam_type']);

        $subjects = Subject::where('is_active', true)->get();
        $gradeSystem = GradeSystem::active()->ordered()->get();

        return view('reports.show', compact('report', 'marks', 'subjects', 'gradeSystem'));
    }

    public function edit(StudentReport $report)
    {
        $this->authorizeReport($report);

        $students = auth()->user()->getAccessibleStudents()->orderBy('name')->get();
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
        $this->authorizeReport($report);

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

        $this->authorizeStudent((int) $validated['student_id']);

        try {
            StudentMark::where('student_id', $report->student_id)
                ->where('academic_year', $report->academic_year)
                ->delete();

            $result = $this->gradeMarks(
                (int) $validated['student_id'],
                $validated['academic_year'],
                $validated['marks']
            );

            $finalGpa = $result['gpa'];
            $finalGradeLetter = $result['grade'];
            $resultStatus = $result['status'];
            $resultRemarks = $result['remarks'];

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
        $this->authorizeReport($report);

        $report->load('student.school');
        $marks = StudentMark::where('student_id', $report->student_id)
            ->where('academic_year', $report->academic_year)
            ->with('subject')
            ->get()
            ->groupBy(['subject_id', 'exam_type']);

        $subjects = Subject::where('is_active', true)->get();
        $gradeSystem = GradeSystem::active()->ordered()->get();

        $pdf = Pdf::loadView('reports.pdf', compact('report', 'marks', 'subjects', 'gradeSystem'));

        return $pdf->download('report-' . $report->student->name . '-' . $report->academic_year . '.pdf');
    }

    public function destroy(StudentReport $report)
    {
        $this->authorizeReport($report);

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

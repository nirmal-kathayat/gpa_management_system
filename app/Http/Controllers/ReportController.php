<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentReport;
use App\Models\StudentMark;
use App\Models\Subject;
use App\Models\GradeSystem;
use App\Support\ReportGrader;
use App\Support\TableResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function __construct(private readonly ReportGrader $grader)
    {
    }

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
                'class' => ['class', 'exact'],
                'academic_year' => ['academic_year', 'exact'],
                'final_grade' => 'final_grade',
                // Every pass is 'PASSED WITH ...', so the filter matches the family.
                'result_status' => fn ($q, $v) => $v === 'PASSED'
                    ? $q->where('result_status', 'like', 'PASSED%')
                    : $q->where('result_status', $v),
            ],
            'sort' => [
                'academic_year' => 'academic_year',
                'final_gpa' => 'final_gpa',
                'position' => 'position',
                'issue_date' => 'issue_date',
            ],
            // Newest first, so a report just issued is the row you land on.
            'default' => ['id', 'desc'],
        ], fn ($report) => [
            'id' => $report->id,
            'student' => $report->student->name ?? '-',
            'class' => trim($report->class.' - '.$report->section, ' -'),
            'academic_year' => $report->academic_year,
            'position' => $report->position,
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
            'years' => StudentReport::distinct()->orderByDesc('academic_year')->pluck('academic_year'),
            'classes' => (clone $base)->distinct()->orderBy('class')->pluck('class'),
            'totalReports' => (clone $base)->count(),
            'passedCount' => (clone $base)->where(fn ($q) => $q->where('result_status', 'like', 'PASSED%')
                ->orWhereNull('result_status'))->count(),
            'failedCount' => (clone $base)->where('result_status', 'FAILED')->count(),
            'averageGpa' => round((float) (clone $base)->avg('final_gpa'), 2),
        ]);
    }

    public function create(Request $request)
    {
        // The student's own page links here with the student already chosen.
        $selected = $request->filled('student_id')
            ? auth()->user()->getAccessibleStudents()->find($request->integer('student_id'))
            : null;

        return view('reports.create', [
            'subjects' => Subject::where('is_active', true)->orderBy('name')->get(),
            'selectedStudent' => $selected,
        ]);
    }

    /**
     * Both report forms post the same shape. A mark is checked against its own
     * subject's full marks rather than a flat 100, and attendance cannot exceed
     * the days the school was open.
     */
    private function validateReport(Request $request, ?StudentReport $report = null): array
    {
        $rules = [
            'student_id' => [
                'required', 'exists:students,id',
                // One report card per student per year.
                Rule::unique('student_reports')
                    ->where(fn ($q) => $q->where('academic_year', $request->input('academic_year')))
                    ->ignore($report?->id),
            ],
            'academic_year' => ['required', 'regex:/^\d{4}$/'],
            // Written on the card, since the student's own record moves on.
            'class' => 'required|string|max:50',
            'section' => 'required|string|max:10',
            'roll_number' => 'required|integer|min:1',
            'marks' => 'required|array',
            'marks.*.subject_id' => 'required|exists:subjects,id',
            'attendance_days' => 'nullable|integer|min:0',
            'total_days' => 'nullable|integer|min:0',
            'remarks' => 'nullable|string|max:2000',
        ];

        foreach (['class_response', 'discipline', 'leadership', 'neatness',
                  'punctuality', 'regularity', 'social_conduct', 'sports_game'] as $field) {
            $rules[$field] = 'required|string|max:2';
        }

        $fullMarks = Subject::pluck('full_marks', 'id');

        foreach ($request->input('marks', []) as $index => $row) {
            $limit = (float) ($fullMarks[$row['subject_id'] ?? null] ?? 100);

            foreach (['first_terminal', 'second_terminal', 'final_terminal', 'pre_board'] as $term) {
                foreach (['th', 'pr'] as $part) {
                    $rules["marks.{$index}.{$term}_{$part}"] = 'nullable|numeric|min:0|max:'.$limit;
                }
            }
        }

        $validated = $request->validate($rules, [
            'student_id.unique' => 'This student already has a report card for that academic year.',
            'academic_year.regex' => 'Enter the academic year as four digits, e.g. 2081.',
            'marks.*.*.max' => 'A mark cannot be more than the subject\'s full marks.',
        ]);

        // Theory and practical together cannot exceed what the subject is out of.
        foreach ($validated['marks'] as $index => $row) {
            $limit = (float) ($fullMarks[$row['subject_id']] ?? 100);

            foreach (['first_terminal', 'second_terminal', 'final_terminal', 'pre_board'] as $term) {
                $total = (float) ($row[$term.'_th'] ?? 0) + (float) ($row[$term.'_pr'] ?? 0);

                if ($total > $limit) {
                    throw ValidationException::withMessages([
                        "marks.{$index}.{$term}_th" => 'Theory and practical together cannot be more than '
                            .$limit.' for this subject.',
                    ]);
                }
            }
        }

        if (($validated['attendance_days'] ?? null) !== null
            && ($validated['total_days'] ?? null) !== null
            && $validated['attendance_days'] > $validated['total_days']) {
            throw ValidationException::withMessages([
                'attendance_days' => 'Days present cannot be more than the total days.',
            ]);
        }

        return $validated;
    }

    public function store(Request $request)
    {
        $validated = $this->validateReport($request);

        $this->authorizeStudent((int) $validated['student_id']);

        $report = DB::transaction(function () use ($validated) {
            // The report exists first so its marks have something to belong to;
            // the totals are written back once they have been graded.
            $report = StudentReport::create($this->reportAttributes($validated) + [
                'final_gpa' => 0,
                'final_grade' => '',
                'issue_date' => now(),
            ]);

            $report->update($this->grader->grade($report, $validated['marks']));

            return $report;
        });

        $this->grader->recalculatePositions($report);

        return redirect()->route('reports.index')->with('success', 'Report created successfully!');
    }

    /**
     * Everything on a report card except the marks and what they work out to.
     */
    private function reportAttributes(array $validated): array
    {
        return [
            'student_id' => $validated['student_id'],
            'academic_year' => $validated['academic_year'],
            'class' => $validated['class'],
            'section' => $validated['section'],
            'roll_number' => $validated['roll_number'],
            'attendance_days' => $validated['attendance_days'] ?? null,
            'total_days' => $validated['total_days'] ?? null,
            'remarks' => $validated['remarks'] ?? null,
            'class_response' => $validated['class_response'],
            'discipline' => $validated['discipline'],
            'leadership' => $validated['leadership'],
            'neatness' => $validated['neatness'],
            'punctuality' => $validated['punctuality'],
            'regularity' => $validated['regularity'],
            'social_conduct' => $validated['social_conduct'],
            'sports_game' => $validated['sports_game'],
        ];
    }

    public function show(StudentReport $report)
    {
        $this->authorizeReport($report);

        return view('reports.show', $this->reportCard($report));
    }

    /**
     * What a report card needs to render, on screen or as a PDF.
     *
     * Subjects come from the marks actually recorded, not from the currently
     * active list - deactivating a subject used to make it vanish from every
     * report card ever issued.
     */
    private function reportCard(StudentReport $report): array
    {
        $report->load('student.school');

        $marks = $report->marks()->with('subject')->get();

        return [
            'report' => $report,
            'marks' => $marks->groupBy(['subject_id', 'exam_type']),
            'subjects' => $marks->pluck('subject')->filter()->unique('id')->sortBy('name')->values(),
            'gradeSystem' => GradeSystem::active()->ordered()->get(),
        ];
    }

    public function edit(StudentReport $report)
    {
        $this->authorizeReport($report);

        // The subjects on the form are the active ones plus any this card
        // already has marks for. Saving replaces every mark with what the form
        // sends, so a subject left off the form is a subject whose marks vanish
        // - which is what deactivating one used to do to every old card.
        $subjects = Subject::where('is_active', true)
            ->orWhereIn('id', $report->marks()->select('subject_id'))
            ->orderBy('name')
            ->get();

        $marks = $report->marks;

        $existingMarks = [];
        foreach ($marks as $mark) {
            $existingMarks[$mark->subject_id][$mark->exam_type] = [
                'theory' => $mark->theory_marks,
                'practical' => $mark->practical_marks
            ];
        }

        return view('reports.edit', compact('report', 'subjects', 'existingMarks'));
    }

    public function update(Request $request, StudentReport $report)
    {
        $this->authorizeReport($report);

        $validated = $this->validateReport($request, $report);

        $this->authorizeStudent((int) $validated['student_id']);

        // The class the report used to be in also needs its ranks redone if the
        // student or the year changed.
        $previous = $report->replicate()->setRawAttributes($report->getOriginal());

        DB::transaction(function () use ($report, $validated) {
            // Marks hang off the report now, so replacing them cannot touch
            // another report card's marks the way keying on student and year did.
            $report->marks()->delete();

            $report->update($this->reportAttributes($validated));
            $report->refresh();
            $report->update($this->grader->grade($report, $validated['marks']));
        });

        $this->grader->recalculatePositions($report);
        $this->grader->recalculatePositions($previous);

        return redirect()->route('reports.index')->with('success', 'Report updated successfully!');
    }

    public function downloadPdf(StudentReport $report)
    {
        $this->authorizeReport($report);

        $pdf = Pdf::loadView('reports.pdf', $this->reportCard($report));

        return $pdf->download('report-'.$report->student->name.'-'.$report->academic_year.'.pdf');
    }

    public function destroy(StudentReport $report)
    {
        $this->authorizeReport($report);

        // Who may delete is decided by the reports.delete permission on the
        // route, not by being an administrator.
        $student = $report->student;
        $academicYear = $report->academic_year;

        // The model removes this report's marks with it, and only this report's -
        // keying on student and year used to empty other report cards too.
        $report->delete();

        if ($student) {
            $this->grader->recalculatePositions(
                (new StudentReport())->forceFill([
                    'student_id' => $student->id,
                    'academic_year' => $academicYear,
                    'class' => $report->class,
                    'section' => $report->section,
                ])
            );
        }

        return redirect()->route('reports.index')->with(
            'success',
            'Report for '.($student->name ?? 'the student').' ('.$academicYear.') has been deleted.'
        );
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\GradeSystem;
use App\Models\Student;
use App\Models\StudentMark;
use App\Models\StudentReport;
use App\Models\Subject;
use App\Support\ReportGrader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * The ledger: one subject, one exam, every student in a class on one page.
 *
 * The report form takes one student and asks for every subject in every
 * terminal - sixty-odd boxes - which is not how marks arrive at a school. A
 * teacher finishes marking one subject's papers for one class and wants to
 * enter that. Each mark saved here lands on the student's report card for the
 * year, creating the card if they have none yet, so the two screens are two
 * ways into the same records.
 */
class MarksEntryController extends Controller
{
    public const EXAMS = [
        'first_terminal' => 'First Terminal',
        'second_terminal' => 'Second Terminal',
        'final_terminal' => 'Final Terminal',
        'pre_board' => 'Pre-Board',
    ];

    public function __construct(private readonly ReportGrader $grader)
    {
    }

    public function index(Request $request)
    {
        $schools = $this->selectableSchools();
        $filters = $this->filters($request, $schools->pluck('id')->all());

        $data = [
            'schools' => $schools,
            'classMap' => $this->classMap($schools->pluck('id')->all()),
            'subjects' => Subject::where('is_active', true)->orderBy('name')->get(),
            'exams' => self::EXAMS,
            'years' => StudentReport::distinct()->orderByDesc('academic_year')->pluck('academic_year'),
            'filters' => $filters,
            'ledger' => null,
        ];

        if ($filters['complete']) {
            $data['ledger'] = $this->ledger($filters);
        }

        return view('marks.index', $data);
    }

    /**
     * What the page is filtered to. Nothing here is trusted yet - a school id
     * outside the user's reach simply does not select, and the ledger is only
     * built once every part is present.
     */
    private function filters(Request $request, array $allowedSchools): array
    {
        $schoolId = (int) $request->input('school_id');

        if (! in_array($schoolId, $allowedSchools, true)) {
            // One school to choose from means it is chosen.
            $schoolId = count($allowedSchools) === 1 ? $allowedSchools[0] : null;
        }

        $filters = [
            'school_id' => $schoolId,
            'class' => trim((string) $request->input('class')) ?: null,
            'section' => trim((string) $request->input('section')) ?: null,
            'academic_year' => preg_match('/^\d{4}$/', (string) $request->input('academic_year'))
                ? $request->input('academic_year') : null,
            'subject_id' => (int) $request->input('subject_id') ?: null,
            'exam_type' => array_key_exists($request->input('exam_type', ''), self::EXAMS)
                ? $request->input('exam_type') : null,
        ];

        $filters['complete'] = ! in_array(null, $filters, true);

        return $filters;
    }

    /**
     * Every class and section that has students, per school, for the cascading
     * pickers. Small enough to ship whole: a few hundred entries at the most.
     */
    private function classMap(array $schoolIds): array
    {
        $map = [];

        $rows = Student::whereIn('school_id', $schoolIds)
            ->select('school_id', 'class', 'section')
            ->distinct()
            ->orderBy('class')
            ->orderBy('section')
            ->get();

        foreach ($rows as $row) {
            $map[$row->school_id][$row->class][] = $row->section;
        }

        return $map;
    }

    /**
     * The rows to enter: students now in the class, plus any who have moved
     * on but hold a report card for that class and year, so last year's
     * ledger can still be completed.
     */
    private function ledger(array $filters): ?array
    {
        $subject = Subject::find($filters['subject_id']);

        if (! $subject) {
            return null;
        }

        $students = Student::where('school_id', $filters['school_id'])
            ->where(function ($q) use ($filters) {
                $q->where(fn ($now) => $now
                    ->where('class', $filters['class'])
                    ->where('section', $filters['section'])
                    ->where('is_active', true))
                ->orWhereHas('reports', fn ($then) => $then
                    ->where('academic_year', $filters['academic_year'])
                    ->where('class', $filters['class'])
                    ->where('section', $filters['section']));
            })
            ->orderBy('roll_number')
            ->orderBy('name')
            ->get();

        $marks = StudentMark::where('subject_id', $subject->id)
            ->where('exam_type', $filters['exam_type'])
            ->whereIn('student_id', $students->pluck('id'))
            ->whereHas('report', fn ($q) => $q->where('academic_year', $filters['academic_year']))
            ->get()
            ->keyBy('student_id');

        return [
            'subject' => $subject,
            'exam' => self::EXAMS[$filters['exam_type']],
            'students' => $students,
            'marks' => $marks,
            // The scale, so the page can show the grade as the mark is typed.
            'bands' => GradeSystem::active()->ordered()->get()
                ->map(fn ($band) => [
                    'from' => $band->marks_from,
                    'to' => $band->marks_to,
                    'letter' => $band->letter_grade,
                    'failing' => $band->is_failing,
                ])->values(),
        ];
    }

    public function store(Request $request)
    {
        $filters = $request->validate([
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'class' => ['required', 'string', 'max:50'],
            'section' => ['required', 'string', 'max:10'],
            'academic_year' => ['required', 'regex:/^\d{4}$/'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'exam_type' => ['required', Rule::in(array_keys(self::EXAMS))],
        ], [
            'academic_year.regex' => 'Enter the academic year as four digits, e.g. 2081.',
        ]);

        $this->authorizeSchool((int) $filters['school_id']);

        $subject = Subject::findOrFail($filters['subject_id']);
        $limit = (float) $subject->full_marks;

        $validated = $request->validate([
            'marks' => ['present', 'array'],
            'marks.*.th' => ['nullable', 'numeric', 'min:0', 'max:'.$limit],
            'marks.*.pr' => ['nullable', 'numeric', 'min:0', 'max:'.$limit],
        ], [
            'marks.*.*.max' => 'A mark cannot be more than the subject\'s full marks ('.$subject->full_marks.').',
            'marks.*.*.min' => 'A mark cannot be negative.',
            'marks.*.*.numeric' => 'A mark has to be a number.',
        ]);

        // Only students of this school can be written to, whatever ids the
        // form carried.
        $students = Student::where('school_id', $filters['school_id'])
            ->whereIn('id', array_keys($validated['marks']))
            ->get()
            ->keyBy('id');

        foreach ($validated['marks'] as $studentId => $parts) {
            if (! $students->has((int) $studentId)) {
                throw ValidationException::withMessages([
                    'marks' => 'One of the students is not in this school. Reload the page and try again.',
                ]);
            }

            $total = (float) ($parts['th'] ?? 0) + (float) ($parts['pr'] ?? 0);

            if ($total > $limit) {
                throw ValidationException::withMessages([
                    "marks.{$studentId}.th" => $students[$studentId]->name.': theory and practical together cannot be more than '
                        .$subject->full_marks.'.',
                ]);
            }
        }

        $saved = 0;
        $cleared = 0;
        $created = 0;

        DB::transaction(function () use ($validated, $filters, $students, $subject, &$saved, &$cleared, &$created) {
            foreach ($validated['marks'] as $studentId => $parts) {
                $student = $students[(int) $studentId];
                $theory = $parts['th'] ?? null;
                $practical = $parts['pr'] ?? null;

                $report = StudentReport::where('student_id', $student->id)
                    ->where('academic_year', $filters['academic_year'])
                    ->first();

                // Both boxes empty means no mark: whatever was there is removed.
                if ($theory === null && $practical === null) {
                    if ($report && $report->marks()
                        ->where('subject_id', $subject->id)
                        ->where('exam_type', $filters['exam_type'])
                        ->delete()) {
                        $report->update($this->grader->summarise($report));
                        $cleared++;
                    }

                    continue;
                }

                if (! $report) {
                    // The card starts with the class it is being filed under
                    // and the behaviour grades every new card starts on.
                    $report = StudentReport::create([
                        'student_id' => $student->id,
                        'academic_year' => $filters['academic_year'],
                        'class' => $filters['class'],
                        'section' => $filters['section'],
                        'roll_number' => $student->roll_number,
                        'final_gpa' => 0,
                        'final_grade' => '',
                        'issue_date' => now(),
                    ]);
                    $created++;
                }

                StudentMark::updateOrCreate(
                    [
                        'student_report_id' => $report->id,
                        'subject_id' => $subject->id,
                        'exam_type' => $filters['exam_type'],
                    ],
                    $this->grader->markAttributes($report, $subject, $subject->id, $filters['exam_type'], $theory, $practical)
                );

                $report->update($this->grader->summarise($report));
                $saved++;
            }
        });

        // Every card touched is in the same class and year, so one pass ranks
        // them all.
        if (($saved || $cleared) && $students->isNotEmpty()) {
            $this->grader->recalculatePositions((new StudentReport())->forceFill([
                'student_id' => $students->first()->id,
                'academic_year' => $filters['academic_year'],
                'class' => $filters['class'],
                'section' => $filters['section'],
            ]));
        }

        $parts = [];
        $parts[] = $saved === 1 ? 'Marks saved for 1 student.' : "Marks saved for {$saved} students.";
        if ($created) {
            $parts[] = $created === 1 ? '1 new report card was created.' : "{$created} new report cards were created.";
        }
        if ($cleared) {
            $parts[] = $cleared === 1 ? '1 mark was cleared.' : "{$cleared} marks were cleared.";
        }

        return redirect()->route('marks.index', $filters)->with('success', implode(' ', $parts));
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PicksClass;
use App\Models\GradeSystem;
use App\Models\Student;
use App\Models\StudentMark;
use App\Models\StudentReport;
use App\Models\Subject;
use App\Support\ReportGrader;
use App\Support\TableResponse;
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
    use PicksClass;

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
            'filters' => $filters,
            'ledger' => null,
        ];

        if ($filters['complete']) {
            $data['ledger'] = $this->ledger($filters);
        }

        return view('marks.index', $data);
    }

    /**
     * What the page is filtered to: the class, plus the subject and exam.
     * The ledger is only built once every part is present.
     */
    private function filters(Request $request, array $allowedSchools): array
    {
        $filters = $this->classFilters($request, $allowedSchools) + [
            'subject_id' => (int) $request->input('subject_id') ?: null,
            'exam_type' => array_key_exists($request->input('exam_type', ''), self::EXAMS)
                ? $request->input('exam_type') : null,
        ];

        $filters['complete'] = ! in_array(null, $filters, true);

        return $filters;
    }

    /**
     * What the ledger card needs before the grid has fetched a row: the
     * subject, the exam, how many students there are and how many have a
     * mark, and the scale so the grade can be shown as a mark is typed.
     */
    private function ledger(array $filters): ?array
    {
        $subject = Subject::find($filters['subject_id']);

        if (! $subject) {
            return null;
        }

        return [
            'subject' => $subject,
            'exam' => self::EXAMS[$filters['exam_type']],
            'students' => $this->ledgerStudents($filters)->count(),
            'entered' => $this->ledgerStudents($filters)->whereNotNull('m.id')->count(),
            'bands' => GradeSystem::active()->ordered()->get()
                ->map(fn ($band) => [
                    'from' => $band->marks_from,
                    'to' => $band->marks_to,
                    'letter' => $band->letter_grade,
                    'failing' => $band->is_failing,
                ])->values(),
        ];
    }

    /**
     * The rows to enter: students now in the class, plus any who have moved
     * on but hold a report card for that class and year, so last year's
     * ledger can still be completed. Each row carries its mark for this
     * subject and exam, if there is one, from a left join on the card.
     */
    private function ledgerStudents(array $filters)
    {
        return Student::query()
            ->leftJoin('student_reports as r', function ($join) use ($filters) {
                $join->on('r.student_id', '=', 'students.id')
                    ->where('r.academic_year', '=', $filters['academic_year']);
            })
            ->leftJoin('student_marks as m', function ($join) use ($filters) {
                $join->on('m.student_report_id', '=', 'r.id')
                    ->where('m.subject_id', '=', $filters['subject_id'])
                    ->where('m.exam_type', '=', $filters['exam_type']);
            })
            ->where('students.school_id', $filters['school_id'])
            ->where(function ($q) use ($filters) {
                $q->where(fn ($now) => $now
                    ->where('students.class', $filters['class'])
                    ->where('students.section', $filters['section'])
                    ->where('students.is_active', true))
                ->orWhere(fn ($then) => $then
                    ->where('r.class', $filters['class'])
                    ->where('r.section', $filters['section']));
            })
            ->select('students.*')
            ->addSelect([
                'm.id as mark_id', 'm.theory_marks', 'm.practical_marks',
                'm.total_marks', 'm.letter_grade', 'm.grade_point',
            ]);
    }

    /**
     * JSON rows for the grid on the ledger. The same filters as the page,
     * plus whatever the grid sends to page, search and sort them.
     */
    public function rows(Request $request)
    {
        $filters = $this->filters($request, $this->selectableSchools()->pluck('id')->all());

        if (! $filters['complete']) {
            return response()->json(['success' => false, 'message' => 'Choose a school, class, section, year, subject and exam first.']);
        }

        // Only the filters the page validated; a bare id in the query string is
        // not enough to see another school's class.
        $this->authorizeSchool($filters['school_id']);

        $current = fn ($student) => $student->class === $filters['class']
            && $student->section === $filters['section']
            && (bool) $student->is_active;

        return TableResponse::make($request, $this->ledgerStudents($filters), [
            'search' => ['students.name', 'students.roll_number'],
            'filters' => [
                'name' => 'students.name',
                'roll_number' => 'students.roll_number',
                // 'entered' / 'missing' - the rows still to do in a long class.
                'entered' => fn ($q, $v) => $v === '1' ? $q->whereNotNull('m.id') : $q->whereNull('m.id'),
            ],
            'sort' => [
                'roll_number' => 'students.roll_number',
                'name' => 'students.name',
            ],
            'default' => ['students.roll_number', 'asc'],
        ], fn ($student) => [
            'id' => $student->id,
            'roll_number' => $student->roll_number,
            'name' => $student->name,
            // Holds a card for this class and year but is not in it now.
            'moved' => ! $current($student),
            'now' => trim($student->class.' '.$student->section).($student->is_active ? '' : ', no longer enrolled'),
            'th' => $student->theory_marks !== null ? $student->theory_marks + 0 : null,
            'pr' => $student->practical_marks !== null ? $student->practical_marks + 0 : null,
            'total' => $student->mark_id ? $student->total_marks + 0 : null,
            'grade' => $student->mark_id ? $student->letter_grade : null,
            'fail' => $student->mark_id ? (float) $student->grade_point <= 0 : false,
        ]);
    }

    public function store(Request $request)
    {
        $filters = $request->validate([
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'class' => ['required', 'string', 'max:50'],
            'section' => ['required', 'string', 'max:10'],
            'academic_year' => ['required', 'regex:/^\d{4}$/', 'exists:academic_years,year'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'exam_type' => ['required', Rule::in(array_keys(self::EXAMS))],
        ], [
            'academic_year.regex' => 'Enter the academic year as four digits, e.g. 2081.',
            'academic_year.exists' => 'That academic year is not in the list. Add it under Years & Classes first.',
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

        $message = implode(' ', $parts);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'saved' => $saved,
                'created' => $created,
                'cleared' => $cleared,
            ]);
        }

        return redirect()->route('marks.index', $filters)->with('success', $message);
    }
}

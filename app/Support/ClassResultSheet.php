<?php

namespace App\Support;

use App\Models\StudentMark;
use App\Models\StudentReport;
use App\Models\Subject;
use Illuminate\Support\Collection;

/**
 * One exam's result for a whole class: every student down the side, every
 * subject across the top, and each student's total, GPA, grade, result and
 * rank for that exam.
 *
 * Worked out from the marks rather than read off the report cards, because a
 * card only carries the final terminal's result and a school publishes a
 * sheet after every terminal. For the final terminal it comes out the same
 * as the cards, since it applies the same rules.
 */
class ClassResultSheet
{
    public readonly Collection $subjects;
    public readonly Collection $rows;
    public readonly array $summary;

    private GradeCalculator $calculator;

    public function __construct(
        public readonly array $filters,
        public readonly string $examLabel,
    ) {
        $this->calculator = new GradeCalculator();

        $reports = StudentReport::with('student')
            ->where('academic_year', $filters['academic_year'])
            ->where('class', $filters['class'])
            ->where('section', $filters['section'])
            ->whereHas('student', fn ($q) => $q->where('school_id', $filters['school_id']))
            ->orderBy('roll_number')
            ->get();

        $marks = StudentMark::whereIn('student_report_id', $reports->pluck('id'))
            ->where('exam_type', $filters['exam_type'])
            ->with('subject')
            ->get()
            ->groupBy('student_report_id');

        // The columns are the subjects anyone in the class sat, not the active
        // list, so a retired subject still shows on an old sheet.
        $this->subjects = $marks->flatten()
            ->pluck('subject')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();

        $this->rows = $this->rank($reports->map(fn ($report) => $this->row($report, $marks->get($report->id, collect()))));

        $this->summary = $this->summarise();
    }

    /** One student's line: a cell per subject, then the totals for the exam. */
    private function row(StudentReport $report, Collection $marks): array
    {
        $bySubject = $marks->keyBy('subject_id');

        $cells = [];
        $obtained = 0;
        $fullMarks = 0;
        $gradePoints = 0;
        $failed = false;

        foreach ($this->subjects as $subject) {
            $mark = $bySubject->get($subject->id);

            if (! $mark) {
                $cells[] = null;

                continue;
            }

            $total = (float) $mark->total_marks;
            $band = $this->calculator->forMarks($total, (float) $subject->full_marks);
            $fail = $this->calculator->fails($band, $total, $subject->pass_marks !== null ? (float) $subject->pass_marks : null);

            $cells[] = [
                'marks' => $total,
                'grade' => $band?->letter_grade ?? 'NG',
                'point' => $fail ? 0.0 : (float) $band->grade_point,
                'fail' => $fail,
            ];

            $obtained += $total;
            $fullMarks += (float) $subject->full_marks;
            $failed = $failed || $fail;
            $gradePoints += $fail ? 0 : (float) $band->grade_point;
        }

        $sat = count(array_filter($cells));

        if ($sat === 0) {
            return [
                'report' => $report,
                'cells' => $cells,
                'absent' => true,
                'obtained' => null, 'full' => null, 'percentage' => null,
                'gpa' => null, 'grade' => null, 'status' => 'ABSENT', 'passed' => false,
                'rank' => null,
            ];
        }

        $gpa = $failed ? 0.0 : round($gradePoints / $sat, 2);
        $result = $this->calculator->resultFor($gpa, $failed);

        return [
            'report' => $report,
            'cells' => $cells,
            'absent' => false,
            'obtained' => $obtained,
            'full' => $fullMarks,
            'percentage' => $fullMarks > 0 ? round($obtained / $fullMarks * 100, 2) : null,
            'gpa' => $gpa,
            'grade' => $failed
                ? ($this->calculator->scale()->firstWhere('is_failing', true)?->letter_grade ?? 'NG')
                : ($this->calculator->forGradePoint($gpa)?->letter_grade ?? 'NG'),
            'status' => $result['status'],
            'passed' => ! $failed,
            'rank' => null,
        ];
    }

    /**
     * Rank within the sheet by GPA, then by marks; equal results share a rank
     * and the next carries on from the count, the same rule the report cards
     * use. Absentees are not ranked. The rows stay in roll order.
     */
    private function rank(Collection $rows): Collection
    {
        $order = $rows->filter(fn ($row) => ! $row['absent'])
            ->sortBy([['gpa', 'desc'], ['obtained', 'desc']])
            ->values();

        $ranks = [];
        $position = 0;
        $seen = null;

        foreach ($order as $counted => $row) {
            $key = $row['gpa'].'|'.$row['obtained'];

            if ($key !== $seen) {
                $position = $counted + 1;
                $seen = $key;
            }

            $ranks[$row['report']->id] = $position;
        }

        return $rows->map(function ($row) use ($ranks) {
            $row['rank'] = $ranks[$row['report']->id] ?? null;

            return $row;
        })->values();
    }

    private function summarise(): array
    {
        $appeared = $this->rows->where('absent', false);
        $passed = $appeared->where('passed', true);

        return [
            'students' => $this->rows->count(),
            'appeared' => $appeared->count(),
            'absent' => $this->rows->count() - $appeared->count(),
            'passed' => $passed->count(),
            'failed' => $appeared->count() - $passed->count(),
            'pass_rate' => $appeared->count() ? round($passed->count() / $appeared->count() * 100, 1) : null,
            'average_gpa' => $appeared->count() ? round($appeared->avg('gpa'), 2) : null,
            'toppers' => $appeared->sortBy('rank')->take(3)->values(),
            // Per subject: how many sat it and how many passed, for the foot row.
            'subjects' => $this->subjects->map(function (Subject $subject, $index) {
                $cells = $this->rows->pluck('cells.'.$index)->filter();

                return [
                    'sat' => $cells->count(),
                    'passed' => $cells->where('fail', false)->count(),
                    'average' => $cells->count() ? round($cells->avg('marks'), 1) : null,
                ];
            })->values()->all(),
        ];
    }

    public function isEmpty(): bool
    {
        return $this->rows->isEmpty();
    }
}

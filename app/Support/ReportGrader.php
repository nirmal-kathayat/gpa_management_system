<?php

namespace App\Support;

use App\Models\Student;
use App\Models\StudentMark;
use App\Models\StudentReport;
use App\Models\Subject;

/**
 * Turns a form's worth of marks into stored marks and a graded report card, and
 * keeps class positions in step.
 *
 * It lives outside the controller so the seeder builds its demo reports exactly
 * the way the form does, rather than reimplementing the maths.
 */
class ReportGrader
{
    /** The terminals a card records, in the order they are sat. */
    public const EXAM_TYPES = ['first_terminal', 'second_terminal', 'final_terminal', 'pre_board'];

    private ?GradeCalculator $calculator = null;

    private function calculator(): GradeCalculator
    {
        return $this->calculator ??= new GradeCalculator();
    }

    /**
     * Stores every mark on the form and works out the final result.
     *
     * The scale decides the grade: a mark is turned into a percentage against
     * that subject's own full marks, and a subject counts as failed when its
     * band says so or when the mark is below the subject's pass mark. Only the
     * final terminal counts toward the GPA.
     */
    public function grade(StudentReport $report, array $marks): array
    {
        $subjects = Subject::whereIn('id', array_column($marks, 'subject_id'))->get()->keyBy('id');

        foreach ($marks as $markData) {
            $subject = $subjects->get($markData['subject_id']);

            foreach (self::EXAM_TYPES as $examType) {
                $theoryKey = $examType.'_th';
                $practicalKey = $examType.'_pr';

                if (! isset($markData[$theoryKey]) && ! isset($markData[$practicalKey])) {
                    continue;
                }

                StudentMark::create($this->markAttributes(
                    $report,
                    $subject,
                    (int) $markData['subject_id'],
                    $examType,
                    $markData[$theoryKey] ?? null,
                    $markData[$practicalKey] ?? null
                ));
            }
        }

        return $this->summarise($report);
    }

    /**
     * The stored row for one subject in one exam: the two parts, their total,
     * and the grade the scale gives that total against the subject's full
     * marks. A failed subject is worth no grade points whatever its band says.
     */
    public function markAttributes(StudentReport $report, ?Subject $subject, int $subjectId, string $examType, $theory, $practical): array
    {
        $fullMarks = (float) ($subject->full_marks ?? 100);
        $passMarks = $subject?->pass_marks !== null ? (float) $subject->pass_marks : null;

        // A part that was not entered stays null - the sheet shows a dash, and
        // the ledger shows an empty box - rather than becoming a zero mark.
        $theoryMarks = $theory === null || $theory === '' ? null : (float) $theory;
        $practicalMarks = $practical === null || $practical === '' ? null : (float) $practical;
        $totalMarks = ($theoryMarks ?? 0) + ($practicalMarks ?? 0);

        $band = $this->calculator()->forMarks($totalMarks, $fullMarks);
        $failed = $this->calculator()->fails($band, $totalMarks, $passMarks);

        return [
            'student_id' => $report->student_id,
            'student_report_id' => $report->id,
            'subject_id' => $subjectId,
            'exam_type' => $examType,
            'theory_marks' => $theoryMarks,
            'practical_marks' => $practicalMarks,
            'total_marks' => $totalMarks,
            'letter_grade' => $band?->letter_grade ?? 'NG',
            'grade_point' => $failed ? 0 : ($band?->grade_point ?? 0),
            'academic_year' => $report->academic_year,
        ];
    }

    /**
     * The card's result from the marks it holds. Read back from what is
     * stored, so a card whose marks were written one subject at a time comes
     * out the same as one saved from the full form.
     */
    public function summarise(StudentReport $report): array
    {
        $calculator = $this->calculator();

        $finals = $report->marks()
            ->where('exam_type', 'final_terminal')
            ->with('subject')
            ->get();

        $totalGradePoints = 0;
        $hasFailedSubject = false;

        foreach ($finals as $mark) {
            $fullMarks = (float) ($mark->subject->full_marks ?? 100);
            $passMarks = $mark->subject?->pass_marks !== null ? (float) $mark->subject->pass_marks : null;

            $band = $calculator->forMarks((float) $mark->total_marks, $fullMarks);

            if ($calculator->fails($band, (float) $mark->total_marks, $passMarks)) {
                $hasFailedSubject = true;
            } else {
                $totalGradePoints += $band->grade_point;
            }
        }

        // Marks arrive a subject and a terminal at a time, so a card can exist
        // before its final terminal does. It has no result yet, not a pass.
        if ($finals->isEmpty()) {
            return [
                'final_gpa' => 0.0,
                'final_grade' => '',
                'result_status' => 'PENDING',
                'result_remarks' => 'Final terminal marks have not been entered yet.',
            ];
        }

        $gpa = $hasFailedSubject ? 0.0 : round($totalGradePoints / $finals->count(), 2);

        $result = $calculator->resultFor($gpa, $hasFailedSubject);

        return [
            'final_gpa' => $gpa,
            'final_grade' => $hasFailedSubject
                ? ($calculator->scale()->firstWhere('is_failing', true)?->letter_grade ?? 'NG')
                : ($calculator->forGradePoint($gpa)?->letter_grade ?? 'NG'),
            'result_status' => $result['status'],
            'result_remarks' => $result['remarks'],
        ];
    }

    /**
     * Grades every stored mark again under the current rules and scale, then
     * the card. For when the rules change after cards have been issued.
     */
    public function regrade(StudentReport $report): array
    {
        foreach ($report->marks()->with('subject')->get() as $mark) {
            $mark->update($this->markAttributes(
                $report, $mark->subject, $mark->subject_id, $mark->exam_type, $mark->theory_marks, $mark->practical_marks
            ));
        }

        $graded = $this->summarise($report);
        $report->update($graded);

        return $graded;
    }

    /**
     * Positions are a rank within a class: same school, class, section and
     * academic year, ordered by GPA. Redone whenever a report in that group
     * changes, since one new report can move everybody below it.
     *
     * The class is the one written on the report, not the one the student is
     * in now - a promoted student's old cards stay ranked against their old
     * classmates.
     */
    public function recalculatePositions(StudentReport $report): void
    {
        $student = Student::find($report->student_id);

        if (! $student) {
            return;
        }

        $reports = StudentReport::where('academic_year', $report->academic_year)
            ->where('class', $report->class)
            ->where('section', $report->section)
            ->whereHas('student', fn ($q) => $q->where('school_id', $student->school_id))
            ->orderByDesc('final_gpa')
            ->orderBy('id')
            ->get();

        $position = 0;
        $seenGpa = null;
        $counted = 0;

        foreach ($reports as $row) {
            $counted++;

            // Equal GPAs share a position; the next one carries on from the count.
            if ((string) $row->final_gpa !== (string) $seenGpa) {
                $position = $counted;
                $seenGpa = $row->final_gpa;
            }

            if ($row->position !== $position) {
                $row->updateQuietly(['position' => $position]);
            }
        }
    }
}

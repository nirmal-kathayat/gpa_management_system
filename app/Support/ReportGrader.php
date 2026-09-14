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
                    'student_id' => $report->student_id,
                    'student_report_id' => $report->id,
                    'subject_id' => $markData['subject_id'],
                    'exam_type' => $examType,
                    'theory_marks' => $theoryMarks,
                    'practical_marks' => $practicalMarks,
                    'total_marks' => $totalMarks,
                    'letter_grade' => $band?->letter_grade ?? 'NG',
                    'grade_point' => $failed ? 0 : ($band?->grade_point ?? 0),
                    'academic_year' => $report->academic_year,
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
            'final_gpa' => $gpa,
            'final_grade' => $hasFailedSubject
                ? ($calculator->scale()->firstWhere('is_failing', true)?->letter_grade ?? 'NG')
                : ($calculator->forGradePoint($gpa)?->letter_grade ?? 'NG'),
            'result_status' => $result['status'],
            'result_remarks' => $result['remarks'],
        ];
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

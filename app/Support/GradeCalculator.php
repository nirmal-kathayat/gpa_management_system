<?php

namespace App\Support;

use App\Models\GradeSystem;
use Illuminate\Support\Collection;

/**
 * The one place that reads the grading scale.
 *
 * ReportController used to carry this logic twice, once in store() and once in
 * update(), and both copies decided things the scale is supposed to decide:
 * marks were turned into a percentage by assuming every subject is out of 200,
 * and anything under 2.0 grade points was a failure whatever the scale said.
 * Both are fixed here, in one place.
 */
class GradeCalculator
{
    /**
     * How a final GPA becomes a result line. Kept here so the report and the
     * Grade Scale screen cannot describe different rules.
     */
    public const RESULT_BANDS = [
        ['min' => 3.6, 'status' => 'PASSED WITH DISTINCTION', 'remarks' => 'Excellent performance. Keep up the good work!'],
        ['min' => 3.2, 'status' => 'PASSED WITH FIRST DIVISION', 'remarks' => 'Very good performance. Well done!'],
        ['min' => 2.8, 'status' => 'PASSED WITH SECOND DIVISION', 'remarks' => 'Good performance. Continue to improve.'],
        ['min' => 2.0, 'status' => 'PASSED WITH THIRD DIVISION', 'remarks' => 'Satisfactory performance. More effort needed.'],
        ['min' => 0.0, 'status' => 'PASSED', 'remarks' => 'Passed. More effort needed.'],
    ];

    private ?Collection $scale = null;

    /** Active bands, highest first. Loaded once per instance. */
    public function scale(): Collection
    {
        return $this->scale ??= GradeSystem::active()->ordered()->get();
    }

    public function isEmpty(): bool
    {
        return $this->scale()->isEmpty();
    }

    /**
     * The band a mark falls in. `$fullMarks` is the subject's own total, so a
     * subject out of 50 and one out of 200 are both read as a percentage.
     */
    public function forMarks(float $obtained, float $fullMarks): ?GradeSystem
    {
        if ($fullMarks <= 0) {
            return null;
        }

        return $this->forPercentage($obtained / $fullMarks * 100);
    }

    public function forPercentage(float $percentage): ?GradeSystem
    {
        $percentage = max(0, min(100, $percentage));

        return $this->scale()->first(
            fn (GradeSystem $band) => $percentage >= $band->marks_from && $percentage <= $band->marks_to
        );
    }

    /** The band a final GPA lands in, by grade point rather than percentage. */
    public function forGradePoint(float $point): ?GradeSystem
    {
        return $this->scale()
            ->sortByDesc('grade_point')
            ->first(fn (GradeSystem $band) => $point >= $band->grade_point);
    }

    /**
     * The lowest percentage that still passes, or null when every band fails
     * (or the scale is empty).
     */
    public function passMark(): ?int
    {
        return $this->scale()->filter->isPassing()->min('marks_from');
    }

    /**
     * Whether a mark is a failure: either it lands in a band marked as failing,
     * or it is below the subject's own pass mark, which can sit higher than the
     * scale's. No band at all is also a failure - nothing says it passed.
     */
    public function fails(?GradeSystem $band, float $obtained, ?float $subjectPassMarks): bool
    {
        if (! $band || $band->is_failing) {
            return true;
        }

        return $subjectPassMarks !== null && $obtained < $subjectPassMarks;
    }

    /** The result line for a final GPA. */
    public function resultFor(float $gpa, bool $failedASubject): array
    {
        if ($failedASubject) {
            return [
                'status' => 'FAILED',
                'remarks' => 'Student has failed in one or more subjects. Needs improvement.',
            ];
        }

        foreach (self::RESULT_BANDS as $band) {
            if ($gpa >= $band['min']) {
                return ['status' => $band['status'], 'remarks' => $band['remarks']];
            }
        }

        return ['status' => 'FAILED', 'remarks' => 'Needs significant improvement in studies.'];
    }

    /**
     * Percentage ranges the scale does not cover, as [from, to] pairs. An
     * uncovered mark cannot be graded, so the screen warns about these.
     */
    public function gaps(): array
    {
        $bands = $this->scale()->sortBy('marks_from')->values();
        $gaps = [];
        $cursor = 0;

        foreach ($bands as $band) {
            if ($band->marks_from > $cursor) {
                $gaps[] = [$cursor, $band->marks_from - 1];
            }

            $cursor = max($cursor, $band->marks_to + 1);
        }

        if ($cursor <= 100) {
            $gaps[] = [$cursor, 100];
        }

        return $gaps;
    }

    /** Pairs of bands whose ranges overlap, which makes grading ambiguous. */
    public function overlaps(): array
    {
        $bands = $this->scale()->sortBy('marks_from')->values();
        $overlaps = [];

        foreach ($bands as $i => $band) {
            $next = $bands[$i + 1] ?? null;

            if ($next && $next->marks_from <= $band->marks_to) {
                $overlaps[] = [$band, $next];
            }
        }

        return $overlaps;
    }
}

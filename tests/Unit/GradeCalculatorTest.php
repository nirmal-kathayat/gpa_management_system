<?php

namespace Tests\Unit;

use App\Models\GradeSystem;
use App\Support\GradeCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradeCalculatorTest extends TestCase
{
    use RefreshDatabase;

    /** The standard scale: whole-number bands with no gaps between them. */
    private function seedScale(): void
    {
        foreach ([
            ['A+', 4.0, 90, 100, false],
            ['A', 3.6, 80, 89, false],
            ['B+', 3.2, 70, 79, false],
            ['B', 2.8, 60, 69, false],
            ['C+', 2.4, 50, 59, false],
            ['C', 2.0, 40, 49, false],
            ['D', 1.6, 35, 39, false],
            ['NG', 0.0, 0, 34, true],
        ] as [$letter, $point, $from, $to, $failing]) {
            GradeSystem::create([
                'letter_grade' => $letter,
                'grade_point' => $point,
                'marks_from' => $from,
                'marks_to' => $to,
                'description' => $letter,
                'is_failing' => $failing,
                'is_active' => true,
            ]);
        }
    }

    public function test_whole_percentages_land_in_their_band(): void
    {
        $this->seedScale();
        $calculator = new GradeCalculator();

        $this->assertSame('A+', $calculator->forPercentage(100)->letter_grade);
        $this->assertSame('A+', $calculator->forPercentage(90)->letter_grade);
        $this->assertSame('A', $calculator->forPercentage(89)->letter_grade);
        $this->assertSame('B+', $calculator->forPercentage(70)->letter_grade);
        $this->assertSame('D', $calculator->forPercentage(35)->letter_grade);
        $this->assertSame('NG', $calculator->forPercentage(0)->letter_grade);
    }

    /**
     * Bands are written as whole numbers, so a fraction between two of them
     * (79.5 between 70-79 and 80-89) belongs to the lower band - it has not
     * reached 80. It used to fall through every band and be graded as nothing.
     */
    public function test_a_fraction_between_two_bands_belongs_to_the_lower_one(): void
    {
        $this->seedScale();
        $calculator = new GradeCalculator();

        $this->assertSame('B+', $calculator->forPercentage(79.5)->letter_grade);
        $this->assertSame('A', $calculator->forPercentage(89.99)->letter_grade);
        $this->assertSame('B', $calculator->forPercentage(69.5)->letter_grade);
        $this->assertSame('NG', $calculator->forPercentage(34.9)->letter_grade);
    }

    public function test_marks_are_read_against_the_subjects_own_full_marks(): void
    {
        $this->seedScale();
        $calculator = new GradeCalculator();

        // 39.75 out of 50 is 79.5%.
        $this->assertSame('B+', $calculator->forMarks(39.75, 50)->letter_grade);
        $this->assertSame('A+', $calculator->forMarks(180, 200)->letter_grade);
        $this->assertNull($calculator->forMarks(10, 0));
    }

    public function test_a_gap_the_scale_leaves_is_still_ungraded(): void
    {
        $this->seedScale();
        GradeSystem::where('letter_grade', 'B+')->delete();
        $calculator = new GradeCalculator();

        $this->assertNull($calculator->forPercentage(75));
        $this->assertSame([[70, 79]], $calculator->gaps());
    }
}

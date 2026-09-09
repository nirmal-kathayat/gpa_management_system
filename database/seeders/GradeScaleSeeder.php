<?php

namespace Database\Seeders;

use App\Models\GradeSystem;
use Illuminate\Database\Seeder;

/**
 * The NEB grading scale used by Nepali schools: eight bands on a 4.0 scale with
 * 35% as the pass mark, so anything below that is Not Graded.
 *
 * Only seeded when the scale is empty. Once a school has adjusted it, re-running
 * this leaves their scale alone.
 */
class GradeScaleSeeder extends Seeder
{
    private const SCALE = [
        ['A+', 4.0, 90, 100, 'Outstanding'],
        ['A', 3.6, 80, 89, 'Excellent'],
        ['B+', 3.2, 70, 79, 'Very Good'],
        ['B', 2.8, 60, 69, 'Good'],
        ['C+', 2.4, 50, 59, 'Satisfactory'],
        ['C', 2.0, 40, 49, 'Acceptable'],
        ['D', 1.6, 35, 39, 'Partially Acceptable'],
        ['NG', 0.0, 0, 34, 'Not Graded'],
    ];

    public function run(): void
    {
        if (GradeSystem::exists()) {
            return;
        }

        foreach (self::SCALE as [$letter, $point, $from, $to, $description]) {
            GradeSystem::create([
                'letter_grade' => $letter,
                'grade_point' => $point,
                'marks_from' => $from,
                'marks_to' => $to,
                'description' => $description,
                // Below the 35% pass mark, so it does not count toward the GPA.
                'is_failing' => $point <= 0,
                'is_active' => true,
            ]);
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GradeSystem;

class GradeSystemSeeder extends Seeder
{
    public function run()
    {
        $grades = [
            ['letter_grade' => 'A+', 'grade_point' => 4.0, 'marks_from' => 90, 'marks_to' => 100, 'remarks' => 'Outstanding'],
            ['letter_grade' => 'A', 'grade_point' => 3.6, 'marks_from' => 80, 'marks_to' => 89, 'remarks' => 'Excellent'],
            ['letter_grade' => 'B+', 'grade_point' => 3.2, 'marks_from' => 70, 'marks_to' => 79, 'remarks' => 'Very Good'],
            ['letter_grade' => 'B', 'grade_point' => 2.8, 'marks_from' => 60, 'marks_to' => 69, 'remarks' => 'Good'],
            ['letter_grade' => 'C+', 'grade_point' => 2.4, 'marks_from' => 50, 'marks_to' => 59, 'remarks' => 'Satisfactory'],
            ['letter_grade' => 'C', 'grade_point' => 2.0, 'marks_from' => 40, 'marks_to' => 49, 'remarks' => 'Acceptable'],
            ['letter_grade' => 'D+', 'grade_point' => 1.6, 'marks_from' => 35, 'marks_to' => 39, 'remarks' => 'Basic'],
            ['letter_grade' => 'D', 'grade_point' => 1.0, 'marks_from' => 32, 'marks_to' => 34, 'remarks' => 'Insufficient'],
            ['letter_grade' => 'NG', 'grade_point' => 0.0, 'marks_from' => 0, 'marks_to' => 31, 'remarks' => 'Not graded'],
        ];

        foreach ($grades as $grade) {
            GradeSystem::create($grade);
        }
    }
}

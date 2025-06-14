<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subject;

class SubjectSeeder extends Seeder
{
    public function run()
    {
        $subjects = [
            ['name' => 'English I', 'code' => 'ENG1', 'full_marks' => 100, 'pass_marks' => 32],
            ['name' => 'English II', 'code' => 'ENG2', 'full_marks' => 100, 'pass_marks' => 32],
            ['name' => 'Nepali', 'code' => 'NEP', 'full_marks' => 100, 'pass_marks' => 32],
            ['name' => 'Mathematics', 'code' => 'MATH', 'full_marks' => 100, 'pass_marks' => 32],
            ['name' => 'Science', 'code' => 'SCI', 'full_marks' => 100, 'pass_marks' => 32],
            ['name' => 'Social Studies', 'code' => 'SS', 'full_marks' => 100, 'pass_marks' => 32],
            ['name' => 'Computer', 'code' => 'COMP', 'full_marks' => 100, 'pass_marks' => 32],
            ['name' => 'Health & Physical Edu.', 'code' => 'HPE', 'full_marks' => 100, 'pass_marks' => 32],
            ['name' => 'Moral Education', 'code' => 'ME', 'full_marks' => 100, 'pass_marks' => 32],
        ];

        foreach ($subjects as $subject) {
            Subject::create($subject);
        }
    }
}

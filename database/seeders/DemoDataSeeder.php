<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\GradeSystem;
use App\Models\SchoolClass;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentMark;
use App\Models\StudentReport;
use App\Models\Subject;
use App\Support\ReportGrader;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Wipes the academic records and puts a workable set back: two schools, the
 * usual subjects, a class of students in each, and a year of report cards.
 *
 * Reports are built through ReportGrader, the same path the form takes, so the
 * GPAs, grades and class positions here are the ones the app would produce.
 *
 * Users, roles, permissions and the grading scale are left alone - they are
 * configuration, not sample data.
 */
class DemoDataSeeder extends Seeder
{
    private const SCHOOLS = [
        [
            'name' => 'Kathmandu Model School',
            'code' => 'KMS001',
            'tagline' => 'Learning for Life',
            'established' => '2045 B.S.',
            'type' => 'Institutional',
            'about' => 'Kathmandu Model School has taught the school-level curriculum in Baneshwor for four decades, with a focus on science and mathematics.',
            'address' => 'Baneshwor, Kathmandu',
            'phone' => '01-4567890',
            'email' => 'info@kmschool.edu.np',
        ],
        [
            'name' => 'Janapriya Secondary School',
            'code' => 'JSS001',
            'tagline' => 'Quality Education for a Better Tomorrow',
            'established' => '2008 B.S.',
            'type' => 'Community',
            'about' => 'Janapriya Secondary School is committed to quality education and the all-round development of its students in Simalchaur, Pokhara.',
            'address' => 'Simalchaur, Kaski',
            'phone' => '061-520145',
            'email' => 'info@janapriya.edu.np',
        ],
    ];

    /** name, code, full marks, pass marks. Theory-only subjects have no practical. */
    private const SUBJECTS = [
        ['Nepali', 'NEP', 100, 35],
        ['English', 'ENG', 100, 35],
        ['Mathematics', 'MATH', 100, 35],
        ['Science', 'SCI', 100, 35],
        ['Social Studies', 'SOC', 100, 35],
        ['Health and Physical Education', 'HPE', 100, 35],
        ['Computer Science', 'COMP', 50, 18],
        ['Occupation, Business and Technology', 'OBT', 50, 18],
    ];

    /** name, gender, class, section, roll, and how well they do (0-1). */
    private const STUDENTS = [
        ['Anjali Gurung', 'Female', '10', 'A', 1, 0.93],
        ['Bikash Thapa', 'Male', '10', 'A', 2, 0.78],
        ['Sita Sharma', 'Male', '10', 'A', 3, 0.62],
        ['Nirajan Adhikari', 'Male', '10', 'A', 4, 0.45],
        ['Puja Shrestha', 'Female', '10', 'A', 5, 0.31],
        ['Sabin Rai', 'Male', '10', 'B', 1, 0.85],
        ['Manisha Karki', 'Female', '10', 'B', 2, 0.70],
        ['Rohit Magar', 'Male', '9', 'A', 1, 0.88],
        ['Sunita Bhandari', 'Female', '9', 'A', 2, 0.66],
        ['Kiran Tamang', 'Male', '9', 'A', 3, 0.52],
    ];

    private const YEARS = ['2080', '2081'];

    public function run(): void
    {
        if (GradeSystem::doesntExist()) {
            $this->call(GradeScaleSeeder::class);
        }

        DB::transaction(function () {
            StudentMark::query()->delete();
            StudentReport::query()->delete();
            Student::query()->delete();
            Subject::query()->delete();
            School::query()->delete();

            $schools = collect(self::SCHOOLS)->map(fn ($school) => School::create($school));

            // The years the demo covers, the last one current; and the classes
            // each demo school runs, from the students it is about to get.
            foreach (self::YEARS as $year) {
                AcademicYear::firstOrCreate(['year' => $year])->update(['is_current' => $year === end(self::YEARS)]);
            }
            AcademicYear::whereNotIn('year', self::YEARS)->update(['is_current' => false]);

            $sections = collect(self::STUDENTS)->groupBy(2)->map(fn ($rows) => $rows->pluck(3)->unique()->values()->all());

            foreach ($schools as $school) {
                foreach ($sections as $class => $classSections) {
                    SchoolClass::create([
                        'school_id' => $school->id,
                        'name' => (string) $class,
                        'sections' => $classSections,
                        'sort_order' => is_numeric($class) ? (int) $class : 100,
                    ]);
                }
            }

            $subjects = collect(self::SUBJECTS)->map(fn ($subject) => Subject::create([
                'name' => $subject[0],
                'code' => $subject[1],
                'full_marks' => $subject[2],
                'pass_marks' => $subject[3],
                'is_active' => true,
            ]));

            $grader = new ReportGrader();

            foreach ($schools as $schoolIndex => $school) {
                foreach (self::STUDENTS as $index => [$name, $gender, $class, $section, $roll, $ability]) {
                    $student = Student::create([
                        'name' => $name,
                        'school_id' => $school->id,
                        'class' => $class,
                        'section' => $section,
                        'roll_number' => $roll,
                        'symbol_number' => sprintf('%s-%s-%03d', $school->code, $class, $roll),
                        'gender' => $gender,
                        'date_of_birth' => now()->subYears(15)->subDays($index * 37)->toDateString(),
                        'date_of_admission' => now()->subYears(4)->toDateString(),
                        'father_name' => explode(' ', $name)[1].' Bahadur',
                        'mother_name' => explode(' ', $name)[1].' Devi',
                        'guardian_phone' => '98'.str_pad((string) (10000000 + $index * 7 + $schoolIndex), 8, '0', STR_PAD_LEFT),
                        'address' => $school->address,
                        'is_active' => true,
                    ]);

                    // The last year is the class the student is in now; each
                    // earlier year's card was issued one class lower.
                    $latest = count(self::YEARS) - 1;

                    foreach (self::YEARS as $yearIndex => $year) {
                        $this->buildReport($grader, $student, $subjects, $year, $ability + ($yearIndex * 0.04), $index,
                            (string) max(1, (int) $class - ($latest - $yearIndex)));
                    }
                }
            }
        });
    }

    /**
     * Builds one report card the way the form does: create it, grade its marks,
     * write the totals back.
     */
    private function buildReport(ReportGrader $grader, Student $student, $subjects, string $year, float $ability, int $seed, string $class): void
    {
        $marks = [];

        foreach ($subjects as $offset => $subject) {
            // Spread the marks a little per subject so no two look identical.
            $swing = (($seed * 13 + $offset * 7) % 17 - 8) / 100;
            $share = max(0.05, min(1.0, $ability + $swing));

            $practical = $subject->full_marks >= 100 ? 25 : 0;
            $theoryFull = $subject->full_marks - $practical;

            $marks[] = [
                'subject_id' => $subject->id,
                'first_terminal_th' => round($theoryFull * max(0.05, $share - 0.05)),
                'first_terminal_pr' => $practical ? round($practical * $share) : null,
                'second_terminal_th' => round($theoryFull * $share),
                'second_terminal_pr' => $practical ? round($practical * $share) : null,
                'final_terminal_th' => round($theoryFull * $share),
                'final_terminal_pr' => $practical ? round($practical * min(1.0, $share + 0.05)) : null,
                'pre_board_th' => round($theoryFull * $share),
                'pre_board_pr' => $practical ? round($practical * $share) : null,
            ];
        }

        $grades = ['A', 'A', 'B', 'A', 'B', 'A', 'B', 'A'];

        $report = StudentReport::create([
            'student_id' => $student->id,
            'academic_year' => $year,
            'class' => $class,
            'section' => $student->section,
            'roll_number' => $student->roll_number,
            'final_gpa' => 0,
            'final_grade' => '',
            'attendance_days' => 200 + ($seed % 15),
            'total_days' => 220,
            'remarks' => 'Keep working steadily.',
            'class_response' => $grades[$seed % 8],
            'discipline' => $grades[($seed + 1) % 8],
            'leadership' => $grades[($seed + 2) % 8],
            'neatness' => $grades[($seed + 3) % 8],
            'punctuality' => $grades[($seed + 4) % 8],
            'regularity' => $grades[($seed + 5) % 8],
            'social_conduct' => $grades[($seed + 6) % 8],
            'sports_game' => $grades[($seed + 7) % 8],
            'issue_date' => now(),
        ]);

        $report->update($grader->grade($report, $marks));
        $grader->recalculatePositions($report);
    }
}

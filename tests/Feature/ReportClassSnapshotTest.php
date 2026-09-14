<?php

namespace Tests\Feature;

use App\Models\GradeSystem;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentReport;
use App\Models\Subject;
use App\Models\User;
use App\Support\ReportGrader;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpAcademics;
use Tests\TestCase;

/**
 * A report card keeps the class it was issued for, however the student's own
 * record changes afterwards.
 */
class ReportClassSnapshotTest extends TestCase
{
    use RefreshDatabase, SetsUpAcademics;

    private School $school;
    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        GradeSystem::create(['letter_grade' => 'A', 'grade_point' => 3.6, 'marks_from' => 80, 'marks_to' => 100, 'description' => 'A', 'is_failing' => false, 'is_active' => true]);
        GradeSystem::create(['letter_grade' => 'B', 'grade_point' => 2.8, 'marks_from' => 50, 'marks_to' => 79, 'description' => 'B', 'is_failing' => false, 'is_active' => true]);
        GradeSystem::create(['letter_grade' => 'NG', 'grade_point' => 0, 'marks_from' => 0, 'marks_to' => 49, 'description' => 'NG', 'is_failing' => true, 'is_active' => true]);

        $this->school = School::create(['name' => 'Test School', 'address' => 'Kathmandu']);
        $this->setUpAcademics($this->school);
        $this->subject = Subject::create(['name' => 'Maths', 'code' => 'MAT', 'full_marks' => 100, 'pass_marks' => 35, 'is_active' => true]);
    }

    private function student(string $name, string $class, int $roll): Student
    {
        return Student::create([
            'name' => $name, 'class' => $class, 'section' => 'A', 'roll_number' => $roll,
            'school_id' => $this->school->id, 'is_active' => true,
        ]);
    }

    private function issue(Student $student, string $year, string $class, float $mark): StudentReport
    {
        $grader = new ReportGrader();

        $report = StudentReport::create([
            'student_id' => $student->id, 'academic_year' => $year,
            'class' => $class, 'section' => 'A', 'roll_number' => $student->roll_number,
            'final_gpa' => 0, 'final_grade' => '', 'issue_date' => now(),
        ]);
        $report->update($grader->grade($report, [
            ['subject_id' => $this->subject->id, 'final_terminal_th' => $mark],
        ]));
        $grader->recalculatePositions($report);

        return $report->fresh();
    }

    public function test_a_promoted_student_is_still_ranked_with_their_old_classmates(): void
    {
        $ram = $this->student('Ram', '9', 1);
        $sita = $this->student('Sita', '9', 2);

        $ramOld = $this->issue($ram, '2080', '9', 90);
        $sitaOld = $this->issue($sita, '2080', '9', 60);

        // Ram moves up; Sita repeats the year.
        $ram->update(['class' => '10']);
        $ramNew = $this->issue($ram, '2081', '10', 70);

        $this->assertSame('9', $ramOld->fresh()->class);
        $this->assertSame(1, $ramOld->fresh()->position);
        $this->assertSame(2, $sitaOld->fresh()->position);

        // Alone in class 10 for 2081, so first there; class 9's ranks untouched.
        $this->assertSame(1, $ramNew->position);
        (new ReportGrader())->recalculatePositions($sitaOld);
        $this->assertSame(2, $sitaOld->fresh()->position);
    }

    public function test_the_form_writes_the_class_onto_the_card(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::create(['name' => 'Admin', 'username' => 'admin', 'email' => 'a@b.c', 'password' => 'secret123', 'is_active' => true]);
        $admin->assignRole('admin');

        $student = $this->student('Hari', '10', 7);

        $response = $this->actingAs($admin)->post(route('reports.store'), [
            'student_id' => $student->id,
            'academic_year' => '2080',
            'class' => '9',
            'section' => 'B',
            'roll_number' => 12,
            'marks' => [['subject_id' => $this->subject->id, 'final_terminal_th' => 80]],
            'class_response' => 'A', 'discipline' => 'A', 'leadership' => 'A', 'neatness' => 'A',
            'punctuality' => 'A', 'regularity' => 'A', 'social_conduct' => 'A', 'sports_game' => 'B',
        ]);

        $response->assertRedirect(route('reports.index'));
        $this->assertDatabaseHas('student_reports', [
            'student_id' => $student->id, 'academic_year' => '2080',
            'class' => '9', 'section' => 'B', 'roll_number' => 12,
        ]);
    }

    public function test_the_class_is_required(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::create(['name' => 'Admin', 'username' => 'admin', 'email' => 'a@b.c', 'password' => 'secret123', 'is_active' => true]);
        $admin->assignRole('admin');

        $student = $this->student('Hari', '10', 7);

        $this->actingAs($admin)->from(route('reports.create'))->post(route('reports.store'), [
            'student_id' => $student->id,
            'academic_year' => '2080',
            'marks' => [['subject_id' => $this->subject->id, 'final_terminal_th' => 80]],
            'class_response' => 'A', 'discipline' => 'A', 'leadership' => 'A', 'neatness' => 'A',
            'punctuality' => 'A', 'regularity' => 'A', 'social_conduct' => 'A', 'sports_game' => 'B',
        ])->assertSessionHasErrors(['class', 'section', 'roll_number']);
    }
}

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
use Tests\TestCase;

/**
 * Deactivating a subject stops it appearing on new report cards; it must not
 * strip it from the ones already issued when they are next edited.
 */
class ReportEditKeepsInactiveSubjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_editing_a_report_keeps_marks_for_a_subject_deactivated_since(): void
    {
        GradeSystem::create(['letter_grade' => 'A', 'grade_point' => 3.6, 'marks_from' => 0, 'marks_to' => 100, 'description' => 'A', 'is_failing' => false, 'is_active' => true]);
        $this->seed(RolePermissionSeeder::class);
        $admin = User::create(['name' => 'Admin', 'username' => 'admin', 'email' => 'a@b.c', 'password' => 'secret123', 'is_active' => true]);
        $admin->assignRole('admin');

        $school = School::create(['name' => 'Test School', 'address' => 'Kathmandu']);
        $student = Student::create(['name' => 'Hari', 'class' => '9', 'section' => 'A', 'roll_number' => 1, 'school_id' => $school->id, 'is_active' => true]);
        $maths = Subject::create(['name' => 'Maths', 'code' => 'MAT', 'full_marks' => 100, 'pass_marks' => 35, 'is_active' => true]);
        $moral = Subject::create(['name' => 'Moral Education', 'code' => 'MOR', 'full_marks' => 50, 'pass_marks' => 18, 'is_active' => true]);

        $report = StudentReport::create([
            'student_id' => $student->id, 'academic_year' => '2080', 'class' => '9', 'section' => 'A', 'roll_number' => 1,
            'final_gpa' => 0, 'final_grade' => '', 'issue_date' => now(),
        ]);
        $report->update((new ReportGrader())->grade($report, [
            ['subject_id' => $maths->id, 'final_terminal_th' => 80],
            ['subject_id' => $moral->id, 'final_terminal_th' => 40],
        ]));

        // The subject is retired after the card was issued.
        $moral->update(['is_active' => false]);

        // The edit form still lists it, so its marks are posted back...
        $this->actingAs($admin)->get(route('reports.edit', $report))
            ->assertOk()
            ->assertSee('Moral Education')
            ->assertSee('Inactive');

        // ...and the save keeps them.
        $this->actingAs($admin)->put(route('reports.update', $report), [
            'student_id' => $student->id, 'academic_year' => '2080',
            'class' => '9', 'section' => 'A', 'roll_number' => 1,
            'marks' => [
                ['subject_id' => $maths->id, 'final_terminal_th' => 85],
                ['subject_id' => $moral->id, 'final_terminal_th' => 40],
            ],
            'class_response' => 'A', 'discipline' => 'A', 'leadership' => 'A', 'neatness' => 'A',
            'punctuality' => 'A', 'regularity' => 'A', 'social_conduct' => 'A', 'sports_game' => 'B',
        ])->assertRedirect(route('reports.index'));

        $this->assertDatabaseHas('student_marks', ['student_report_id' => $report->id, 'subject_id' => $moral->id, 'total_marks' => 40]);
        $this->assertDatabaseHas('student_marks', ['student_report_id' => $report->id, 'subject_id' => $maths->id, 'total_marks' => 85]);
        $this->assertSame(2, $report->marks()->count());
    }

    public function test_a_new_report_does_not_offer_an_inactive_subject(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::create(['name' => 'Admin', 'username' => 'admin', 'email' => 'a@b.c', 'password' => 'secret123', 'is_active' => true]);
        $admin->assignRole('admin');

        Subject::create(['name' => 'Maths', 'code' => 'MAT', 'full_marks' => 100, 'pass_marks' => 35, 'is_active' => true]);
        Subject::create(['name' => 'Moral Education', 'code' => 'MOR', 'full_marks' => 50, 'pass_marks' => 18, 'is_active' => false]);

        $this->actingAs($admin)->get(route('reports.create'))
            ->assertOk()
            ->assertSee('Maths')
            ->assertDontSee('Moral Education');
    }
}

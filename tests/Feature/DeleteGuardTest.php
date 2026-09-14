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
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * What a delete is allowed to take with it.
 */
class DeleteGuardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private School $school;
    private Subject $subject;
    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        GradeSystem::create(['letter_grade' => 'A', 'grade_point' => 3.6, 'marks_from' => 0, 'marks_to' => 100, 'description' => 'A', 'is_failing' => false, 'is_active' => true]);

        $this->admin = User::create(['name' => 'Admin', 'username' => 'admin', 'email' => 'a@b.c', 'password' => 'secret123', 'is_active' => true]);
        $this->admin->assignRole('admin');

        $this->school = School::create(['name' => 'Test School', 'address' => 'Kathmandu']);
        $this->subject = Subject::create(['name' => 'Maths', 'code' => 'MAT', 'full_marks' => 100, 'pass_marks' => 35, 'is_active' => true]);
        $this->student = Student::create(['name' => 'Hari', 'class' => '9', 'section' => 'A', 'roll_number' => 1, 'school_id' => $this->school->id, 'is_active' => true]);
    }

    private function issueReport(): StudentReport
    {
        $report = StudentReport::create([
            'student_id' => $this->student->id, 'academic_year' => '2080', 'class' => '9', 'section' => 'A', 'roll_number' => 1,
            'final_gpa' => 0, 'final_grade' => '', 'issue_date' => now(),
        ]);
        $report->update((new ReportGrader())->grade($report, [
            ['subject_id' => $this->subject->id, 'final_terminal_th' => 80],
        ]));

        return $report;
    }

    public function test_a_subject_on_a_report_card_cannot_be_deleted(): void
    {
        $report = $this->issueReport();

        $this->actingAs($this->admin)->delete(route('subjects.destroy', $this->subject))
            ->assertRedirect(route('subjects.index'))
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'Maths is on 1 report card')
                && str_contains($message, 'Deactivate it instead'));

        $this->assertDatabaseHas('subjects', ['id' => $this->subject->id]);
        $this->assertSame(1, $report->marks()->count());
    }

    public function test_an_unused_subject_can_be_deleted(): void
    {
        $this->actingAs($this->admin)->delete(route('subjects.destroy', $this->subject))
            ->assertRedirect(route('subjects.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('subjects', ['id' => $this->subject->id]);
    }

    public function test_deleting_a_school_reports_what_went_with_it(): void
    {
        $this->issueReport();

        $this->actingAs($this->admin)->delete(route('schools.destroy', $this->school))
            ->assertRedirect(route('schools.index'))
            ->assertSessionHas('success', 'Test School has been deleted, along with 1 student and 1 report card.');

        $this->assertDatabaseMissing('schools', ['id' => $this->school->id]);
        $this->assertDatabaseMissing('students', ['id' => $this->student->id]);
        $this->assertDatabaseCount('student_reports', 0);
        $this->assertDatabaseCount('student_marks', 0);
    }

    public function test_a_user_cannot_delete_another_school(): void
    {
        $other = School::create(['name' => 'Other School', 'address' => 'Pokhara']);

        // A role that may delete schools, held by someone who belongs to one school.
        Role::findOrCreate('principal', 'web')->givePermissionTo('schools.delete');
        $principal = User::create(['name' => 'Principal', 'username' => 'p', 'email' => 'p@b.c', 'password' => 'secret123', 'is_active' => true, 'school_id' => $other->id]);
        $principal->assignRole('principal');

        $this->actingAs($principal)->delete(route('schools.destroy', $this->school))->assertForbidden();
        $this->assertDatabaseHas('schools', ['id' => $this->school->id]);

        $this->actingAs($principal)->delete(route('schools.destroy', $other))->assertRedirect(route('schools.index'));
        $this->assertDatabaseMissing('schools', ['id' => $other->id]);
    }

    public function test_the_student_grid_carries_the_report_count(): void
    {
        $this->issueReport();

        $this->actingAs($this->admin)->getJson(route('students.list'))
            ->assertOk()
            ->assertJsonFragment(['name' => 'Hari', 'reports_count' => 1]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentReport;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Concerns\SetsUpAcademics;
use Tests\TestCase;

class AcademicStructureTest extends TestCase
{
    use RefreshDatabase, SetsUpAcademics;

    private User $admin;
    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->admin = User::create(['name' => 'Admin', 'username' => 'admin', 'email' => 'a@b.c', 'password' => 'secret123', 'is_active' => true]);
        $this->admin->assignRole('admin');

        $this->school = School::create(['name' => 'Test School', 'address' => 'Kathmandu']);
        $this->setUpAcademics($this->school);
    }

    public function test_years_are_added_made_current_and_removed_only_when_unused(): void
    {
        $this->actingAs($this->admin)->post(route('structure.years.store'), ['year' => '2083', 'make_current' => 1])
            ->assertSessionHas('success', 'Academic year 2083 added and set as current.');
        $this->assertSame('2083', AcademicYear::current());
        $this->assertSame(1, AcademicYear::where('is_current', true)->count());

        $y2081 = AcademicYear::where('year', '2081')->first();
        $this->actingAs($this->admin)->put(route('structure.years.current', $y2081));
        $this->assertSame('2081', AcademicYear::current());

        $this->actingAs($this->admin)->from(route('structure.index'))->post(route('structure.years.store'), ['year' => '2081'])
            ->assertSessionHasErrors('year');
        $this->actingAs($this->admin)->from(route('structure.index'))->post(route('structure.years.store'), ['year' => '81'])
            ->assertSessionHasErrors('year');

        $student = Student::create(['name' => 'Ram', 'class' => '9', 'section' => 'A', 'roll_number' => 1, 'school_id' => $this->school->id, 'is_active' => true]);
        StudentReport::create(['student_id' => $student->id, 'academic_year' => '2080', 'class' => '9', 'section' => 'A', 'roll_number' => 1, 'final_gpa' => 3, 'final_grade' => 'B', 'issue_date' => now()]);

        $this->actingAs($this->admin)->delete(route('structure.years.destroy', AcademicYear::where('year', '2080')->first()))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('academic_years', ['year' => '2080']);

        $this->actingAs($this->admin)->delete(route('structure.years.destroy', AcademicYear::where('year', '2083')->first()))
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('academic_years', ['year' => '2083']);
    }

    public function test_classes_are_added_edited_and_guarded_by_their_students(): void
    {
        $this->actingAs($this->admin)->post(route('structure.classes.store'), [
            'school_id' => $this->school->id, 'name' => '12', 'sections' => 'A, B,  b, ,C',
        ])->assertSessionHas('success', 'Class 12 added with sections A, B, b, C.');
        $this->assertSame(['A', 'B', 'b', 'C'], SchoolClass::where('name', '12')->first()->sections);

        $this->actingAs($this->admin)->from(route('structure.index'))->post(route('structure.classes.store'), [
            'school_id' => $this->school->id, 'name' => '10', 'sections' => 'A',
        ])->assertSessionHasErrors('name');

        $ten = SchoolClass::where('school_id', $this->school->id)->where('name', '10')->first();
        $ram = Student::create(['name' => 'Ram', 'class' => '10', 'section' => 'B', 'roll_number' => 1, 'school_id' => $this->school->id, 'is_active' => true]);

        // Dropping section B, which has Ram in it, is refused.
        $this->actingAs($this->admin)->from(route('structure.index'))->put(route('structure.classes.update', $ten), [
            'school_id' => $this->school->id, 'name' => '10', 'sections' => 'A',
        ])->assertSessionHasErrors('sections');

        // Renaming the class renames the students in it.
        $this->actingAs($this->admin)->put(route('structure.classes.update', $ten), [
            'school_id' => $this->school->id, 'name' => 'Ten', 'sections' => 'A, B, C',
        ])->assertSessionHas('success');
        $this->assertSame('Ten', $ram->fresh()->class);
        $this->assertSame(['A', 'B', 'C'], $ten->fresh()->sections);

        $this->actingAs($this->admin)->delete(route('structure.classes.destroy', $ten))->assertSessionHas('error');
        $this->assertDatabaseHas('school_classes', ['id' => $ten->id]);

        $eleven = SchoolClass::where('school_id', $this->school->id)->where('name', '11')->first();
        $this->actingAs($this->admin)->delete(route('structure.classes.destroy', $eleven))->assertSessionHas('success');
        $this->assertDatabaseMissing('school_classes', ['id' => $eleven->id]);
    }

    public function test_a_student_can_only_be_filed_in_a_class_the_school_runs(): void
    {
        $form = fn (array $o) => $o + ['name' => 'Ram', 'roll_number' => 1, 'school_id' => $this->school->id, 'is_active' => 1];

        $this->actingAs($this->admin)->from(route('students.create'))->post(route('students.store'), $form(['class' => '12', 'section' => 'A']))
            ->assertSessionHasErrors('section');
        $this->actingAs($this->admin)->from(route('students.create'))->post(route('students.store'), $form(['class' => '10', 'section' => 'Z']))
            ->assertSessionHasErrors('section');
        $this->assertDatabaseCount('students', 0);

        $this->actingAs($this->admin)->post(route('students.store'), $form(['class' => '10', 'section' => 'B']))
            ->assertRedirect(route('students.index'));
        $this->assertDatabaseHas('students', ['name' => 'Ram', 'class' => '10', 'section' => 'B']);
    }

    public function test_the_bulk_import_skips_a_row_in_a_class_the_school_does_not_run(): void
    {
        $csv = "name,class,section,roll_number,school_id\nRam,10,A,1,{$this->school->id}\nSita,12,A,2,{$this->school->id}\n";
        $file = UploadedFile::fake()->createWithContent('students.csv', $csv);

        $result = $this->actingAs($this->admin)->post(route('bulk-import.students'), ['csv_file' => $file])
            ->assertSessionHas('importResult')
            ->getSession()->get('importResult');

        $this->assertSame(1, $result['imported'], json_encode($result));
        $this->assertCount(1, $result['skipped']);
        $this->assertStringContainsString('does not run class 12 A', $result['skipped'][0]['reason']);
    }

    public function test_a_report_card_needs_a_listed_year(): void
    {
        $student = Student::create(['name' => 'Ram', 'class' => '10', 'section' => 'A', 'roll_number' => 1, 'school_id' => $this->school->id, 'is_active' => true]);
        $subject = \App\Models\Subject::create(['name' => 'Maths', 'code' => 'MAT', 'full_marks' => 100, 'pass_marks' => 35, 'is_active' => true]);

        $this->actingAs($this->admin)->get(route('reports.create'))
            ->assertOk()
            ->assertSee('<option value="2081" selected>', false);

        $this->actingAs($this->admin)->from(route('reports.create'))->post(route('reports.store'), [
            'student_id' => $student->id, 'academic_year' => '2079', 'class' => '10', 'section' => 'A', 'roll_number' => 1,
            'marks' => [['subject_id' => $subject->id, 'final_terminal_th' => 80]],
            'class_response' => 'A', 'discipline' => 'A', 'leadership' => 'A', 'neatness' => 'A',
            'punctuality' => 'A', 'regularity' => 'A', 'social_conduct' => 'A', 'sports_game' => 'B',
        ])->assertSessionHasErrors('academic_year');
    }
}

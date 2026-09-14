<?php

namespace Tests\Feature;

use App\Models\GradeSystem;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentMark;
use App\Models\StudentReport;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Concerns\SetsUpAcademics;
use Tests\TestCase;

/**
 * The ledger: one subject's marks for a whole class, landing on each
 * student's report card.
 */
class MarksEntryTest extends TestCase
{
    use RefreshDatabase, SetsUpAcademics;

    private User $admin;
    private School $school;
    private Subject $maths;
    private Student $ram;
    private Student $sita;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        foreach ([
            ['A+', 4.0, 90, 100, false], ['A', 3.6, 80, 89, false], ['B+', 3.2, 70, 79, false],
            ['B', 2.8, 60, 69, false], ['C+', 2.4, 50, 59, false], ['C', 2.0, 40, 49, false],
            ['D', 1.6, 35, 39, false], ['NG', 0.0, 0, 34, true],
        ] as [$letter, $point, $from, $to, $failing]) {
            GradeSystem::create(['letter_grade' => $letter, 'grade_point' => $point, 'marks_from' => $from, 'marks_to' => $to, 'description' => $letter, 'is_failing' => $failing, 'is_active' => true]);
        }

        $this->admin = User::create(['name' => 'Admin', 'username' => 'admin', 'email' => 'a@b.c', 'password' => 'secret123', 'is_active' => true]);
        $this->admin->assignRole('admin');

        $this->school = School::create(['name' => 'Test School', 'address' => 'Kathmandu']);
        $this->setUpAcademics($this->school);
        $this->maths = Subject::create(['name' => 'Maths', 'code' => 'MAT', 'full_marks' => 100, 'pass_marks' => 35, 'is_active' => true]);

        $this->ram = Student::create(['name' => 'Ram', 'class' => '10', 'section' => 'A', 'roll_number' => 1, 'school_id' => $this->school->id, 'is_active' => true]);
        $this->sita = Student::create(['name' => 'Sita', 'class' => '10', 'section' => 'A', 'roll_number' => 2, 'school_id' => $this->school->id, 'is_active' => true]);
    }

    private function filters(array $overrides = []): array
    {
        return $overrides + [
            'school_id' => $this->school->id, 'class' => '10', 'section' => 'A',
            'academic_year' => '2081', 'subject_id' => $this->maths->id, 'exam_type' => 'final_terminal',
        ];
    }

    public function test_the_ledger_lists_the_class_by_roll_number(): void
    {
        $this->actingAs($this->admin)->get(route('marks.index', $this->filters()))
            ->assertOk()
            ->assertSee('2 students')
            ->assertSee('0 / 2 entered');

        $rows = $this->actingAs($this->admin)->getJson(route('marks.rows', $this->filters()))
            ->assertOk()
            ->assertJsonPath('total', 2)
            ->json('data');

        $this->assertSame(['Ram', 'Sita'], array_column($rows, 'name'));
        $this->assertNull($rows[0]['th']);
        $this->assertNull($rows[0]['grade']);
    }

    public function test_the_rows_carry_the_mark_and_can_be_narrowed_to_the_missing_ones(): void
    {
        $this->actingAs($this->admin)->post(route('marks.store'), $this->filters() + [
            'marks' => [$this->ram->id => ['th' => 62.5, 'pr' => 20]],
        ]);

        $rows = $this->actingAs($this->admin)->getJson(route('marks.rows', $this->filters()))->json('data');
        $this->assertSame([62.5, 20, 82.5, 'A', false], [$rows[0]['th'], $rows[0]['pr'], $rows[0]['total'], $rows[0]['grade'], $rows[0]['fail']]);

        $missing = $this->actingAs($this->admin)->getJson(route('marks.rows', $this->filters(['entered' => '0'])))->json('data');
        $this->assertSame(['Sita'], array_column($missing, 'name'));

        $this->actingAs($this->admin)->getJson(route('marks.rows', $this->filters(['search' => 'ram'])))
            ->assertJsonPath('total', 1);
    }

    public function test_the_rows_need_the_whole_filter_and_the_users_own_school(): void
    {
        $this->actingAs($this->admin)->getJson(route('marks.rows', ['class' => '10']))
            ->assertOk()
            ->assertJsonPath('success', false);

        $other = School::create(['name' => 'Other', 'address' => 'Pokhara']);
        Role::findOrCreate('teacher-y', 'web')->givePermissionTo(['marks.viewAny']);
        $teacher = User::create(['name' => 'T', 'username' => 't2', 'email' => 't2@b.c', 'password' => 'secret123', 'is_active' => true, 'school_id' => $other->id]);
        $teacher->assignRole('teacher-y');

        // Another school's id does not select: the ledger is the teacher's own
        // school, which has nobody in class 10 A.
        $this->actingAs($teacher)->getJson(route('marks.rows', $this->filters()))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('total', 0);
    }

    public function test_saving_from_the_grid_answers_in_json(): void
    {
        $this->actingAs($this->admin)->postJson(route('marks.store'), $this->filters() + [
            'marks' => [$this->ram->id => ['th' => 50, 'pr' => '']],
        ])->assertOk()
          ->assertJson(['success' => true, 'saved' => 1, 'created' => 1, 'cleared' => 0]);

        $this->actingAs($this->admin)->postJson(route('marks.store'), $this->filters() + [
            'marks' => [$this->ram->id => ['th' => 80, 'pr' => 30]],
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['marks.'.$this->ram->id.'.th']);
    }

    public function test_saving_creates_a_report_card_for_each_student_and_grades_it(): void
    {
        $this->actingAs($this->admin)->post(route('marks.store'), $this->filters() + [
            'marks' => [
                $this->ram->id => ['th' => 62.5, 'pr' => 20],
                $this->sita->id => ['th' => 20, 'pr' => ''],
            ],
        ])->assertRedirect(route('marks.index', $this->filters()))
          ->assertSessionHas('success', 'Marks saved for 2 students. 2 new report cards were created.');

        $ram = StudentReport::where('student_id', $this->ram->id)->first();
        $this->assertSame('10', $ram->class);
        $this->assertSame('A', $ram->section);
        $this->assertSame(1, $ram->roll_number);
        $this->assertSame('3.60', (string) $ram->final_gpa); // 82.5% -> A
        $this->assertSame('A', $ram->final_grade);
        $this->assertSame(1, $ram->position);
        $this->assertDatabaseHas('student_marks', [
            'student_report_id' => $ram->id, 'subject_id' => $this->maths->id, 'exam_type' => 'final_terminal',
            'theory_marks' => 62.5, 'practical_marks' => 20, 'total_marks' => 82.5, 'letter_grade' => 'A',
        ]);

        $sita = StudentReport::where('student_id', $this->sita->id)->first();
        $this->assertSame('FAILED', $sita->result_status);
        $this->assertSame(2, $sita->position);
        // An empty practical is no mark, not a zero.
        $this->assertNull(StudentMark::where('student_report_id', $sita->id)->first()->practical_marks);
    }

    public function test_saving_again_updates_the_mark_rather_than_adding_another(): void
    {
        $this->actingAs($this->admin)->post(route('marks.store'), $this->filters() + [
            'marks' => [$this->ram->id => ['th' => 50, 'pr' => '']],
        ]);
        $this->actingAs($this->admin)->post(route('marks.store'), $this->filters() + [
            'marks' => [$this->ram->id => ['th' => 70, 'pr' => '']],
        ])->assertSessionHas('success', 'Marks saved for 1 student.');

        $report = StudentReport::where('student_id', $this->ram->id)->first();
        $this->assertSame(1, $report->marks()->count());
        $this->assertSame('B+', $report->marks()->first()->letter_grade);
        $this->assertSame('3.20', (string) $report->final_gpa);
    }

    public function test_a_terminal_that_is_not_the_final_leaves_the_result_pending(): void
    {
        $this->actingAs($this->admin)->post(route('marks.store'), $this->filters(['exam_type' => 'first_terminal']) + [
            'marks' => [$this->ram->id => ['th' => 80, 'pr' => '']],
        ]);

        $report = StudentReport::where('student_id', $this->ram->id)->first();
        $this->assertSame('PENDING', $report->result_status);
        $this->assertSame('', $report->final_grade);
    }

    public function test_clearing_both_boxes_removes_the_mark(): void
    {
        $this->actingAs($this->admin)->post(route('marks.store'), $this->filters() + [
            'marks' => [$this->ram->id => ['th' => 50, 'pr' => '']],
        ]);
        $this->actingAs($this->admin)->post(route('marks.store'), $this->filters() + [
            'marks' => [$this->ram->id => ['th' => '', 'pr' => '']],
        ])->assertSessionHas('success', 'Marks saved for 0 students. 1 mark was cleared.');

        $report = StudentReport::where('student_id', $this->ram->id)->first();
        $this->assertSame(0, $report->marks()->count());
        $this->assertSame('PENDING', $report->result_status);
    }

    public function test_a_mark_over_the_full_marks_is_refused(): void
    {
        $this->actingAs($this->admin)->from(route('marks.index'))->post(route('marks.store'), $this->filters() + [
            'marks' => [$this->ram->id => ['th' => 80, 'pr' => 30]],
        ])->assertSessionHasErrors(['marks.'.$this->ram->id.'.th']);

        $this->assertDatabaseCount('student_marks', 0);
    }

    public function test_a_teacher_cannot_write_to_another_school(): void
    {
        $other = School::create(['name' => 'Other', 'address' => 'Pokhara']);
        Role::findOrCreate('teacher-x', 'web')->givePermissionTo(['marks.viewAny', 'marks.update']);
        $teacher = User::create(['name' => 'T', 'username' => 't', 'email' => 't@b.c', 'password' => 'secret123', 'is_active' => true, 'school_id' => $other->id]);
        $teacher->assignRole('teacher-x');

        $this->actingAs($teacher)->post(route('marks.store'), $this->filters() + [
            'marks' => [$this->ram->id => ['th' => 50, 'pr' => '']],
        ])->assertForbidden();

        // Nor can a student of another school be smuggled in under their own school id.
        $this->actingAs($teacher)->from(route('marks.index'))->post(route('marks.store'), $this->filters(['school_id' => $other->id]) + [
            'marks' => [$this->ram->id => ['th' => 50, 'pr' => '']],
        ])->assertSessionHasErrors(['marks']);

        $this->assertDatabaseCount('student_marks', 0);
    }

    public function test_a_promoted_student_still_appears_on_last_years_ledger(): void
    {
        $this->actingAs($this->admin)->post(route('marks.store'), $this->filters() + [
            'marks' => [$this->ram->id => ['th' => 50, 'pr' => '']],
        ]);
        $this->ram->update(['class' => '11']);

        $rows = $this->actingAs($this->admin)->getJson(route('marks.rows', $this->filters()))->json('data');
        $this->assertSame('Ram', $rows[0]['name']);
        $this->assertTrue($rows[0]['moved']);
        $this->assertSame('11 A', $rows[0]['now']);

        $rows = $this->actingAs($this->admin)->getJson(route('marks.rows', $this->filters(['academic_year' => '2082'])))->json('data');
        $this->assertNotContains('Ram', array_column($rows, 'name'));
    }
}

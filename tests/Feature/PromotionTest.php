<?php

namespace Tests\Feature;

use App\Http\Controllers\PromotionController;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentReport;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Concerns\SetsUpAcademics;
use Tests\TestCase;

class PromotionTest extends TestCase
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

    private function student(string $name, int $roll, string $class = '9', string $section = 'A'): Student
    {
        return Student::create(['name' => $name, 'class' => $class, 'section' => $section, 'roll_number' => $roll, 'school_id' => $this->school->id, 'is_active' => true]);
    }

    private function card(Student $student, float $gpa, string $status = 'PASSED WITH FIRST DIVISION'): StudentReport
    {
        return StudentReport::create([
            'student_id' => $student->id, 'academic_year' => '2081', 'class' => $student->class, 'section' => $student->section,
            'roll_number' => $student->roll_number, 'final_gpa' => $gpa, 'final_grade' => 'B+', 'result_status' => $status, 'issue_date' => now(),
        ]);
    }

    public function test_the_page_lists_the_class_with_its_results_and_suggests_the_next_class(): void
    {
        $ram = $this->student('Ram', 1);
        $sita = $this->student('Sita', 2);
        $this->card($ram, 3.4);
        $this->card($sita, 0, 'FAILED');

        $page = $this->actingAs($this->admin)->get(route('promotion.index', ['school_id' => $this->school->id, 'class' => '9', 'section' => 'A']));
        $page->assertOk()
            ->assertSee('Ram')
            ->assertSee('Sita')
            ->assertSee('1 failed in 2081')
            ->assertSee('<option value="10" selected>', false);

        // The failed student starts unticked.
        $this->assertMatchesRegularExpression('#value="'.$ram->id.'"\s+checked#', $page->getContent());
        $this->assertDoesNotMatchRegularExpression('#value="'.$sita->id.'"\s+checked#', $page->getContent());
    }

    public function test_promoting_moves_the_students_and_leaves_their_cards_alone(): void
    {
        $ram = $this->student('Ram', 1);
        $sita = $this->student('Sita', 2);
        $hari = $this->student('Hari', 3);
        $card = $this->card($ram, 3.4);

        $this->actingAs($this->admin)->post(route('promotion.store'), [
            'school_id' => $this->school->id, 'class' => '9', 'section' => 'A', 'academic_year' => '2081',
            'student_ids' => [$ram->id, $hari->id],
            'to_class' => '10', 'to_section' => 'B', 'roll_mode' => 'keep',
        ])->assertRedirect()
          ->assertSessionHas('success', '2 students moved from class 9 A to class 10 B.');

        $this->assertSame(['10', 'B', 1], [$ram->fresh()->class, $ram->fresh()->section, $ram->fresh()->roll_number]);
        $this->assertSame(['10', 'B', 3], [$hari->fresh()->class, $hari->fresh()->section, $hari->fresh()->roll_number]);
        $this->assertSame(['9', 'A'], [$sita->fresh()->class, $sita->fresh()->section]);

        // The card still says class 9.
        $this->assertSame(['9', 'A', 1], [$card->fresh()->class, $card->fresh()->section, $card->fresh()->roll_number]);
    }

    public function test_rolls_can_be_renumbered_by_name_or_by_rank_after_whoever_is_there(): void
    {
        $sita = $this->student('Sita', 1);
        $ram = $this->student('Ram', 2);
        $hari = $this->student('Hari', 3);
        $this->card($sita, 2.8);
        $this->card($ram, 3.6);
        $this->card($hari, 3.2);
        $this->student('Old Timer', 4, '10', 'A');   // already in the target section

        $post = fn ($mode) => $this->actingAs($this->admin)->post(route('promotion.store'), [
            'school_id' => $this->school->id, 'class' => '9', 'section' => 'A', 'academic_year' => '2081',
            'student_ids' => [$sita->id, $ram->id, $hari->id],
            'to_class' => '10', 'to_section' => 'A', 'roll_mode' => $mode,
        ]);

        $post('rank');
        // Ram 3.6, Hari 3.2, Sita 2.8 -> 5, 6, 7 after roll 4.
        $this->assertSame([5, 6, 7], [$ram->fresh()->roll_number, $hari->fresh()->roll_number, $sita->fresh()->roll_number]);

        // Back down and up again, by name this time.
        Student::whereIn('id', [$sita->id, $ram->id, $hari->id])->update(['class' => '9', 'section' => 'A']);
        $post('name');
        $this->assertSame([5, 6, 7], [$hari->fresh()->roll_number, $ram->fresh()->roll_number, $sita->fresh()->roll_number]);
    }

    public function test_leaving_marks_the_students_as_no_longer_enrolled(): void
    {
        $ram = $this->student('Ram', 1, '10', 'A');

        $this->actingAs($this->admin)->post(route('promotion.store'), [
            'school_id' => $this->school->id, 'class' => '10', 'section' => 'A', 'academic_year' => '2081',
            'student_ids' => [$ram->id],
            'to_class' => PromotionController::LEAVE, 'roll_mode' => 'keep',
        ])->assertSessionHas('success', '1 student from class 10 A marked as left. Their report cards are kept.');

        $this->assertFalse($ram->fresh()->is_active);
        $this->assertSame('10', $ram->fresh()->class);
    }

    public function test_the_target_has_to_be_a_class_the_school_runs_and_not_the_same_one(): void
    {
        $ram = $this->student('Ram', 1);

        $post = fn (array $to) => $this->actingAs($this->admin)->from(route('promotion.index'))->post(route('promotion.store'), [
            'school_id' => $this->school->id, 'class' => '9', 'section' => 'A', 'academic_year' => '2081',
            'student_ids' => [$ram->id], 'roll_mode' => 'keep',
        ] + $to);

        $post(['to_class' => '12', 'to_section' => 'A'])->assertSessionHasErrors('to_section');
        $post(['to_class' => '10', 'to_section' => 'Z'])->assertSessionHasErrors('to_section');
        $post(['to_class' => '9', 'to_section' => 'A'])->assertSessionHasErrors('to_class');
        $this->assertSame('9', $ram->fresh()->class);
    }

    public function test_only_the_users_own_school_can_be_promoted(): void
    {
        $ram = $this->student('Ram', 1);
        $other = School::create(['name' => 'Other', 'address' => 'Pokhara']);
        Role::findOrCreate('promoter', 'web')->givePermissionTo(['promotion.run']);
        $user = User::create(['name' => 'P', 'username' => 'p', 'email' => 'p@b.c', 'password' => 'secret123', 'is_active' => true, 'school_id' => $other->id]);
        $user->assignRole('promoter');

        $this->actingAs($user)->post(route('promotion.store'), [
            'school_id' => $this->school->id, 'class' => '9', 'section' => 'A',
            'student_ids' => [$ram->id], 'to_class' => '10', 'to_section' => 'A', 'roll_mode' => 'keep',
        ])->assertForbidden();

        $this->assertSame('9', $ram->fresh()->class);
    }
}

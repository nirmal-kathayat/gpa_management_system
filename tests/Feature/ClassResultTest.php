<?php

namespace Tests\Feature;

use App\Models\GradeSystem;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentReport;
use App\Models\Subject;
use App\Models\User;
use App\Support\ClassResultSheet;
use App\Support\ReportGrader;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The class result sheet: one exam's result for every student in a class.
 */
class ClassResultTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private School $school;
    private Subject $maths;
    private Subject $science;

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
        $this->maths = Subject::create(['name' => 'Maths', 'code' => 'MAT', 'full_marks' => 100, 'pass_marks' => 35, 'is_active' => true]);
        $this->science = Subject::create(['name' => 'Science', 'code' => 'SCI', 'full_marks' => 50, 'pass_marks' => 18, 'is_active' => true]);
    }

    private function filters(array $overrides = []): array
    {
        return $overrides + [
            'school_id' => $this->school->id, 'class' => '10', 'section' => 'A',
            'academic_year' => '2081', 'exam_type' => 'final_terminal',
        ];
    }

    /** A student with a card, graded from the marks given per subject and exam. */
    private function student(string $name, int $roll, array $marks): Student
    {
        $student = Student::create(['name' => $name, 'class' => '10', 'section' => 'A', 'roll_number' => $roll, 'school_id' => $this->school->id, 'is_active' => true]);

        $report = StudentReport::create([
            'student_id' => $student->id, 'academic_year' => '2081', 'class' => '10', 'section' => 'A', 'roll_number' => $roll,
            'final_gpa' => 0, 'final_grade' => '', 'issue_date' => now(),
        ]);
        $grader = new ReportGrader();
        $report->update($grader->grade($report, $marks));
        $grader->recalculatePositions($report);

        return $student;
    }

    private function sheet(array $overrides = []): ClassResultSheet
    {
        return new ClassResultSheet($this->filters($overrides), 'Final Terminal');
    }

    public function test_the_sheet_ranks_the_class_and_agrees_with_the_cards(): void
    {
        $ram = $this->student('Ram', 1, [
            ['subject_id' => $this->maths->id, 'final_terminal_th' => 90],
            ['subject_id' => $this->science->id, 'final_terminal_th' => 40],
        ]);
        $sita = $this->student('Sita', 2, [
            ['subject_id' => $this->maths->id, 'final_terminal_th' => 60],
            ['subject_id' => $this->science->id, 'final_terminal_th' => 45],
        ]);
        $hari = $this->student('Hari', 3, [
            ['subject_id' => $this->maths->id, 'final_terminal_th' => 20],   // fails
            ['subject_id' => $this->science->id, 'final_terminal_th' => 45],
        ]);

        $sheet = $this->sheet();

        $this->assertSame(['Maths', 'Science'], $sheet->subjects->pluck('name')->all());
        $this->assertSame(['Ram', 'Sita', 'Hari'], $sheet->rows->map(fn ($r) => $r['report']->student->name)->all());

        [$r1, $r2, $r3] = $sheet->rows;

        // Ram: A+ (4.0) and A (3.6) -> 3.8; Sita: B (2.8) and A+ (4.0) -> 3.4; Hari fails.
        $this->assertSame([3.8, 'A', 1, true], [$r1['gpa'], $r1['grade'], $r1['rank'], $r1['passed']]);
        $this->assertSame([3.4, 'B+', 2, true], [$r2['gpa'], $r2['grade'], $r2['rank'], $r2['passed']]);
        $this->assertSame([0.0, 'NG', 3, false, 'FAILED'], [$r3['gpa'], $r3['grade'], $r3['rank'], $r3['passed'], $r3['status']]);
        $this->assertTrue($r3['cells'][0]['fail']);
        $this->assertSame(130.0, $r1['obtained']);
        $this->assertSame(150.0, $r1['full']);

        // The same result the cards carry.
        foreach ([[$ram, $r1], [$sita, $r2], [$hari, $r3]] as [$student, $row]) {
            $card = $student->reports()->first();
            $this->assertSame((string) $card->final_gpa, number_format($row['gpa'], 2));
            $this->assertSame($card->position, $row['rank']);
        }

        $this->assertSame(3, $sheet->summary['students']);
        $this->assertSame(2, $sheet->summary['passed']);
        $this->assertSame(1, $sheet->summary['failed']);
        $this->assertSame(66.7, $sheet->summary['pass_rate']);
        $this->assertSame('Ram', $sheet->summary['toppers'][0]['report']->student->name);
        $this->assertSame(['sat' => 3, 'passed' => 2, 'average' => 56.7], $sheet->summary['subjects'][0]);
    }

    public function test_an_exam_nobody_sat_marks_everyone_absent_and_a_missing_subject_is_ab(): void
    {
        $this->student('Ram', 1, [
            ['subject_id' => $this->maths->id, 'final_terminal_th' => 90, 'first_terminal_th' => 70],
            ['subject_id' => $this->science->id, 'final_terminal_th' => 40],
        ]);
        $this->student('Sita', 2, [
            ['subject_id' => $this->maths->id, 'final_terminal_th' => 60],
        ]);

        // Only Ram sat the first terminal, and only in maths.
        $first = $this->sheet(['exam_type' => 'first_terminal']);
        $this->assertSame(['Maths'], $first->subjects->pluck('name')->all());
        $this->assertFalse($first->rows[0]['absent']);
        $this->assertSame(1, $first->rows[0]['rank']);
        $this->assertTrue($first->rows[1]['absent']);
        $this->assertNull($first->rows[1]['rank']);
        $this->assertSame(1, $first->summary['absent']);
        $this->assertSame(100.0, $first->summary['pass_rate']);

        // In the final, Sita has no science mark: that cell is blank and her
        // total is out of maths alone.
        $final = $this->sheet();
        $this->assertNull($final->rows[1]['cells'][1]);
        $this->assertSame(100.0, $final->rows[1]['full']);
        $this->assertSame(2.8, $final->rows[1]['gpa']);

        $this->assertTrue($this->sheet(['exam_type' => 'pre_board'])->rows->every(fn ($r) => $r['absent']));
        $this->assertTrue($this->sheet(['academic_year' => '2080'])->isEmpty());
    }

    public function test_equal_results_share_a_rank(): void
    {
        $this->student('Ram', 1, [['subject_id' => $this->maths->id, 'final_terminal_th' => 80]]);
        $this->student('Sita', 2, [['subject_id' => $this->maths->id, 'final_terminal_th' => 80]]);
        $this->student('Hari', 3, [['subject_id' => $this->maths->id, 'final_terminal_th' => 50]]);

        $this->assertSame([1, 1, 3], $this->sheet()->rows->pluck('rank')->all());
    }

    public function test_a_class_of_mark_sheets_downloads_as_one_pdf_a_page_each(): void
    {
        $this->student('Ram', 1, [['subject_id' => $this->maths->id, 'final_terminal_th' => 80]]);
        $this->student('Sita', 2, [['subject_id' => $this->maths->id, 'final_terminal_th' => 60]]);
        $this->student('Hari', 3, [['subject_id' => $this->maths->id, 'final_terminal_th' => 40]]);

        $filters = array_diff_key($this->filters(), ['exam_type' => 1]);

        $this->actingAs($this->admin)->get(route('results.index', $this->filters()))
            ->assertOk()
            ->assertSee('All Mark Sheets')
            ->assertSee(e(route('reports.class-pdf', $filters)), false);

        $response = $this->actingAs($this->admin)->get(route('reports.class-pdf', $filters));
        $response->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename="mark-sheets-class-10-A-2081.pdf"');

        $content = $response->getContent();
        $this->assertStringStartsWith('%PDF', $content);
        // One page per student.
        $this->assertSame(3, preg_match_all('#/Type\s*/Page[^s]#', $content));

        // A year with no cards has nothing to print.
        $this->actingAs($this->admin)->get(route('reports.class-pdf', ['academic_year' => '2080'] + $filters))
            ->assertNotFound();

        // Another school's class is out of reach, and half a filter is nothing.
        $other = School::create(['name' => 'Other', 'address' => 'Pokhara']);
        Role::findOrCreate('printer', 'web')->givePermissionTo(['reports.pdf']);
        $printer = User::create(['name' => 'P', 'username' => 'p', 'email' => 'p@b.c', 'password' => 'secret123', 'is_active' => true, 'school_id' => $other->id]);
        $printer->assignRole('printer');

        $this->actingAs($printer)->get(route('reports.class-pdf', $filters))->assertNotFound();
        $this->actingAs($this->admin)->get(route('reports.class-pdf', ['class' => '10']))->assertNotFound();
    }

    public function test_the_page_and_the_pdf_are_scoped_to_the_users_school(): void
    {
        $this->student('Ram', 1, [['subject_id' => $this->maths->id, 'final_terminal_th' => 80]]);

        $this->actingAs($this->admin)->get(route('results.index', $this->filters()))
            ->assertOk()
            ->assertSee('Class Result Sheet')
            ->assertSee('Ram')
            ->assertSee('Download PDF');

        $pdf = $this->actingAs($this->admin)->get(route('results.pdf', $this->filters()));
        $pdf->assertOk();
        $this->assertStringStartsWith('%PDF', $pdf->getContent());

        $other = School::create(['name' => 'Other', 'address' => 'Pokhara']);
        Role::findOrCreate('viewer', 'web')->givePermissionTo(['results.viewAny']);
        $viewer = User::create(['name' => 'V', 'username' => 'v', 'email' => 'v@b.c', 'password' => 'secret123', 'is_active' => true, 'school_id' => $other->id]);
        $viewer->assignRole('viewer');

        // The other school's id does not select; the viewer's own school has no class 10 A.
        $this->actingAs($viewer)->get(route('results.index', $this->filters()))
            ->assertOk()
            ->assertDontSee('Ram');

        $this->actingAs($viewer)->get(route('results.pdf', $this->filters()))->assertForbidden();
    }
}

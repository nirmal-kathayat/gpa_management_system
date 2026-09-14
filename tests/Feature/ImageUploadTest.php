<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImageUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private School $school;
    private array $written = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->admin = User::create(['name' => 'Admin', 'username' => 'admin', 'email' => 'a@b.c', 'password' => 'secret123', 'is_active' => true]);
        $this->admin->assignRole('admin');
        $this->school = School::create(['name' => 'Test School', 'address' => 'Kathmandu']);
    }

    protected function tearDown(): void
    {
        foreach ($this->written as $path) {
            if (is_file(public_path($path))) {
                unlink(public_path($path));
            }
        }

        parent::tearDown();
    }

    private function studentForm(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Hari', 'class' => '9', 'section' => 'A', 'roll_number' => 1,
            'school_id' => $this->school->id, 'is_active' => 1,
        ];
    }

    public function test_a_photo_is_stored_under_a_random_name_not_the_uploaded_one(): void
    {
        $photo = UploadedFile::fake()->image('../../my passport photo (1).jpg');

        $this->actingAs($this->admin)
            ->post(route('students.store'), $this->studentForm(['photo' => $photo]))
            ->assertRedirect(route('students.index'));

        $student = Student::first();
        $this->written[] = $student->photo;

        $this->assertMatchesRegularExpression('#^assets/student/[A-Za-z0-9]{40}\.jpg$#', $student->photo);
        $this->assertFileExists(public_path($student->photo));
    }

    public function test_replacing_a_photo_removes_the_old_file(): void
    {
        $this->actingAs($this->admin)
            ->post(route('students.store'), $this->studentForm(['photo' => UploadedFile::fake()->image('a.jpg')]));
        $student = Student::first();
        $old = $student->photo;
        $this->written[] = $old;

        $this->actingAs($this->admin)
            ->put(route('students.update', $student), $this->studentForm(['photo' => UploadedFile::fake()->image('b.png')]))
            ->assertRedirect(route('students.index'));

        $student->refresh();
        $this->written[] = $student->photo;

        $this->assertNotSame($old, $student->photo);
        $this->assertFileDoesNotExist(public_path($old));
        $this->assertFileExists(public_path($student->photo));
    }

    public function test_saving_without_a_photo_keeps_the_current_one(): void
    {
        $this->actingAs($this->admin)
            ->post(route('students.store'), $this->studentForm(['photo' => UploadedFile::fake()->image('a.jpg')]));
        $student = Student::first();
        $this->written[] = $student->photo;

        $this->actingAs($this->admin)->put(route('students.update', $student), $this->studentForm(['name' => 'Hari Prasad']));

        $this->assertSame($this->written[0], $student->fresh()->photo);
        $this->assertFileExists(public_path($student->photo));
    }

    public function test_a_school_logo_is_stored_the_same_way(): void
    {
        $this->actingAs($this->admin)->put(route('schools.update', $this->school), [
            'name' => 'Test School', 'address' => 'Kathmandu',
            'logo' => UploadedFile::fake()->image('logo.png'),
        ])->assertRedirect();

        $logo = $this->school->fresh()->logo;
        $this->written[] = $logo;

        $this->assertMatchesRegularExpression('#^assets/school/[A-Za-z0-9]{40}\.png$#', $logo);
        $this->assertFileExists(public_path($logo));
    }
}

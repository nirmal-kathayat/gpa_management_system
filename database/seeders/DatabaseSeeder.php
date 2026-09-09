<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            GradeScaleSeeder::class,
        ]);

        $admin = User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'System Administrator',
                'email' => 'admin@gpa-system.com',
                'password' => Hash::make('Nirmal977@#'),
                'is_active' => true,
            ]
        );

        $admin->syncRoles('admin');
    }
}

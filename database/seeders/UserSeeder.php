<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\School;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Create admin user
        User::create([
            'name' => 'System Administrator',
            'email' => 'admin@gpa-system.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'school_id' => null,
            'phone' => '9841234567',
            'address' => 'Kathmandu, Nepal',
            'is_active' => true,
            'email_verified_at' => now()
        ]);

        // Get first school for sample users
        $school = School::first();
        
        if ($school) {
            // Create teacher user
            User::create([
                'name' => 'John Teacher',
                'email' => 'teacher@school.com',
                'password' => Hash::make('teacher123'),
                'role' => 'teacher',
                'school_id' => $school->id,
                'phone' => '9851234567',
                'address' => 'Kathmandu, Nepal',
                'is_active' => true,
                'email_verified_at' => now()
            ]);

            // Create staff user
            User::create([
                'name' => 'Jane Staff',
                'email' => 'staff@school.com',
                'password' => Hash::make('staff123'),
                'role' => 'staff',
                'school_id' => $school->id,
                'phone' => '9861234567',
                'address' => 'Lalitpur, Nepal',
                'is_active' => true,
                'email_verified_at' => now()
            ]);
        }
    }
}

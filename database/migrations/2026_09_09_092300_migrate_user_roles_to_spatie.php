<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

/**
 * Moves authorisation off the users.role enum and onto spatie/laravel-permission.
 *
 * The three names that lived in the enum become real roles, every user is given
 * the matching role, and only then is the column dropped. What each role is
 * allowed to do is defined in RolePermissionSeeder, not here.
 */
return new class extends Migration
{
    private const ROLES = ['admin', 'teacher', 'staff'];

    public function up(): void
    {
        foreach (self::ROLES as $name) {
            Role::findOrCreate($name, 'web');
        }

        if (Schema::hasColumn('users', 'role')) {
            DB::table('users')->select('id', 'role')->orderBy('id')->chunk(200, function ($users) {
                foreach ($users as $user) {
                    $role = Role::where('name', $user->role)->where('guard_name', 'web')->first();

                    if ($role) {
                        DB::table('model_has_roles')->insertOrIgnore([
                            'role_id' => $role->id,
                            'model_type' => \App\Models\User::class,
                            'model_id' => $user->id,
                        ]);
                    }
                }
            });

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', self::ROLES)->default('staff')->after('password');
            });
        }

        $roleNames = Role::whereIn('name', self::ROLES)->pluck('name', 'id');

        DB::table('model_has_roles')
            ->where('model_type', \App\Models\User::class)
            ->whereIn('role_id', $roleNames->keys())
            ->orderBy('model_id')
            ->chunk(200, function ($assignments) use ($roleNames) {
                foreach ($assignments as $assignment) {
                    DB::table('users')->where('id', $assignment->model_id)
                        ->update(['role' => $roleNames[$assignment->role_id]]);
                }
            });
    }
};

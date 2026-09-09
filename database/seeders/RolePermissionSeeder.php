<?php

namespace Database\Seeders;

use App\Support\Permissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Syncs App\Support\Permissions into the database and gives the three built-in
 * roles their starting sets. Safe to re-run: it only ever adds what is missing,
 * so permissions an admin granted by hand in the UI are left alone.
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * What each built-in role starts with. 'admin' is absent on purpose - it
     * passes every check through the Gate::before in AuthServiceProvider.
     */
    private const ROLE_PERMISSIONS = [
        'teacher' => [
            'students.viewAny', 'students.create', 'students.update',
            'reports.viewAny', 'reports.create', 'reports.update', 'reports.delete', 'reports.pdf',
            'subjects.viewAny', 'grades.viewAny',
        ],
        'staff' => [
            'students.viewAny',
            'reports.viewAny', 'reports.pdf',
            'subjects.viewAny', 'grades.viewAny',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Permissions::names() as $name) {
            Permission::findOrCreate($name, 'web');
        }

        // Admin holds every permission as well as bypassing the checks, so the
        // Roles screen shows the truth rather than an empty row.
        Role::findOrCreate('admin', 'web')->syncPermissions(Permissions::names());

        foreach (self::ROLE_PERMISSIONS as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'web');

            $role->givePermissionTo(array_diff($permissions, $role->permissions->pluck('name')->all()));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

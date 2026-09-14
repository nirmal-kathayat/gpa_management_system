<?php

namespace Database\Seeders;

use App\Support\Permissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Syncs App\Support\Permissions into the database.
 *
 * 'admin' is the only role the application itself knows about, so it is always
 * present and always holds everything. 'teacher' and 'staff' are examples for a
 * fresh install and nothing in the code names them - once an administrator has
 * made roles of their own, re-running this seeder leaves them alone rather than
 * resurrecting examples they deleted.
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * Example roles for a fresh install. 'admin' is absent on purpose - it
     * passes every check through the Gate::before in AuthServiceProvider.
     */
    private const EXAMPLE_ROLES = [
        'teacher' => [
            'students.viewAny', 'students.create', 'students.update',
            'marks.viewAny', 'marks.update',
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

        if (Role::where('name', '!=', 'admin')->exists()) {
            return;
        }

        foreach (self::EXAMPLE_ROLES as $roleName => $permissions) {
            Role::findOrCreate($roleName, 'web')->givePermissionTo($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

<?php

namespace App\Support;

/**
 * The single source of truth for what permissions exist.
 *
 * RolePermissionSeeder syncs this into the permissions table, the routes name
 * these strings, and the Roles screen renders one card per module from it. Add
 * a module here and everything else picks it up.
 */
class Permissions
{
    /** Ability key => the verb shown to a person. */
    public const ABILITIES = [
        'viewAny' => 'View',
        'create' => 'Create',
        'update' => 'Edit',
        'delete' => 'Delete',
        'pdf' => 'Download PDF',
        'run' => 'Run import',
    ];

    /**
     * Sections of the sidebar, each holding the modules that live under it.
     * 'abilities' lists which of the verbs above that module supports.
     */
    public const GROUPS = [
        'Academic records' => [
            'students' => ['label' => 'Students', 'icon' => 'fa-user-graduate', 'abilities' => ['viewAny', 'create', 'update', 'delete']],
            'marks' => ['label' => 'Marks Entry', 'icon' => 'fa-pen-to-square', 'abilities' => ['viewAny', 'update']],
            'reports' => ['label' => 'Report Cards', 'icon' => 'fa-file-lines', 'abilities' => ['viewAny', 'create', 'update', 'delete', 'pdf']],
        ],
        'Academic setup' => [
            'schools' => ['label' => 'Schools', 'icon' => 'fa-school', 'abilities' => ['viewAny', 'create', 'update', 'delete']],
            'subjects' => ['label' => 'Subjects', 'icon' => 'fa-book', 'abilities' => ['viewAny', 'create', 'update', 'delete']],
            'grades' => ['label' => 'Grade Scale', 'icon' => 'fa-award', 'abilities' => ['viewAny', 'create', 'update', 'delete']],
            'bulk-import' => ['label' => 'Bulk Import', 'icon' => 'fa-upload', 'abilities' => ['run']],
        ],
        'Administration' => [
            'users' => ['label' => 'Users', 'icon' => 'fa-users', 'abilities' => ['viewAny', 'create', 'update', 'delete']],
            'roles' => ['label' => 'Roles', 'icon' => 'fa-user-shield', 'abilities' => ['viewAny', 'create', 'update', 'delete']],
            'permissions' => ['label' => 'Permissions', 'icon' => 'fa-key', 'abilities' => ['viewAny']],
        ],
    ];

    /** Every module keyed by name, with its group folded in. */
    public static function modules(): array
    {
        $modules = [];

        foreach (self::GROUPS as $group => $groupModules) {
            foreach ($groupModules as $name => $module) {
                $modules[$name] = $module + ['group' => $group];
            }
        }

        return $modules;
    }

    /** Every permission name, e.g. 'students.create'. */
    public static function names(): array
    {
        $names = [];

        foreach (self::modules() as $module => $definition) {
            foreach ($definition['abilities'] as $ability) {
                $names[] = "{$module}.{$ability}";
            }
        }

        return $names;
    }

    /** 'students.create' => 'Students: Create', for listing screens. */
    public static function label(string $permission): string
    {
        [$module, $ability] = array_pad(explode('.', $permission, 2), 2, '');

        $moduleLabel = self::modules()[$module]['label'] ?? ucfirst(str_replace('-', ' ', $module));

        return $moduleLabel.': '.(self::ABILITIES[$ability] ?? ucfirst($ability));
    }
}

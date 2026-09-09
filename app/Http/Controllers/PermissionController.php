<?php

namespace App\Http\Controllers;

use App\Support\Permissions;
use Spatie\Permission\Models\Permission;

/**
 * Read-only. Permissions are defined in App\Support\Permissions and synced by
 * RolePermissionSeeder, because a permission only means something if the code
 * checks for it - one typed into a form would grant nothing.
 */
class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::with('roles:id,name')->get()->keyBy('name');

        return view('permissions.index', [
            'groups' => Permissions::GROUPS,
            'abilities' => Permissions::ABILITIES,
            'permissions' => $permissions,
            'missing' => array_diff(Permissions::names(), $permissions->keys()->all()),
        ]);
    }
}

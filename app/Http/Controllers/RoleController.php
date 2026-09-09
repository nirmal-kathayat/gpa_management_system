<?php

namespace App\Http\Controllers;

use App\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Roles that ship with the app and cannot be renamed or removed. Losing
     * 'admin' would leave nobody able to grant permissions again.
     */
    public const LOCKED = ['admin'];

    public function index()
    {
        $roles = Role::withCount(['permissions', 'users'])->orderBy('name')->get();

        return view('roles.index', [
            'roles' => $roles,
            'locked' => self::LOCKED,
            'totalPermissions' => Permission::count(),
        ]);
    }

    public function create()
    {
        return view('roles.create', [
            'groups' => Permissions::GROUPS,
            'abilities' => Permissions::ABILITIES,
            'granted' => old('permissions', []),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateRole($request);

        $role = Role::create(['name' => $validated['name'], 'guard_name' => 'web']);
        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()->route('roles.index')
            ->with('success', 'Role "'.Str::headline($role->name).'" created successfully!');
    }

    public function edit(Role $role)
    {
        return view('roles.edit', [
            'role' => $role,
            'groups' => Permissions::GROUPS,
            'abilities' => Permissions::ABILITIES,
            'granted' => old('permissions', $role->permissions->pluck('name')->all()),
            'isLocked' => in_array($role->name, self::LOCKED, true),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        if (in_array($role->name, self::LOCKED, true)) {
            return redirect()->route('roles.index')
                ->with('error', 'The "'.Str::headline($role->name).'" role is built in and cannot be changed.');
        }

        $validated = $this->validateRole($request, $role);

        $role->update(['name' => $validated['name']]);
        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()->route('roles.index')
            ->with('success', 'Role "'.Str::headline($role->name).'" updated successfully!');
    }

    public function destroy(Role $role)
    {
        if (in_array($role->name, self::LOCKED, true)) {
            return redirect()->route('roles.index')
                ->with('error', 'The "'.Str::headline($role->name).'" role is built in and cannot be deleted.');
        }

        if ($role->users()->exists()) {
            return redirect()->route('roles.index')
                ->with('error', 'This role is still assigned to users. Move them to another role first.');
        }

        $name = Str::headline($role->name);
        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Role "'.$name.'" deleted successfully!');
    }

    /**
     * Names are stored machine-readable ("head-teacher") and shown as a headline,
     * so two roles cannot differ by capitalisation alone.
     */
    private function validateRole(Request $request, ?Role $role = null): array
    {
        $request->merge([
            'name' => Str::slug((string) $request->input('name')),
        ]);

        return $request->validate([
            'name' => [
                'required', 'string', 'max:50',
                Rule::unique('roles', 'name')->ignore($role?->id),
                Rule::notIn($role ? array_diff(self::LOCKED, [$role->name]) : self::LOCKED),
            ],
            'permissions' => 'array',
            'permissions.*' => ['string', Rule::in(Permissions::names())],
        ], [
            'name.required' => 'Enter a role name using letters, numbers, spaces or hyphens.',
            'name.not_in' => 'That name is reserved for a built-in role.',
        ]);
    }
}

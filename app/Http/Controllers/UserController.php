<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['school', 'roles'])->orderBy('name')->paginate(10);
        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.create', [
            'schools' => School::orderBy('name')->get(),
            'roles' => $this->assignableRoles(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|alpha_dash|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => ['required', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'school_id' => 'nullable|exists:schools,id',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $role = $validated['role'];
        unset($validated['role']);

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = $request->has('is_active');

        $user = User::create($validated);
        $user->syncRoles($role);

        return redirect()->route('users.index')->with('success', 'User created successfully!');
    }

    public function show(User $user)
    {
        $user->load(['school', 'roles.permissions']);
        return view('users.show', compact('user'));
    }

    public function edit(User $user)
    {
        return view('users.edit', [
            'user' => $user,
            'schools' => School::orderBy('name')->get(),
            'roles' => $this->assignableRoles(),
            'isSelf' => $user->id === auth()->id(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('users')->ignore($user->id)],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'school_id' => 'nullable|exists:schools,id',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        if ($request->filled('password')) {
            $request->validate([
                'password' => 'string|min:8|confirmed'
            ]);
            $validated['password'] = Hash::make($request->password);
        }

        $role = $validated['role'];
        unset($validated['role']);

        $validated['is_active'] = $request->has('is_active');

        // Changing your own role or switching yourself off would end the session
        // you are working in, so those two fields are ignored for your own row.
        if ($user->id === auth()->id()) {
            $validated['is_active'] = $user->is_active;
            $role = null;
        }

        $user->update($validated);

        if ($role !== null) {
            $user->syncRoles($role);
        }

        return redirect()->route('users.index')->with('success', 'User updated successfully!');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')->with('error', 'You cannot delete your own account!');
        }

        $user->delete();
        return redirect()->route('users.index')->with('success', 'User deleted successfully!');
    }

    /**
     * Every role can be handed out; 'admin' included, since only an admin can
     * reach this screen in the first place.
     */
    private function assignableRoles()
    {
        return Role::where('guard_name', 'web')->orderBy('name')->get();
    }

    public function profile()
    {
        $user = auth()->user();
        return view('users.profile', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);

        if ($request->filled('password')) {
            $request->validate([
                'current_password' => 'required',
                'password' => 'required|string|min:8|confirmed'
            ]);

            if (!Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'Current password is incorrect']);
            }

            $validated['password'] = Hash::make($request->password);
        }

        $user->update($validated);

        return redirect()->route('users.profile')->with('success', 'Profile updated successfully!');
    }
}

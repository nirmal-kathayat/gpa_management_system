<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Support\TableResponse;
use Illuminate\Http\Request;

class SchoolController extends Controller
{
    /**
     * JSON rows for the TableHelper grid on the index page.
     */
    public function list(Request $request)
    {
        return TableResponse::make($request, School::withCount('students'), [
            'search' => ['name', 'address', 'email'],
            'filters' => [
                'name' => 'name',
                'address' => 'address',
                'phone' => 'phone',
                'email' => 'email',
            ],
            'sort' => [
                'name' => 'name',
                'students_count' => 'students_count',
            ],
            'default' => ['id', 'desc'],
        ], fn ($school) => [
            'id' => $school->id,
            'name' => $school->name,
            // Carried so the edit modal can fill itself without another request.
            'code' => $school->code,
            'tagline' => $school->tagline,
            'established' => $school->established,
            'type' => $school->type,
            'about' => $school->about,
            'address' => $school->address,
            'phone' => $school->phone,
            'email' => $school->email,
            'logo' => $school->logo,
            'students_count' => $school->students_count,
        ]);
    }

    public function index()
    {
        // Rows are fetched by the grid from schools.list.
        return view('schools.index');
    }

    /**
     * The modal is on the listing and on the dashboard, so a save goes back to
     * whichever one it was opened from. A route name, never a URL, so the form
     * cannot send the user anywhere else.
     */
    private function backTo(Request $request): string
    {
        return $request->input('return_to') === 'dashboard' ? 'dashboard' : 'schools.index';
    }

    /**
     * Schools are added and edited through a modal on the index page, so there
     * are no create/edit screens; old links land on the list with it open.
     */
    public function create()
    {
        return redirect()->route('schools.index', ['add' => 1]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'tagline' => 'nullable|string|max:255',
            'established' => 'nullable|string|max:50',
            'type' => 'nullable|string|max:50',
            'about' => 'nullable|string|max:2000',
            'address' => 'required|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $imagePath = '';
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $destination = public_path('assets/school/');
            if (!file_exists($destination)) {
                mkdir($destination, 0777, true);
            }
            $imageName = time() . '_' . $file->getClientOriginalName();
            $file->move($destination, $imageName);
            $imagePath = 'assets/school/' . $imageName;
        }

        School::create([
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'tagline' => $validated['tagline'] ?? null,
            'established' => $validated['established'] ?? null,
            'type' => $validated['type'] ?? null,
            'about' => $validated['about'] ?? null,
            'address' => $validated['address'],
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'logo' => $imagePath,
        ]);

        return redirect()->route($this->backTo($request))->with('success', 'School created successfully!');
    }


    public function show(School $school)
    {
        $students = $school->students();

        return view('schools.show', [
            'school' => $school,
            'studentCount' => (clone $students)->count(),
            'classCount' => (clone $students)->distinct()->count('class'),
            'sectionCount' => (clone $students)->distinct()->count('section'),
        ]);
    }

    public function edit(School $school)
    {
        return redirect()->route('schools.index', ['edit' => $school->id]);
    }

    public function update(Request $request, School $school)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'tagline' => 'nullable|string|max:255',
            'established' => 'nullable|string|max:50',
            'type' => 'nullable|string|max:50',
            'about' => 'nullable|string|max:2000',
            'address' => 'required|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $imagePath = $school->logo; // keep old logo if not updating
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $destination = public_path('assets/school/');
            if (!file_exists($destination)) {
                mkdir($destination, 0777, true);
            }
            $imageName = time() . '_' . $file->getClientOriginalName();
            $file->move($destination, $imageName);
            $imagePath = 'assets/school/' . $imageName;

            // Optionally delete the old logo
            if ($school->logo && file_exists(public_path($school->logo))) {
                unlink(public_path($school->logo));
            }
        }

        $school->update([
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'tagline' => $validated['tagline'] ?? null,
            'established' => $validated['established'] ?? null,
            'type' => $validated['type'] ?? null,
            'about' => $validated['about'] ?? null,
            'address' => $validated['address'],
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'logo' => $imagePath,
        ]);

        return redirect()->route($this->backTo($request))->with('success', 'School updated successfully!');
    }

    public function destroy(School $school)
    {
        $school->delete();
        return redirect()->route('schools.index')->with('success', 'School deleted successfully!');
    }
}

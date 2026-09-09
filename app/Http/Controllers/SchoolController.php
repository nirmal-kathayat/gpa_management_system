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
            'default' => ['name', 'asc'],
        ], fn ($school) => [
            'id' => $school->id,
            'name' => $school->name,
            'address' => $school->address,
            'phone' => $school->phone,
            'email' => $school->email,
            'students_count' => $school->students_count,
        ]);
    }

    public function index()
    {
        // Rows are fetched by the grid from schools.list.
        return view('schools.index');
    }

    public function create()
    {
        return view('schools.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
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
            'address' => $validated['address'],
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'logo' => $imagePath,
        ]);

        return redirect()->route('schools.index')->with('success', 'School created successfully!');
    }


    public function show(School $school)
    {
        $school->load('students');
        return view('schools.show', compact('school'));
    }

    public function edit(School $school)
    {
        return view('schools.edit', compact('school'));
    }

    public function update(Request $request, School $school)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
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
            'address' => $validated['address'],
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'logo' => $imagePath,
        ]);

        return redirect()->route('schools.index')->with('success', 'School updated successfully!');
    }

    public function destroy(School $school)
    {
        $school->delete();
        return redirect()->route('schools.index')->with('success', 'School deleted successfully!');
    }
}

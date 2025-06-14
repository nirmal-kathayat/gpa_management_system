<?php

namespace App\Http\Controllers;

use App\Models\GradeSystem;
use Illuminate\Http\Request;

class GradeSystemController extends Controller
{
    public function index()
    {
        $grades = GradeSystem::orderBy('grade_point', 'desc')->get();
        return view('grades.index', compact('grades'));
    }

    public function create()
    {
        return view('grades.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'letter_grade' => 'required|string|max:5|unique:grade_systems',
            'grade_point' => 'required|numeric|min:0|max:4',
            'marks_from' => 'required|integer|min:0|max:100',
            'marks_to' => 'required|integer|min:0|max:100|gte:marks_from',
            'remarks' => 'required|string|max:255'
        ]);

        GradeSystem::create($validated);

        return redirect()->route('grades.index')->with('success', 'Grade created successfully!');
    }

    public function edit(GradeSystem $grade)
    {
        return view('grades.edit', compact('grade'));
    }

    public function update(Request $request, GradeSystem $grade)
    {
        $validated = $request->validate([
            'letter_grade' => 'required|string|max:5|unique:grade_systems,letter_grade,' . $grade->id,
            'grade_point' => 'required|numeric|min:0|max:4',
            'marks_from' => 'required|integer|min:0|max:100',
            'marks_to' => 'required|integer|min:0|max:100|gte:marks_from',
            'remarks' => 'required|string|max:255'
        ]);

        $grade->update($validated);

        return redirect()->route('grades.index')->with('success', 'Grade updated successfully!');
    }

    public function destroy(GradeSystem $grade)
    {
        $grade->delete();
        return redirect()->route('grades.index')->with('success', 'Grade deleted successfully!');
    }
}

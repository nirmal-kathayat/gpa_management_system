<?php

namespace App\Http\Controllers;

use App\Models\GradeSystem;
use App\Support\TableResponse;
use Illuminate\Http\Request;

class GradeSystemController extends Controller
{
    /**
     * JSON rows for the TableHelper grid on the index page.
     */
    public function list(Request $request)
    {
        return TableResponse::make($request, GradeSystem::query(), [
            'search' => ['letter_grade', 'remarks'],
            'filters' => [
                'letter_grade' => 'letter_grade',
                'remarks' => 'remarks',
            ],
            'sort' => [
                'letter_grade' => 'letter_grade',
                'grade_point' => 'grade_point',
                'marks' => 'marks_from',
            ],
            'default' => ['grade_point', 'desc'],
        ], fn ($grade) => [
            'id' => $grade->id,
            'letter_grade' => $grade->letter_grade,
            'grade_point' => $grade->grade_point,
            'marks' => $grade->marks_from.' - '.$grade->marks_to,
            'remarks' => $grade->remarks,
        ]);
    }

    public function index()
    {
        // Rows are fetched by the grid from grades.list.
        return view('grades.index');
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

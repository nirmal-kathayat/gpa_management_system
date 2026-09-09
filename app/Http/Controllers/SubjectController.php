<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Support\TableResponse;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    /**
     * JSON rows for the TableHelper grid on the index page.
     */
    public function list(Request $request)
    {
        return TableResponse::make($request, Subject::query(), [
            'search' => ['name', 'code'],
            'filters' => [
                'name' => 'name',
                'code' => 'code',
                'is_active' => ['is_active', 'exact'],
            ],
            'sort' => [
                'name' => 'name',
                'code' => 'code',
                'full_marks' => 'full_marks',
            ],
            'default' => ['name', 'asc'],
        ], fn ($subject) => [
            'id' => $subject->id,
            'name' => $subject->name,
            'code' => $subject->code,
            'full_marks' => $subject->full_marks,
            'pass_marks' => $subject->pass_marks,
            'is_active' => (int) $subject->is_active,
        ]);
    }

    public function index()
    {
        // Rows are fetched by the grid from subjects.list.
        return view('subjects.index');
    }

    public function create()
    {
        return view('subjects.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:subjects',
            'full_marks' => 'required|integer|min:1|max:200',
            'pass_marks' => 'required|integer|min:1|max:100',
            'is_active' => 'boolean'
        ]);

        Subject::create($validated);

        return redirect()->route('subjects.index')->with('success', 'Subject created successfully!');
    }

    public function show(Subject $subject)
    {
        return view('subjects.show', compact('subject'));
    }

    public function edit(Subject $subject)
    {
        return view('subjects.edit', compact('subject'));
    }

    public function update(Request $request, Subject $subject)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:subjects,code,' . $subject->id,
            'full_marks' => 'required|integer|min:1|max:200',
            'pass_marks' => 'required|integer|min:1|max:100',
            'is_active' => 'boolean'
        ]);

        $subject->update($validated);

        return redirect()->route('subjects.index')->with('success', 'Subject updated successfully!');
    }

    public function destroy(Subject $subject)
    {
        $subject->delete();
        return redirect()->route('subjects.index')->with('success', 'Subject deleted successfully!');
    }
}

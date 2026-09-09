<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Support\TableResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    /**
     * JSON rows for the TableHelper grid on the index page.
     */
    public function list(Request $request)
    {
        $query = auth()->user()->getAccessibleStudents()->with('school:id,name');

        // The school detail page reuses this endpoint for one school only. It is
        // applied here, not as a filter, so a search inside the grid cannot widen
        // it back out to every school.
        if ($request->filled('school_id')) {
            $this->authorizeSchool((int) $request->input('school_id'));
            $query->where('school_id', (int) $request->input('school_id'));
        }

        return TableResponse::make($request, $query, [
            'search' => ['name', 'roll_number', 'class'],
            'filters' => [
                'name' => 'name',
                'class' => 'class',
                'section' => 'section',
                'roll_number' => 'roll_number',
                'school' => fn ($q, $v) => $q->whereHas('school', fn ($s) => $s->where('name', 'like', '%'.$v.'%')),
            ],
            'sort' => [
                'name' => 'name',
                'class' => 'class',
                'roll_number' => 'roll_number',
            ],
            'default' => ['name', 'asc'],
        ], fn ($student) => [
            'id' => $student->id,
            'name' => $student->name,
            'class' => $student->class,
            'section' => $student->section,
            'roll_number' => $student->roll_number,
            'school' => $student->school->name ?? '-',
        ]);
    }

    public function index()
    {
        // Rows are fetched by the grid from students.list.
        return view('students.index');
    }

    public function create()
    {
        $schools = $this->selectableSchools();
        return view('students.create', compact('schools'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'class' => 'required|string|max:50',
            'section' => 'required|string|max:10',
            'roll_number' => 'required|integer',
            'date_of_birth' => 'nullable|date',
            'father_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'school_id' => 'required|exists:schools,id'
        ]);

        $this->authorizeSchool((int) $validated['school_id']);

        Student::create($validated);

        return redirect()->route('students.index')->with('success', 'Student created successfully!');
    }

    public function show(Student $student)
    {
        $this->authorizeSchool($student->school_id);

        $student->load('school', 'marks.subject', 'reports');
        return view('students.show', compact('student'));
    }

    public function edit(Student $student)
    {
        $this->authorizeSchool($student->school_id);

        $schools = $this->selectableSchools();
        return view('students.edit', compact('student', 'schools'));
    }

    public function update(Request $request, Student $student)
    {
        $this->authorizeSchool($student->school_id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'class' => 'required|string|max:50',
            'section' => 'required|string|max:10',
            'roll_number' => 'required|integer',
            'date_of_birth' => 'nullable|date',
            'father_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'school_id' => 'required|exists:schools,id'
        ]);

        // Also blocks moving a student into a school the user cannot manage.
        $this->authorizeSchool((int) $validated['school_id']);

        $student->update($validated);

        return redirect()->route('students.index')->with('success', 'Student updated successfully!');
    }

    public function destroy(Student $student)
    {
        $this->authorizeSchool($student->school_id);

        $student->delete();
        return redirect()->route('students.index')->with('success', 'Student deleted successfully!');
    }
}

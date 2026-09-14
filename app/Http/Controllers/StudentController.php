<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Support\TableResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    /**
     * Moves an uploaded photo into public/assets/student and returns its path,
     * or null when the form did not carry one. Mirrors the school logo.
     */
    private function storePhoto(Request $request): ?string
    {
        if (! $request->hasFile('photo')) {
            return null;
        }

        $destination = public_path('assets/student/');
        if (! file_exists($destination)) {
            mkdir($destination, 0777, true);
        }

        $file = $request->file('photo');
        $name = time().'_'.$file->getClientOriginalName();
        $file->move($destination, $name);

        return 'assets/student/'.$name;
    }

    /**
     * Students for the Select2 picker on the report form: searched and paged on
     * the server, so a school with thousands of students never ships them all to
     * the browser.
     */
    public function options(Request $request)
    {
        $perPage = 30;
        $page = max(1, (int) $request->input('page', 1));
        $term = trim((string) $request->input('q', ''));

        $query = auth()->user()->getAccessibleStudents()->with('school:id,name');

        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', '%'.$term.'%')
                    ->orWhere('roll_number', 'like', '%'.$term.'%')
                    ->orWhere('symbol_number', 'like', '%'.$term.'%');
            });
        }

        $total = (clone $query)->count();

        $students = $query->orderBy('class')->orderBy('section')->orderBy('roll_number')
            ->forPage($page, $perPage)->get();

        return response()->json([
            'results' => $students->map(fn ($student) => [
                'id' => $student->id,
                'text' => static::studentLabel($student),
                'school' => $student->school->name,
                'inactive' => ! $student->is_active,
                // The report form copies these in as the card's own class.
                'class' => $student->class,
                'section' => $student->section,
                'roll_number' => $student->roll_number,
            ]),
            'pagination' => ['more' => $page * $perPage < $total],
        ]);
    }

    /** The one label format, so the picker and the pre-selected option match. */
    public static function studentLabel($student): string
    {
        return $student->name.' — Class '.$student->class.' '.$student->section
            .' (Roll '.$student->roll_number.')';
    }

    /**
     * JSON rows for the TableHelper grid on the index page.
     */
    public function list(Request $request)
    {
        $query = auth()->user()->getAccessibleStudents()->with('school:id,name')->withCount('reports');

        // The school detail page reuses this endpoint for one school only. It is
        // applied here, not as a filter, so a search inside the grid cannot widen
        // it back out to every school.
        if ($request->filled('school_id')) {
            $this->authorizeSchool((int) $request->input('school_id'));
            $query->where('school_id', (int) $request->input('school_id'));
        }

        return TableResponse::make($request, $query, [
            'search' => ['name', 'roll_number', 'class', 'symbol_number'],
            'filters' => [
                'name' => 'name',
                'class' => 'class',
                'section' => 'section',
                'roll_number' => 'roll_number',
                'school' => fn ($q, $v) => $q->whereHas('school', fn ($s) => $s->where('name', 'like', '%'.$v.'%')),
                'is_active' => ['is_active', 'exact'],
            ],
            'sort' => [
                'name' => 'name',
                'class' => 'class',
                'roll_number' => 'roll_number',
            ],
            // Newest first, so a student just added is the row you land on.
            'default' => ['id', 'desc'],
        ], fn ($student) => [
            'id' => $student->id,
            'name' => $student->name,
            'class' => $student->class,
            'section' => $student->section,
            'roll_number' => $student->roll_number,
            'school' => $student->school->name ?? '-',
            'is_active' => (int) $student->is_active,
            // So the delete confirmation can say how many report cards go too.
            'reports_count' => $student->reports_count,
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
            'symbol_number' => 'nullable|string|max:50',
            'gender' => 'nullable|in:Male,Female,Other',
            'date_of_birth' => 'nullable|date',
            'date_of_admission' => 'nullable|date',
            'father_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'guardian_name' => 'nullable|string|max:255',
            'guardian_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'is_active' => 'boolean',
            'school_id' => 'required|exists:schools,id'
        ]);

        $this->authorizeSchool((int) $validated['school_id']);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['photo'] = $this->storePhoto($request) ?? null;

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
            'symbol_number' => 'nullable|string|max:50',
            'gender' => 'nullable|in:Male,Female,Other',
            'date_of_birth' => 'nullable|date',
            'date_of_admission' => 'nullable|date',
            'father_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'guardian_name' => 'nullable|string|max:255',
            'guardian_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'is_active' => 'boolean',
            'school_id' => 'required|exists:schools,id'
        ]);

        // Also blocks moving a student into a school the user cannot manage.
        $this->authorizeSchool((int) $validated['school_id']);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['photo'] = $this->storePhoto($request) ?? $student->photo;

        $student->update($validated);

        return redirect()->route('students.index')->with('success', 'Student updated successfully!');
    }

    public function destroy(Student $student)
    {
        $this->authorizeSchool($student->school_id);

        $reports = $student->reports()->count();

        $student->delete();

        return redirect()->route('students.index')->with(
            'success',
            $student->name.' has been deleted'
            .($reports ? ', along with '.$reports.' report card'.($reports === 1 ? '' : 's') : '').'.'
        );
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentReport;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The academic years and, per school, the classes and sections. Small lists,
 * so one page holds both and edits happen in place.
 */
class AcademicStructureController extends Controller
{
    public function index(Request $request)
    {
        $schools = $this->selectableSchools();

        // Which school's classes are showing. Only ever one of the user's own.
        $schoolId = (int) $request->input('school_id');
        if (! $schools->contains('id', $schoolId)) {
            $schoolId = $schools->first()?->id;
        }

        $reportsByYear = StudentReport::selectRaw('academic_year, count(*) as n')
            ->groupBy('academic_year')->pluck('n', 'academic_year');

        $studentsByClass = Student::where('school_id', $schoolId)
            ->selectRaw('class, section, count(*) as n')
            ->groupBy('class', 'section')
            ->get()
            ->groupBy('class')
            ->map(fn ($rows) => $rows->pluck('n', 'section'));

        return view('structure.index', [
            'schools' => $schools,
            'schoolId' => $schoolId,
            'years' => AcademicYear::ordered()->get()
                ->map(fn ($year) => $year->setAttribute('reports_count', $reportsByYear[$year->year] ?? 0)),
            'classes' => $schoolId ? SchoolClass::where('school_id', $schoolId)->ordered()->get() : collect(),
            'studentsByClass' => $studentsByClass,
        ]);
    }

    // ---- Years ------------------------------------------------------------

    public function storeYear(Request $request)
    {
        $validated = $request->validate([
            'year' => ['required', 'regex:/^\d{4}$/', 'unique:academic_years,year'],
        ], [
            'year.regex' => 'Enter the academic year as four digits, e.g. 2082.',
            'year.unique' => 'That year is already in the list.',
        ]);

        $year = AcademicYear::create(['year' => $validated['year']]);

        // The first year, or one later than the current, becomes current.
        if ($request->boolean('make_current') || AcademicYear::count() === 1) {
            $year->makeCurrent();
        }

        return back()->with('success', 'Academic year '.$year->year.' added'.($year->is_current ? ' and set as current.' : '.'));
    }

    public function makeCurrent(AcademicYear $year)
    {
        $year->makeCurrent();

        return back()->with('success', $year->year.' is now the current academic year.');
    }

    public function destroyYear(AcademicYear $year)
    {
        $reports = StudentReport::where('academic_year', $year->year)->count();

        if ($reports > 0) {
            return back()->with('error', $year->year.' has '.$reports.' report card'.($reports === 1 ? '' : 's').' and cannot be removed.');
        }

        $year->delete();

        return back()->with('success', 'Academic year '.$year->year.' removed.');
    }

    // ---- Classes ----------------------------------------------------------

    private function validateClass(Request $request, ?SchoolClass $class = null): array
    {
        $validated = $request->validate([
            'school_id' => ['required', 'exists:schools,id'],
            'name' => [
                'required', 'string', 'max:50',
                Rule::unique('school_classes')
                    ->where(fn ($q) => $q->where('school_id', $request->input('school_id')))
                    ->ignore($class?->id),
            ],
            'sections' => ['required', 'string', 'max:200'],
        ], [
            'name.unique' => 'That school already has this class.',
        ]);

        $this->authorizeSchool((int) $validated['school_id']);

        // "A, B, C" -> ["A", "B", "C"], trimmed, no blanks or repeats.
        $sections = collect(preg_split('/[\s,]+/', $validated['sections']))
            ->map(fn ($s) => trim($s))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (! $sections) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'sections' => 'Give the class at least one section, e.g. A.',
            ]);
        }

        return [
            'school_id' => $validated['school_id'],
            'name' => trim($validated['name']),
            'sections' => $sections,
            'sort_order' => is_numeric(trim($validated['name'])) ? (int) trim($validated['name']) : 100,
        ];
    }

    public function storeClass(Request $request)
    {
        $class = SchoolClass::create($this->validateClass($request));

        return redirect()->route('structure.index', ['school_id' => $class->school_id])
            ->with('success', 'Class '.$class->name.' added with section'.(count($class->sections) === 1 ? '' : 's').' '.implode(', ', $class->sections).'.');
    }

    public function updateClass(Request $request, SchoolClass $class)
    {
        $this->authorizeSchool($class->school_id);

        $validated = $this->validateClass($request, $class);

        // The school a class belongs to is not something to move.
        $validated['school_id'] = $class->school_id;

        // A section that still has students cannot be dropped.
        $dropped = array_diff($class->sections ?? [], $validated['sections']);
        $occupied = Student::where('school_id', $class->school_id)
            ->where('class', $class->name)
            ->whereIn('section', $dropped)
            ->selectRaw('section, count(*) as n')
            ->groupBy('section')
            ->pluck('n', 'section');

        if ($occupied->isNotEmpty()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'sections' => 'Section '.$occupied->keys()->implode(', ').' still has students. Move them first.',
            ]);
        }

        // Renaming the class renames the students in it; report cards keep
        // the name they were issued under.
        if ($validated['name'] !== $class->name) {
            Student::where('school_id', $class->school_id)->where('class', $class->name)
                ->update(['class' => $validated['name']]);
        }

        $class->update($validated);

        return redirect()->route('structure.index', ['school_id' => $class->school_id])
            ->with('success', 'Class '.$class->name.' updated.');
    }

    public function destroyClass(SchoolClass $class)
    {
        $this->authorizeSchool($class->school_id);

        $students = Student::where('school_id', $class->school_id)->where('class', $class->name)->count();

        if ($students > 0) {
            return back()->with('error', 'Class '.$class->name.' has '.$students.' student'.($students === 1 ? '' : 's').' and cannot be removed. Move or promote them first.');
        }

        $class->delete();

        return redirect()->route('structure.index', ['school_id' => $class->school_id])
            ->with('success', 'Class '.$class->name.' removed.');
    }
}

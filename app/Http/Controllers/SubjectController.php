<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\GradeSystem;
use App\Models\StudentMark;
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
            'description' => $subject->description,
            'is_active' => (int) $subject->is_active,
        ]);
    }

    public function index()
    {
        // Rows are fetched by the grid from subjects.list.
        return view('subjects.index');
    }

    /**
     * Subjects are added and edited through a modal on the index page, so there
     * are no create/edit screens; old links land on the list with it open.
     */
    public function create()
    {
        return redirect()->route('subjects.index', ['add' => 1]);
    }

    /**
     * The modal is on the listing and on the dashboard, so a save goes back to
     * whichever one it was opened from. A route name, never a URL, so the form
     * cannot send the user anywhere else.
     */
    private function backTo(Request $request): string
    {
        return $request->input('return_to') === 'dashboard' ? 'dashboard' : 'subjects.index';
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:subjects',
            'description' => 'nullable|string|max:2000',
            'full_marks' => 'required|integer|min:1|max:200',
            'pass_marks' => 'required|integer|min:1|max:100',
            'is_active' => 'boolean'
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        Subject::create($validated);

        return redirect()->route($this->backTo($request))->with('success', 'Subject created successfully!');
    }

    public function show(Subject $subject)
    {
        // Counted over the final terminal, which is the mark that decides the
        // year; a student with no mark for this subject is not counted at all.
        $marks = StudentMark::where('subject_id', $subject->id)->where('exam_type', 'final_terminal');

        $total = (clone $marks)->distinct()->count('student_id');
        $passed = (clone $marks)->where('total_marks', '>=', $subject->pass_marks)->distinct()->count('student_id');

        return view('subjects.show', [
            'subject' => $subject,
            'totalStudents' => $total,
            'passedStudents' => $passed,
            'failedStudents' => max(0, $total - $passed),
            'averageScore' => (clone $marks)->avg('total_marks'),
            'grades' => GradeSystem::orderByDesc('grade_point')->get(),
        ]);
    }

    public function edit(Subject $subject)
    {
        return redirect()->route('subjects.index', ['edit' => $subject->id]);
    }

    public function update(Request $request, Subject $subject)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:subjects,code,' . $subject->id,
            'description' => 'nullable|string|max:2000',
            'full_marks' => 'required|integer|min:1|max:200',
            'pass_marks' => 'required|integer|min:1|max:100',
            'is_active' => 'boolean'
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $subject->update($validated);

        return redirect()->route($this->backTo($request))->with('success', 'Subject updated successfully!');
    }

    public function destroy(Subject $subject)
    {
        $subject->delete();
        return redirect()->route('subjects.index')->with('success', 'Subject deleted successfully!');
    }
}

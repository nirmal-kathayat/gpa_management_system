<?php

namespace App\Http\Controllers;

use App\Models\GradeSystem;
use App\Models\StudentMark;
use App\Support\GradeCalculator;
use App\Support\TableResponse;
use Database\Seeders\GradeScaleSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GradeSystemController extends Controller
{
    /**
     * JSON rows for the TableHelper grid on the index page.
     */
    public function list(Request $request)
    {
        return TableResponse::make($request, GradeSystem::query(), [
            'search' => ['letter_grade', 'description'],
            'filters' => [
                'letter_grade' => 'letter_grade',
                'description' => 'description',
                'is_failing' => ['is_failing', 'exact'],
            ],
            'sort' => [
                'letter_grade' => 'letter_grade',
                'grade_point' => 'grade_point',
                'marks' => 'marks_from',
            ],
            'default' => ['marks_from', 'desc'],
        ], fn ($grade) => [
            'id' => $grade->id,
            'letter_grade' => $grade->letter_grade,
            'grade_point' => number_format($grade->grade_point, 1),
            'marks' => $grade->marks_from.' - '.$grade->marks_to.'%',
            'marks_from' => $grade->marks_from,
            'marks_to' => $grade->marks_to,
            'description' => $grade->description,
            'is_failing' => (int) $grade->is_failing,
            'is_active' => (int) $grade->is_active,
        ]);
    }

    public function index()
    {
        $calculator = new GradeCalculator();

        return view('grades.index', [
            'passMark' => $calculator->passMark(),
            'gaps' => $calculator->gaps(),
            'overlaps' => $calculator->overlaps(),
            'bandCount' => $calculator->scale()->count(),
            'resultBands' => GradeCalculator::RESULT_BANDS,
        ]);
    }

    /**
     * Grades are added and edited through a modal on the index page, so there
     * are no create/edit screens; old links land on the list with it open.
     */
    public function create()
    {
        return redirect()->route('grades.index', ['add' => 1]);
    }

    public function edit(GradeSystem $grade)
    {
        return redirect()->route('grades.index', ['edit' => $grade->id]);
    }

    public function store(Request $request)
    {
        $grade = GradeSystem::create($this->validated($request));

        return redirect()->route('grades.index')
            ->with('success', 'Grade '.$grade->letter_grade.' created successfully!');
    }

    public function update(Request $request, GradeSystem $grade)
    {
        $grade->update($this->validated($request, $grade));

        return redirect()->route('grades.index')
            ->with('success', 'Grade '.$grade->letter_grade.' updated successfully!');
    }

    public function destroy(GradeSystem $grade)
    {
        // Marks store the letter they were awarded, so deleting a band does not
        // rewrite history - but it does silently change how the next report is
        // graded. Switching it off keeps both the record and the scale honest.
        if (StudentMark::where('letter_grade', $grade->letter_grade)->exists()) {
            return redirect()->route('grades.index')->with(
                'error',
                'Grade '.$grade->letter_grade.' has already been awarded to students. '
                .'Switch it off instead of deleting it.'
            );
        }

        $letter = $grade->letter_grade;
        $grade->delete();

        return redirect()->route('grades.index')->with('success', 'Grade '.$letter.' deleted successfully!');
    }

    /**
     * Replaces the scale with the NEB standard. Offered on the index page when
     * the scale is empty or does not cover every mark.
     */
    public function loadStandard()
    {
        DB::transaction(function () {
            GradeSystem::query()->delete();
            (new GradeScaleSeeder())->run();
        });

        return redirect()->route('grades.index')
            ->with('success', 'The NEB standard grading scale has been loaded.');
    }

    /**
     * Bands may not overlap: two bands claiming the same mark would make the
     * grade depend on row order.
     */
    private function validated(Request $request, ?GradeSystem $grade = null): array
    {
        $noOverlap = function (string $attribute, $value, $fail) use ($request, $grade) {
            $from = (int) $request->input('marks_from');
            $to = (int) $request->input('marks_to');

            $clash = GradeSystem::query()
                ->when($grade, fn ($q) => $q->whereKeyNot($grade->id))
                ->where('marks_from', '<=', $to)
                ->where('marks_to', '>=', $from)
                ->first();

            if ($clash) {
                $fail('This range overlaps '.$clash->letter_grade
                    .' ('.$clash->marks_from.'-'.$clash->marks_to.'%).');
            }
        };

        $validated = $request->validate([
            'letter_grade' => ['required', 'string', 'max:5', Rule::unique('grade_systems')->ignore($grade?->id)],
            'grade_point' => 'required|numeric|min:0|max:4',
            'marks_from' => ['required', 'integer', 'min:0', 'max:100'],
            'marks_to' => ['required', 'integer', 'min:0', 'max:100', 'gte:marks_from', $noOverlap],
            'description' => 'nullable|string|max:255',
            'is_failing' => 'boolean',
            'is_active' => 'boolean',
        ], [
            'marks_to.gte' => 'The upper mark cannot be lower than the lower mark.',
        ]);

        $validated['is_failing'] = $request->boolean('is_failing');
        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }
}

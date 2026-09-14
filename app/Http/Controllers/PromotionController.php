<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PicksClass;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Year end: a class moves up, or its last year leaves the school. Only the
 * student records change - every report card keeps the class it was issued
 * under, which is the point of the class being written on the card.
 */
class PromotionController extends Controller
{
    use PicksClass;

    public const LEAVE = '__leave__';

    public function index(Request $request)
    {
        $schools = $this->selectableSchools();
        $filters = $this->classFilters($request, $schools->pluck('id')->all());
        $complete = ! in_array(null, $filters, true);

        $data = [
            'schools' => $schools,
            'classMap' => $this->classMap($schools->pluck('id')->all()),
            'filters' => $filters,
            'students' => null,
            'suggested' => null,
            'targetCounts' => [],
        ];

        if ($complete) {
            $this->authorizeSchool($filters['school_id']);

            $data['students'] = $this->classList($filters);
            $data['suggested'] = $this->nextClass($filters['school_id'], $filters['class']);
            // How many already sit in each class/section, so a target can be
            // judged before the move.
            $data['targetCounts'] = Student::where('school_id', $filters['school_id'])
                ->where('is_active', true)
                ->selectRaw("class, section, count(*) as n")
                ->groupBy('class', 'section')
                ->get()
                ->mapWithKeys(fn ($r) => [$r->class.'|'.$r->section => (int) $r->n])
                ->all();
        }

        return view('promotion.index', $data);
    }

    /**
     * The students in the class now, each with their card for the chosen
     * year so a failed student can be held back at a glance.
     */
    private function classList(array $filters)
    {
        $students = Student::where('school_id', $filters['school_id'])
            ->where('class', $filters['class'])
            ->where('section', $filters['section'])
            ->where('is_active', true)
            ->orderBy('roll_number')
            ->orderBy('name')
            ->get();

        $reports = StudentReport::whereIn('student_id', $students->pluck('id'))
            ->where('academic_year', $filters['academic_year'])
            ->get()
            ->keyBy('student_id');

        return $students->map(fn ($student) => [
            'student' => $student,
            'report' => $reports->get($student->id),
        ]);
    }

    /** The class after this one in the school's list, if there is one. */
    private function nextClass(int $schoolId, string $class): ?SchoolClass
    {
        $classes = SchoolClass::where('school_id', $schoolId)->ordered()->get()->values();
        $index = $classes->search(fn ($c) => $c->name === $class);

        return $index === false ? null : $classes->get($index + 1);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'school_id' => ['required', 'exists:schools,id'],
            'class' => ['required', 'string'],
            'section' => ['required', 'string'],
            'academic_year' => ['nullable', 'regex:/^\d{4}$/'],
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer'],
            'to_class' => ['required', 'string'],
            'to_section' => ['nullable', 'string'],
            'roll_mode' => ['required', Rule::in(['keep', 'name', 'rank'])],
        ], [
            'student_ids.required' => 'Tick at least one student.',
        ]);

        $this->authorizeSchool((int) $validated['school_id']);

        $leaving = $validated['to_class'] === self::LEAVE;

        if (! $leaving) {
            if (! SchoolClass::runs((int) $validated['school_id'], $validated['to_class'], (string) ($validated['to_section'] ?? ''))) {
                throw ValidationException::withMessages(['to_section' => 'Choose a class and section the school runs.']);
            }

            if ($validated['to_class'] === $validated['class'] && $validated['to_section'] === $validated['section']) {
                throw ValidationException::withMessages(['to_class' => 'That is the class they are already in.']);
            }
        }

        // Only students actually in the class being moved, whatever ids came in.
        $students = Student::where('school_id', $validated['school_id'])
            ->where('class', $validated['class'])
            ->where('section', $validated['section'])
            ->where('is_active', true)
            ->whereIn('id', $validated['student_ids'])
            ->orderBy('roll_number')
            ->orderBy('name')
            ->get();

        if ($students->isEmpty()) {
            throw ValidationException::withMessages(['student_ids' => 'None of those students are in that class any more. Reload the page.']);
        }

        DB::transaction(function () use ($students, $validated, $leaving) {
            if ($leaving) {
                Student::whereIn('id', $students->pluck('id'))->update(['is_active' => false]);

                return;
            }

            $ordered = $this->orderForRolls($students, $validated);

            // New rolls carry on after whoever is already in the target section.
            $next = 1 + (int) Student::where('school_id', $validated['school_id'])
                ->where('class', $validated['to_class'])
                ->where('section', $validated['to_section'])
                ->max('roll_number');

            foreach ($ordered as $student) {
                $student->update([
                    'class' => $validated['to_class'],
                    'section' => $validated['to_section'],
                    'roll_number' => $validated['roll_mode'] === 'keep' ? $student->roll_number : $next++,
                ]);
            }
        });

        $count = $students->count();
        $who = $count === 1 ? '1 student' : "{$count} students";
        $from = 'class '.$validated['class'].' '.$validated['section'];

        return redirect()->route('promotion.index', [
            'school_id' => $validated['school_id'],
            'class' => $validated['class'],
            'section' => $validated['section'],
            'academic_year' => $validated['academic_year'] ?? null,
        ])->with('success', $leaving
            ? "{$who} from {$from} marked as left. Their report cards are kept."
            : "{$who} moved from {$from} to class {$validated['to_class']} {$validated['to_section']}.");
    }

    /** The order new roll numbers are handed out in. */
    private function orderForRolls($students, array $validated)
    {
        return match ($validated['roll_mode']) {
            'name' => $students->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values(),
            'rank' => (function () use ($students, $validated) {
                $gpa = StudentReport::whereIn('student_id', $students->pluck('id'))
                    ->where('academic_year', $validated['academic_year'] ?? '')
                    ->pluck('final_gpa', 'student_id');

                // Highest GPA first; those without a card go last, by name.
                return $students->sortBy([
                    fn ($a, $b) => (float) ($gpa[$b->id] ?? -1) <=> (float) ($gpa[$a->id] ?? -1),
                    fn ($a, $b) => strnatcasecmp($a->name, $b->name),
                ])->values();
            })(),
            default => $students,
        };
    }
}

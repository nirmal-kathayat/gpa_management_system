<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\Student;
use App\Models\StudentReport;
use App\Models\User;

class DashboardController extends Controller
{
    /**
     * GPA bands shown in the distribution panel, high to low.
     * Ranges are half-open [min, max) so every report falls in exactly one.
     */
    private const GPA_BANDS = [
        ['label' => '3.6 - 4.0', 'min' => 3.6, 'max' => 4.01],
        ['label' => '3.1 - 3.5', 'min' => 3.1, 'max' => 3.6],
        ['label' => '2.6 - 3.0', 'min' => 2.6, 'max' => 3.1],
        ['label' => '2.1 - 2.5', 'min' => 2.1, 'max' => 2.6],
        ['label' => 'Below 2.0', 'min' => 0.0, 'max' => 2.1],
    ];

    public function index()
    {
        $user = auth()->user();
        $isAdmin = $user->isAdmin();

        // Teachers and staff only ever see their own school's numbers.
        $schools = School::query();
        $students = Student::query();
        $reports = StudentReport::query();
        $teachers = User::where('role', 'teacher');

        if (!$isAdmin) {
            $schools->where('id', $user->school_id);
            $students->where('school_id', $user->school_id);
            $teachers->where('school_id', $user->school_id);
            $reports->whereIn('student_id', Student::where('school_id', $user->school_id)->select('id'));
        }

        return view('dashboard.index', [
            'isAdmin' => $isAdmin,
            'stats' => $this->stats($isAdmin, $schools, $students, $teachers, $reports),
            'schoolRows' => $this->schoolRows($schools),
            'gpaBands' => $this->gpaBands($reports),
            'attention' => $this->attention($schools, $students),
            'academicYear' => (clone $reports)->max('academic_year'),
        ]);
    }

    /**
     * The four cards. Deltas are measured against real records rather than a
     * stored baseline: counts over the last 30 days, GPA against the previous
     * academic year.
     */
    private function stats(bool $isAdmin, $schools, $students, $teachers, $reports): array
    {
        $since = now()->subDays(30);
        $averageGpa = (clone $reports)->avg('final_gpa');

        $first = $isAdmin
            ? [
                'label' => 'Total Schools',
                'value' => number_format((clone $schools)->count()),
                'delta' => $this->countDelta((clone $schools)->where('created_at', '>=', $since)->count()),
            ]
            : [
                'label' => 'Report Cards',
                'value' => number_format((clone $reports)->count()),
                'delta' => $this->countDelta((clone $reports)->where('created_at', '>=', $since)->count()),
            ];

        return [
            $first,
            [
                'label' => 'Total Students',
                'value' => number_format((clone $students)->count()),
                'delta' => $this->countDelta((clone $students)->where('created_at', '>=', $since)->count()),
            ],
            [
                'label' => 'Teachers',
                'value' => number_format((clone $teachers)->count()),
                'delta' => $this->countDelta((clone $teachers)->where('created_at', '>=', $since)->count()),
            ],
            [
                'label' => 'Average GPA',
                'value' => $averageGpa ? number_format($averageGpa, 2) : '—',
                'delta' => $this->gpaDelta($reports),
            ],
        ];
    }

    private function countDelta(int $added): array
    {
        return $added > 0
            ? ['text' => '+' . number_format($added) . ' in the last 30 days', 'tone' => 'positive']
            : ['text' => 'No change in the last 30 days', 'tone' => 'muted'];
    }

    /**
     * Compares the two most recent academic years present in the reports.
     */
    private function gpaDelta($reports): array
    {
        $years = (clone $reports)->select('academic_year')
            ->distinct()
            ->orderByDesc('academic_year')
            ->limit(2)
            ->pluck('academic_year');

        if ($years->count() < 2) {
            return ['text' => 'No earlier year to compare', 'tone' => 'muted'];
        }

        $current = (clone $reports)->where('academic_year', $years[0])->avg('final_gpa');
        $previous = (clone $reports)->where('academic_year', $years[1])->avg('final_gpa');
        $change = round($current - $previous, 2);

        if ($change == 0.0) {
            return ['text' => 'Level with ' . $years[1], 'tone' => 'muted'];
        }

        return [
            'text' => ($change > 0 ? '+' : '') . number_format($change, 2) . ' vs ' . $years[1],
            'tone' => $change > 0 ? 'positive' : 'danger',
        ];
    }

    /**
     * Recently added schools with their student count and average GPA.
     * "Status" reflects real state: a school with no students is still in setup.
     */
    private function schoolRows($schools)
    {
        $rows = (clone $schools)->withCount('students')
            ->latest()
            ->take(6)
            ->get();

        $gpaBySchool = StudentReport::join('students', 'students.id', '=', 'student_reports.student_id')
            ->whereIn('students.school_id', $rows->pluck('id'))
            ->groupBy('students.school_id')
            ->selectRaw('students.school_id as school_id, avg(student_reports.final_gpa) as avg_gpa')
            ->pluck('avg_gpa', 'school_id');

        return $rows->map(fn (School $school) => [
            'name' => $school->name,
            'address' => $school->address ?: '—',
            'students' => number_format($school->students_count),
            'gpa' => isset($gpaBySchool[$school->id]) ? number_format($gpaBySchool[$school->id], 2) : '—',
            'status' => $school->students_count > 0 ? 'Active' : 'Setup',
            'statusTone' => $school->students_count > 0 ? 'positive' : 'warning',
        ]);
    }

    private function gpaBands($reports): array
    {
        $total = (clone $reports)->count();

        return array_map(function (array $band) use ($reports, $total) {
            $count = $total
                ? (clone $reports)->where('final_gpa', '>=', $band['min'])
                    ->where('final_gpa', '<', $band['max'])
                    ->count()
                : 0;

            return [
                'label' => $band['label'],
                'count' => $count,
                'pct' => $total ? (int) round($count / $total * 100) : 0,
            ];
        }, self::GPA_BANDS);
    }

    /**
     * Real gaps in the data, not a workflow queue: schools without students,
     * then students who have no report card yet.
     */
    private function attention($schools, $students)
    {
        $items = (clone $schools)->doesntHave('students')
            ->orderBy('name')
            ->take(3)
            ->get()
            ->map(fn (School $school) => [
                'title' => $school->name,
                'subtitle' => 'No students added yet',
                'tag' => 'Setup',
            ]);

        if ($items->count() < 4) {
            $items = $items->concat(
                (clone $students)->doesntHave('reports')
                    ->with('school')
                    ->orderBy('name')
                    ->take(4 - $items->count())
                    ->get()
                    ->map(fn (Student $student) => [
                        'title' => $student->name,
                        'subtitle' => ($student->school->name ?? 'No school') . ' · class ' . $student->class,
                        'tag' => 'No report',
                    ])
            );
        }

        return $items;
    }
}

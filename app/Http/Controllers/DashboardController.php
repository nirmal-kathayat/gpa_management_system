<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\Student;
use App\Models\StudentReport;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;

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

    public function index(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $user->isAdmin();

        // Non-admins only ever see their own school's numbers.
        $schools = School::query();
        $students = Student::query();
        $reports = StudentReport::query();
        $users = User::query();

        // Roles are created and deleted by admins, so this cannot name one:
        // anybody who is not an administrator is counted as staff.
        $staff = User::whereDoesntHave('roles', fn ($q) => $q->where('name', 'admin'));

        if (! $isAdmin) {
            $schools->where('id', $user->school_id);
            $students->where('school_id', $user->school_id);
            $staff->where('school_id', $user->school_id);
            $users->where('school_id', $user->school_id);
            $reports->whereIn('student_id', Student::where('school_id', $user->school_id)->select('id'));
        }

        // Subjects are shared across schools, so they are never scoped.
        $subjects = Subject::query();

        $years = (clone $reports)->distinct()->orderByDesc('academic_year')->pluck('academic_year');

        // The picker only accepts a year that actually has report cards.
        $year = $years->contains($request->query('year')) ? $request->query('year') : $years->first();
        $yearReports = $year ? (clone $reports)->where('academic_year', $year) : clone $reports;

        return view('dashboard.index', [
            'isAdmin' => $isAdmin,
            'years' => $years,
            'academicYear' => $year,
            'stats' => $this->stats($isAdmin, $schools, $students, $staff, $subjects, $reports),
            'trend' => $this->trend($reports),
            'gpaBands' => $this->gpaBands($yearReports),
            'gpaTotal' => (clone $yearReports)->count(),
            'schoolRows' => $this->schoolRows($schools),
            'studentRows' => $this->studentRows($students),
            'activities' => $this->activities($schools, $students, $users),
        ]);
    }

    /**
     * The five cards. Deltas are measured against real records rather than a
     * stored baseline: what was added this calendar year, and the average GPA
     * against the previous academic year.
     */
    private function stats(bool $isAdmin, $schools, $students, $staff, $subjects, $reports): array
    {
        $since = now()->startOfYear();
        $averageGpa = (clone $reports)->avg('final_gpa');

        $first = $isAdmin
            ? [
                'label' => 'Total Schools',
                'icon' => 'fa-school',
                'tone' => 'is-blue',
                'value' => number_format((clone $schools)->count()),
                'delta' => $this->countDelta((clone $schools)->where('created_at', '>=', $since)->count()),
            ]
            : [
                'label' => 'Report Cards',
                'icon' => 'fa-file-lines',
                'tone' => 'is-blue',
                'value' => number_format((clone $reports)->count()),
                'delta' => $this->countDelta((clone $reports)->where('created_at', '>=', $since)->count()),
            ];

        return [
            $first,
            [
                'label' => 'Total Students',
                'icon' => 'fa-user-graduate',
                'tone' => 'is-green',
                'value' => number_format((clone $students)->count()),
                'delta' => $this->countDelta((clone $students)->where('created_at', '>=', $since)->count()),
            ],
            [
                'label' => 'Total Staff',
                'icon' => 'fa-users',
                'tone' => 'is-purple',
                'value' => number_format((clone $staff)->count()),
                'delta' => $this->countDelta((clone $staff)->where('created_at', '>=', $since)->count()),
            ],
            [
                'label' => 'Total Subjects',
                'icon' => 'fa-book',
                'tone' => 'is-amber',
                'value' => number_format((clone $subjects)->count()),
                'delta' => $this->countDelta((clone $subjects)->where('created_at', '>=', $since)->count()),
            ],
            [
                'label' => 'Average GPA',
                'icon' => 'fa-chart-column',
                'tone' => 'is-rose',
                'value' => $averageGpa ? number_format($averageGpa, 2) : '—',
                'delta' => $this->gpaDelta($reports),
            ],
        ];
    }

    private function countDelta(int $added): array
    {
        return $added > 0
            ? ['text' => '+' . number_format($added) . ' this year', 'tone' => 'positive']
            : ['text' => 'No change this year', 'tone' => 'muted'];
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
     * How many students were graded in each academic year, and how many schools
     * they came from - the only growth the data can actually evidence.
     */
    private function trend($reports): array
    {
        $rows = (clone $reports)
            ->join('students', 'students.id', '=', 'student_reports.student_id')
            ->groupBy('student_reports.academic_year')
            ->orderByDesc('student_reports.academic_year')
            ->limit(5)
            ->selectRaw('student_reports.academic_year as year')
            ->selectRaw('count(distinct student_reports.student_id) as students')
            ->selectRaw('count(distinct students.school_id) as schools')
            ->get()
            ->reverse()
            ->values();

        // Both series share one axis, so they are measured against one maximum.
        $max = max(1, (int) $rows->max('students'), (int) $rows->max('schools'));

        return $rows->map(fn ($row) => [
            'year' => $row->year,
            'students' => (int) $row->students,
            'schools' => (int) $row->schools,
            'studentsPct' => (int) round($row->students / $max * 100),
            'schoolsPct' => (int) round($row->schools / $max * 100),
        ])->all();
    }

    /**
     * Recently added schools with their student count and average GPA.
     * "Status" reflects real state: a school with no students is still in setup.
     */
    private function schoolRows($schools)
    {
        $rows = (clone $schools)->withCount('students')
            ->latest()
            ->take(5)
            ->get();

        $gpaBySchool = StudentReport::join('students', 'students.id', '=', 'student_reports.student_id')
            ->whereIn('students.school_id', $rows->pluck('id'))
            ->groupBy('students.school_id')
            ->selectRaw('students.school_id as school_id, avg(student_reports.final_gpa) as avg_gpa')
            ->pluck('avg_gpa', 'school_id');

        return $rows->map(fn (School $school) => [
            'id' => $school->id,
            'name' => $school->name,
            'address' => $school->address ?: '—',
            'students' => number_format($school->students_count),
            'gpa' => isset($gpaBySchool[$school->id]) ? number_format($gpaBySchool[$school->id], 2) : '—',
            'status' => $school->students_count > 0 ? 'Active' : 'Setup',
            'statusTone' => $school->students_count > 0 ? 'positive' : 'warning',
            'added' => $school->created_at?->format('d M Y') ?: '—',
        ]);
    }

    private function studentRows($students)
    {
        return (clone $students)->with('school')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn (Student $student) => [
                'id' => $student->id,
                'name' => $student->name,
                'school' => $student->school->name ?? '—',
                'class' => $student->class,
                'added' => $student->created_at?->format('d M Y') ?: '—',
            ]);
    }

    /**
     * There is no activity log, so the feed is assembled from the timestamps
     * the records already carry. Nothing here is recorded for its own sake.
     */
    private function activities($schools, $students, $users)
    {
        $items = collect()
            ->concat((clone $students)->with('school')->latest()->take(5)->get()
                ->map(fn (Student $student) => [
                    'icon' => 'fa-user-graduate',
                    'tone' => 'is-green',
                    'title' => 'New student added',
                    'text' => $student->name . ' was added to ' . ($student->school->name ?? 'no school'),
                    'at' => $student->created_at,
                ]))
            ->concat((clone $schools)->latest()->take(3)->get()
                ->map(fn (School $school) => [
                    'icon' => 'fa-school',
                    'tone' => 'is-blue',
                    'title' => 'New school added',
                    'text' => $school->name . ' was created',
                    'at' => $school->created_at,
                ]))
            ->concat(Subject::whereColumn('updated_at', '>', 'created_at')
                ->latest('updated_at')->take(3)->get()
                ->map(fn (Subject $subject) => [
                    'icon' => 'fa-book',
                    'tone' => 'is-purple',
                    'title' => 'Subject updated',
                    'text' => $subject->name . ' details were updated',
                    'at' => $subject->updated_at,
                ]))
            ->concat((clone $users)->whereNotNull('last_login_at')
                ->orderByDesc('last_login_at')->take(3)->get()
                ->map(fn (User $user) => [
                    'icon' => 'fa-right-to-bracket',
                    'tone' => 'is-amber',
                    'title' => 'User login',
                    'text' => $user->name . ' signed in',
                    'at' => $user->last_login_at,
                ]));

        return $items->filter(fn (array $item) => $item['at'] !== null)
            ->sortByDesc('at')
            ->take(5)
            ->values()
            ->map(fn (array $item) => $item + ['ago' => $item['at']->diffForHumans()]);
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
}

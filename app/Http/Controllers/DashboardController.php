<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\School;
use App\Models\Subject;
use App\Models\StudentReport;
use App\Models\GradeSystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $totalStudents = Student::count();
        $totalSchools = School::count();
        $totalSubjects = Subject::where('is_active', true)->count();
        $totalReports = StudentReport::count();

        // Recent reports
        $recentReports = StudentReport::with('student')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Grade distribution
        $gradeDistribution = StudentReport::select('final_grade', DB::raw('count(*) as count'))
            ->groupBy('final_grade')
            ->orderBy('final_grade')
            ->get();

        // Top performing students
        $topStudents = StudentReport::with('student')
            ->orderBy('final_gpa', 'desc')
            ->take(10)
            ->get();

        // Monthly report creation stats
        $monthlyStats = StudentReport::select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('YEAR(created_at) as year'),
                DB::raw('count(*) as count')
            )
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        return view('dashboard.index', compact(
            'totalStudents',
            'totalSchools', 
            'totalSubjects',
            'totalReports',
            'recentReports',
            'gradeDistribution',
            'topStudents',
            'monthlyStats'
        ));
    }
}

<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// API routes for mobile app or external integrations
Route::prefix('v1')->group(function () {
    // Public routes
    Route::get('/schools', function () {
        return \App\Models\School::select('id', 'name', 'address')->get();
    });
    
    Route::get('/subjects', function () {
        return \App\Models\Subject::where('is_active', true)->get();
    });
    
    Route::get('/grade-system', function () {
        return \App\Models\GradeSystem::orderBy('grade_point', 'desc')->get();
    });
    
    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/students', function (Request $request) {
            return $request->user()->getAccessibleStudents()->with('school')->paginate(20);
        });
        
        Route::get('/reports', function (Request $request) {
            $query = \App\Models\StudentReport::with('student.school');
            
            if (!$request->user()->isAdmin()) {
                $query->whereHas('student', function ($q) use ($request) {
                    $q->where('school_id', $request->user()->school_id);
                });
            }
            
            return $query->paginate(20);
        });
    });
});

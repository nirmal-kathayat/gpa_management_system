<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\GradeSystemController;
use App\Http\Controllers\MarksEntryController;
use App\Http\Controllers\ClassResultController;
use App\Http\Controllers\AcademicStructureController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\BulkImportController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;

/**
 * Registers the seven resource routes, each behind the matching permission for
 * that module (students.viewAny, students.create, ...). Authorisation stays
 * visible here in the route table rather than hidden in controllers.
 */
$resource = function (string $name, string $controller, array $except = []) {
    // Same order Route::resource uses, so /students/create is matched before
    // the /students/{student} wildcard.
    $abilities = [
        'index' => 'viewAny',
        'create' => 'create',
        'store' => 'create',
        'show' => 'viewAny',
        'edit' => 'update',
        'update' => 'update',
        'destroy' => 'delete',
    ];

    // The table's JSON endpoint. Registered first so /students/list is not read
    // as /students/{student}.
    Route::get("{$name}/list", [$controller, 'list'])
        ->name("{$name}.list")
        ->middleware("permission:{$name}.viewAny");

    foreach (array_diff_key($abilities, array_flip($except)) as $action => $ability) {
        Route::resource($name, $controller)
            ->only($action)
            ->middleware("permission:{$name}.{$ability}");
    }
};

// Authentication Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:20,1');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Protected Routes
Route::middleware(['auth', 'active'])->group(function () use ($resource) {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile Routes - every signed-in user manages their own account.
    Route::get('/profile', [UserController::class, 'profile'])->name('users.profile');
    Route::put('/profile', [UserController::class, 'updateProfile'])->name('users.profile.update');

    // Before the resource, so /students/options is not read as a student id.
    // Anyone who can build a report needs it, not only those who manage students.
    Route::get('students/options', [StudentController::class, 'options'])
        ->name('students.options')
        ->middleware('permission:students.viewAny|reports.create|reports.update');

    $resource('students', StudentController::class);

    // One subject's marks for a whole class at once. Every saved mark lands on
    // the student's report card for that year, created if they have none yet.
    Route::get('marks', [MarksEntryController::class, 'index'])
        ->name('marks.index')
        ->middleware('permission:marks.viewAny');
    // The ledger's rows, paged and searched by the grid.
    Route::get('marks/rows', [MarksEntryController::class, 'rows'])
        ->name('marks.rows')
        ->middleware('permission:marks.viewAny');
    Route::post('marks', [MarksEntryController::class, 'store'])
        ->name('marks.store')
        ->middleware('permission:marks.update');

    // One exam's result for a whole class, on screen and as a landscape PDF.
    Route::get('results', [ClassResultController::class, 'index'])
        ->name('results.index')
        ->middleware('permission:results.viewAny');
    Route::get('results/pdf', [ClassResultController::class, 'pdf'])
        ->name('results.pdf')
        ->middleware('permission:results.pdf');

    // Before the resource, so /reports/class-pdf is not read as a report id.
    Route::get('reports/class-pdf', [ReportController::class, 'downloadClassPdf'])
        ->name('reports.class-pdf')
        ->middleware('permission:reports.pdf');

    $resource('reports', ReportController::class);
    Route::get('reports/{report}/pdf', [ReportController::class, 'downloadPdf'])
        ->name('reports.pdf')
        ->middleware('permission:reports.pdf');

    $resource('schools', SchoolController::class);

    // Academic years and each school's classes and sections, on one screen.
    Route::prefix('structure')->name('structure.')->group(function () {
        Route::get('/', [AcademicStructureController::class, 'index'])->name('index')
            ->middleware('permission:structure.viewAny');
        Route::post('years', [AcademicStructureController::class, 'storeYear'])->name('years.store')
            ->middleware('permission:structure.create');
        Route::put('years/{year}/current', [AcademicStructureController::class, 'makeCurrent'])->name('years.current')
            ->middleware('permission:structure.update');
        Route::delete('years/{year}', [AcademicStructureController::class, 'destroyYear'])->name('years.destroy')
            ->middleware('permission:structure.delete');
        Route::post('classes', [AcademicStructureController::class, 'storeClass'])->name('classes.store')
            ->middleware('permission:structure.create');
        Route::put('classes/{class}', [AcademicStructureController::class, 'updateClass'])->name('classes.update')
            ->middleware('permission:structure.update');
        Route::delete('classes/{class}', [AcademicStructureController::class, 'destroyClass'])->name('classes.destroy')
            ->middleware('permission:structure.delete');
    });

    // Moving a class up a year, or out of the school.
    Route::get('promotion', [PromotionController::class, 'index'])->name('promotion.index')
        ->middleware('permission:promotion.run');
    Route::post('promotion', [PromotionController::class, 'store'])->name('promotion.store')
        ->middleware('permission:promotion.run');
    $resource('subjects', SubjectController::class);
    // GradeSystemController has no show(), so no /grades/{grade} route.
    Route::post('grades/load-standard', [GradeSystemController::class, 'loadStandard'])
        ->name('grades.load-standard')
        ->middleware('permission:grades.create');
    $resource('grades', GradeSystemController::class, ['show']);

    // User Management
    $resource('users', UserController::class);
    $resource('roles', RoleController::class, ['show']);
    Route::get('permissions', [PermissionController::class, 'index'])
        ->name('permissions.index')
        ->middleware('permission:permissions.viewAny');

    Route::prefix('bulk-import')->name('bulk-import.')->middleware('permission:bulk-import.run')->group(function () {
        Route::get('/', [BulkImportController::class, 'index'])->name('index');
        Route::post('/students', [BulkImportController::class, 'importStudents'])->name('students');
        Route::get('/template', [BulkImportController::class, 'downloadTemplate'])->name('template');
    });
});

// Redirect root to login if not authenticated
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

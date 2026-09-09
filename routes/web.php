<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\GradeSystemController;
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

    $resource('students', StudentController::class);

    $resource('reports', ReportController::class);
    Route::get('reports/{report}/pdf', [ReportController::class, 'downloadPdf'])
        ->name('reports.pdf')
        ->middleware('permission:reports.pdf');

    $resource('schools', SchoolController::class);
    $resource('subjects', SubjectController::class);
    $resource('grades', GradeSystemController::class);

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

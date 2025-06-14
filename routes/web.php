<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\GradeSystemController;
use App\Http\Controllers\BulkImportController;
use App\Http\Controllers\UserController;

// Authentication Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

// Protected Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Profile Routes
    Route::get('/profile', [UserController::class, 'profile'])->name('users.profile');
    Route::put('/profile', [UserController::class, 'updateProfile'])->name('users.profile.update');
    
    // Student Routes
    Route::resource('students', StudentController::class);
    
    // Report Routes
    Route::resource('reports', ReportController::class);
    Route::get('reports/{report}/pdf', [ReportController::class, 'downloadPdf'])->name('reports.pdf');
    
    // Admin Only Routes
    Route::middleware(['admin'])->group(function () {
        Route::resource('users', UserController::class);
        Route::resource('schools', SchoolController::class);
        Route::resource('subjects', SubjectController::class);
        Route::resource('grades', GradeSystemController::class);
        
        Route::prefix('bulk-import')->name('bulk-import.')->group(function () {
            Route::get('/', [BulkImportController::class, 'index'])->name('index');
            Route::post('/students', [BulkImportController::class, 'importStudents'])->name('students');
            Route::get('/template', [BulkImportController::class, 'downloadTemplate'])->name('template');
        });
    });
});

// Redirect root to login if not authenticated
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

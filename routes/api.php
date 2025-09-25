<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CvController;
use App\Http\Controllers\Api\JobDescriptionController;
use App\Http\Controllers\Api\AnalysisController;
use App\Http\Controllers\Api\ComparisonController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\ReportController;

// Authentication routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/profile', [AuthController::class, 'profile']);

    // CV Management
    Route::apiResource('cvs', CvController::class);
    Route::get('/cvs/{cv}/download', [CvController::class, 'download']);

    // CV Analysis
    Route::post('/analysis/analyze', [AnalysisController::class, 'analyze']);
    Route::get('/analysis/history', [AnalysisController::class, 'history']);
    Route::get('/analysis/{id}', [AnalysisController::class, 'show']);
    Route::post('/analysis/{id}/course-recommendations', [AnalysisController::class, 'generateCourseRecommendations']);

    // CV Comparison
    Route::post('/comparison/compare', [ComparisonController::class, 'compare']);
    Route::get('/comparison/history', [ComparisonController::class, 'history']);
    Route::get('/comparison/{id}', [ComparisonController::class, 'show']);

    // Reports & Export
    Route::post('/reports/export-analysis/{id}', [ReportController::class, 'exportAnalysis']);
    Route::post('/reports/export-comparison/{id}', [ReportController::class, 'exportComparison']);
    Route::get('/reports/cv-templates', [ReportController::class, 'getCvTemplates']);
    Route::get('/reports/cv-templates/{template}/download', [ReportController::class, 'downloadTemplate']);

    // Job Descriptions (All users can view)
    Route::get('/jobs', [JobDescriptionController::class, 'index']);
    Route::get('/jobs/{jobDescription}', [JobDescriptionController::class, 'show']);

    // Admin routes
    Route::middleware('admin')->prefix('admin')->group(function () {
        // Job Description Management
        Route::post('/jobs', [JobDescriptionController::class, 'store']);
        Route::put('/jobs/{jobDescription}', [JobDescriptionController::class, 'update']);
        Route::delete('/jobs/{jobDescription}', [JobDescriptionController::class, 'destroy']);

        // Analytics & Monitoring
        Route::get('/analytics/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/analytics/cv-statistics', [AdminController::class, 'cvStatistics']);
        Route::get('/analytics/user-activity', [AdminController::class, 'userActivity']);
        Route::get('/analytics/score-trends', [AdminController::class, 'scoreTrends']);

        // User Management
        Route::get('/users', [AdminController::class, 'users']);
        Route::put('/users/{user}/role', [AdminController::class, 'updateUserRole']);
        Route::delete('/users/{user}', [AdminController::class, 'deleteUser']);
    });
});
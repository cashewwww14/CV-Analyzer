<?php
use Illuminate\Support\Facades\Route;

// Redirect root ke frontend
Route::get('/', function () {
    return redirect('/frontend/login.html');
});

// Serve frontend files
Route::get('/frontend/{path}', function ($path) {
    $filePath = public_path("frontend/{$path}");
    if (file_exists($filePath)) {
        return response()->file($filePath);
    }
    abort(404);
})->where('path', '.*');

// API Routes handled by api.php
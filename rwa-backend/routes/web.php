<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\PaymentManagementController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\WebAuthController;
use App\Http\Controllers\Web\ResidentManagementController;

// Authentication Routes (Public)
Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [WebAuthController::class, 'login'])->name('login.post');
Route::get('/register', [WebAuthController::class, 'showRegister'])->name('register');
Route::post('/register', [WebAuthController::class, 'register'])->name('register.post');
Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');

// Test route for Google Sheets debugging
Route::get('/test-google-connection', [App\Http\Controllers\TestController::class, 'testGoogleConnection']);

// Root route - redirect to login if not authenticated, dashboard if authenticated
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard.index');
    }
    return redirect()->route('login');
});

// Redirect old static dashboard to new Laravel dashboard
Route::get('/dashboard.html', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard.index');
    }
    return redirect()->route('login');
});

// Protected Routes (Require Authentication)
Route::middleware('auth')->group(function () {
    // Dashboard Routes
    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('index');
        Route::get('/stats', [DashboardController::class, 'getStats'])->name('stats');
    });
    
    // Payment Management Web Routes
    Route::prefix('payment-management')->name('payment-management.')->group(function () {
        Route::get('/', [PaymentManagementController::class, 'index'])->name('index');

        Route::get('/payment/{payment}', [PaymentManagementController::class, 'show'])->name('show');
    Route::get('/{payment}/edit', [PaymentManagementController::class, 'edit'])->name('edit');
    Route::patch('/{payment}', [PaymentManagementController::class, 'update'])->name('update');
    Route::get('/{payment}/carryforward-breakdown', [PaymentManagementController::class, 'getCarryForwardBreakdown'])->name('carryforward-breakdown');
        Route::get('/unpaid-residents', [PaymentManagementController::class, 'unpaidResidents'])->name('unpaid');
        Route::get('/defaulters', [PaymentManagementController::class, 'defaulters'])->name('defaulters');
        Route::get('/analytics', [PaymentManagementController::class, 'analytics'])->name('analytics');
        Route::get('/export', [PaymentManagementController::class, 'export'])->name('export');
    });
    
    // Resident Management Web Routes
    Route::prefix('residents')->name('web.residents.')->group(function () {
        Route::get('/', [ResidentManagementController::class, 'index'])->name('index');
        Route::get('/create', [ResidentManagementController::class, 'create'])->name('create');
        Route::post('/', [ResidentManagementController::class, 'store'])->name('store');
        Route::get('/{resident}', [ResidentManagementController::class, 'show'])->name('show');
        Route::get('/{resident}/edit', [ResidentManagementController::class, 'edit'])->name('edit');
        Route::put('/{resident}', [ResidentManagementController::class, 'update'])->name('update');
        Route::delete('/{resident}', [ResidentManagementController::class, 'destroy'])->name('destroy');
        Route::get('/export/csv', [ResidentManagementController::class, 'export'])->name('export');
    });
});

// Debug route for Google Sheets path (can be removed in production)
Route::get('/debug-google-path', function () {
    $path = config('google.service_account_json_path');
    $exists = file_exists($path);
    $baseDir = base_path();
    $expectedPath = base_path('rosewood-estate-2-2bb6a4c480b2.json');
    $expectedExists = file_exists($expectedPath);
    
    return response()->json([
        'config_path' => $path,
        'config_path_exists' => $exists,
        'base_directory' => $baseDir,
        'expected_path' => $expectedPath,
        'expected_path_exists' => $expectedExists,
        'env_path' => env('GOOGLE_SERVICE_ACCOUNT_JSON_PATH'),
        'files_in_base' => array_slice(scandir($baseDir), 0, 20), // First 20 files
    ]);
});

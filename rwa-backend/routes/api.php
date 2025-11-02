<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ResidentController;
use App\Http\Controllers\PaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Authentication Routes (Public)
Route::prefix('v1/auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    
    // Protected auth routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('user', [AuthController::class, 'user']);
        Route::post('refresh', [AuthController::class, 'refreshToken']);
    });
});

// API Routes for RWA Management System (Protected)
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    
    // Residents routes
    Route::apiResource('residents', ResidentController::class);
    Route::get('residents/{resident}/payments', [ResidentController::class, 'getPayments']);
    Route::get('residents/filter/payers', [ResidentController::class, 'getPayers']);
    Route::get('residents/filter/non-payers', [ResidentController::class, 'getNonPayers']);
    
    // Payments routes
    Route::apiResource('payments', PaymentController::class);
    Route::get('payments/filter/by-status/{status}', [PaymentController::class, 'getByStatus']);
    Route::get('payments/filter/by-month/{month}', [PaymentController::class, 'getByMonth']);
    Route::get('payments/filter/overdue', [PaymentController::class, 'getOverdue']);
    
    // Dashboard stats
    Route::get('dashboard/stats', [ResidentController::class, 'getDashboardStats']);
    
    // Google Sheets sync
    Route::post('sync/google-sheets', [ResidentController::class, 'syncFromGoogleSheets']);
});

// Public routes for testing (remove in production)
Route::prefix('v1/public')->group(function () {
    Route::get('dashboard/stats', [ResidentController::class, 'getDashboardStats']);
    Route::get('residents', [ResidentController::class, 'index']);
});
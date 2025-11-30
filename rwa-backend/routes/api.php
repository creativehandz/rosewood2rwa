<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ResidentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\GoogleSheetsController;
use App\Http\Controllers\Web\DashboardController;
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
    
    // Residents filter routes (must be before apiResource)
    Route::get('residents/filter/payers', [ResidentController::class, 'getPayers']);
    Route::get('residents/filter/non-payers', [ResidentController::class, 'getNonPayers']);
    Route::get('residents/{resident}/payments', [ResidentController::class, 'getPayments']);
    
    // Residents CRUD routes
    Route::apiResource('residents', ResidentController::class);
    
    // Payments filter routes (must be before apiResource)
    Route::get('payments/filter/by-status/{status}', [PaymentController::class, 'getByStatus']);
    Route::get('payments/filter/by-month/{month}', [PaymentController::class, 'getByMonth']);
    Route::get('payments/filter/overdue', [PaymentController::class, 'getOverdue']);
    Route::get('payments/filter/defaulters', [PaymentController::class, 'getDefaultersList']);
    Route::get('payments/summary', [PaymentController::class, 'getSummary']);
    Route::get('payments/analytics', [PaymentController::class, 'getAnalytics']);
    Route::post('payments/search', [PaymentController::class, 'search']);
    Route::get('payments/resident/{resident}/month/{month}', [PaymentController::class, 'getResidentPayment']);
    Route::post('payments/bulk-sync', [PaymentController::class, 'bulkSync']);
    Route::post('payments/bulk-create-monthly', [PaymentController::class, 'bulkCreateMonthlyPayments']);
    Route::post('payments/{payment}/partial-payment', [PaymentController::class, 'processPartialPayment']);
    Route::post('payments/update-overdue', [PaymentController::class, 'updateOverduePayments']);
    Route::post('payments/generate-report', [PaymentController::class, 'generateReport']);
    
    // Payment Google Sheets sync routes
    Route::prefix('payments/google-sheets')->group(function () {
        Route::post('sync-to-sheets', [PaymentController::class, 'syncToSheets']);
        Route::post('sync-from-sheets', [PaymentController::class, 'syncFromSheets']);
        Route::post('bidirectional-sync', [PaymentController::class, 'bidirectionalSync']);
        Route::get('sync-status', [PaymentController::class, 'getSyncStatus']);
        Route::get('test-connection', [PaymentController::class, 'testSheetsConnection']);
    });
    
    // Payments CRUD routes
    Route::apiResource('payments', PaymentController::class);
    
    // Dashboard stats
    Route::get('dashboard/stats', [ResidentController::class, 'getDashboardStats']);
    
    // Web dashboard stats
    Route::get('dashboard/web-stats', [DashboardController::class, 'getStats']);
    
    // Google Sheets routes
    Route::prefix('google-sheets')->group(function () {
        Route::post('test-connection', [GoogleSheetsController::class, 'testConnection']);
        Route::post('push-to-sheet', [GoogleSheetsController::class, 'pushResidents']);
        Route::post('sync-from-sheet', [GoogleSheetsController::class, 'syncFromSheets']);
        Route::get('preview-sync', [GoogleSheetsController::class, 'previewSync']);
        Route::post('create-sheet', [GoogleSheetsController::class, 'createSheet']);
    });
    
    // Google Sheets sync
    Route::post('sync/google-sheets', [ResidentController::class, 'syncFromGoogleSheets']);
});

// Public routes for testing (remove in production)
Route::prefix('v1/public')->group(function () {
    Route::get('dashboard/stats', [ResidentController::class, 'getDashboardStats']);
    Route::get('residents', [ResidentController::class, 'index']);
    Route::get('residents/{resident}', [ResidentController::class, 'show']);
    Route::get('residents/filter/payers', [ResidentController::class, 'getPayers']);
    Route::get('residents/filter/non-payers', [ResidentController::class, 'getNonPayers']);
    Route::get('residents/{resident}/payments', [ResidentController::class, 'getPayments']);
    
    // Payment public test routes
    Route::get('payments', [PaymentController::class, 'index']);
    Route::get('payments/summary', [PaymentController::class, 'getSummary']);
    Route::get('payments/analytics', [PaymentController::class, 'getAnalytics']);
    Route::get('payments/filter/overdue', [PaymentController::class, 'getOverdue']);
    Route::get('payments/filter/defaulters', [PaymentController::class, 'getDefaultersList']);
    Route::get('payments/filter/by-status/{status}', [PaymentController::class, 'getByStatus']);
    Route::get('payments/filter/by-month/{month}', [PaymentController::class, 'getByMonth']);
    Route::post('payments/search', [PaymentController::class, 'search']);
    Route::post('payments', [PaymentController::class, 'store']);
    Route::put('payments/{payment}', [PaymentController::class, 'update']);
    Route::delete('payments/{payment}', [PaymentController::class, 'destroy']);
    
    // Google Sheets public test routes
    Route::prefix('google-sheets')->group(function () {
        Route::post('test-connection', [GoogleSheetsController::class, 'testConnection']);
        Route::post('push-to-sheet', [GoogleSheetsController::class, 'pushResidents']);
        Route::post('sync-from-sheet', [GoogleSheetsController::class, 'syncFromSheets']);
        Route::get('preview-sync', [GoogleSheetsController::class, 'previewSync']);
        Route::post('create-sheet', [GoogleSheetsController::class, 'createSheet']);
    });
});
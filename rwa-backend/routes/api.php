<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ResidentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\GoogleSheetsController;
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
    
    // Payments CRUD routes
    Route::apiResource('payments', PaymentController::class);
    
    // Dashboard stats
    Route::get('dashboard/stats', [ResidentController::class, 'getDashboardStats']);
    
    // Google Sheets routes
    Route::prefix('google-sheets')->group(function () {
        Route::get('test-connection', [GoogleSheetsController::class, 'testConnection']);
        Route::post('push-residents', [GoogleSheetsController::class, 'pushResidents']);
        Route::post('sync-from-sheets', [GoogleSheetsController::class, 'syncFromSheets']);
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
    
    // Google Sheets public test routes
    Route::prefix('google-sheets')->group(function () {
        Route::get('test-connection', [GoogleSheetsController::class, 'testConnection']);
        Route::post('push-residents', [GoogleSheetsController::class, 'pushResidents']);
        Route::post('sync-from-sheets', [GoogleSheetsController::class, 'syncFromSheets']);
        Route::get('preview-sync', [GoogleSheetsController::class, 'previewSync']);
        Route::post('create-sheet', [GoogleSheetsController::class, 'createSheet']);
    });
});
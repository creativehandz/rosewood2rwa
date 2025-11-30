<?php

namespace App\Http\Controllers;

use App\Services\GoogleSheetsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Support\Facades\Log;

class TestController extends Controller
{
    public function testGoogleConnection()
    {
        try {
            Log::info('Testing Google Sheets connection...');
            
            // First test: Check if config values are loaded
            $configTest = [
                'service_account_path' => config('google.service_account_json_path'),
                'spreadsheet_id' => config('google.sheets.spreadsheet_id'),
                'application_name' => config('google.sheets.application_name'),
                'scopes' => config('google.scopes'),
            ];
            
            Log::info('Config test:', $configTest);
            
            // Check if file exists
            $serviceAccountPath = config('google.service_account_json_path');
            $fileExists = file_exists($serviceAccountPath);
            
            Log::info('File existence check:', [
                'path' => $serviceAccountPath,
                'exists' => $fileExists,
                'is_readable' => $fileExists ? is_readable($serviceAccountPath) : false
            ]);
            
            if (!$fileExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google service account JSON file not found',
                    'debug' => [
                        'expected_path' => $serviceAccountPath,
                        'file_exists' => false
                    ]
                ]);
            }
            
            // Try to read and validate JSON file
            $jsonContent = file_get_contents($serviceAccountPath);
            $credentials = json_decode($jsonContent, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid JSON in service account file',
                    'debug' => [
                        'json_error' => json_last_error_msg(),
                        'file_size' => strlen($jsonContent)
                    ]
                ]);
            }
            
            Log::info('JSON validation passed, credentials loaded');
            
            // Test Google Sheets Service initialization
            $googleSheetsService = new GoogleSheetsService();
            Log::info('GoogleSheetsService instantiated successfully');
            
            // Test connection
            $result = $googleSheetsService->testConnection();
            Log::info('Connection test result:', $result);
            
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'debug' => [
                    'config_loaded' => true,
                    'file_exists' => true,
                    'json_valid' => true,
                    'service_email' => $credentials['client_email'] ?? 'unknown',
                    'full_result' => $result
                ]
            ]);
            
        } catch (Exception $e) {
            Log::error('Google connection test failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
                'debug' => [
                    'error_class' => get_class($e),
                    'error_line' => $e->getLine(),
                    'error_file' => $e->getFile()
                ]
            ]);
        }
    }
}
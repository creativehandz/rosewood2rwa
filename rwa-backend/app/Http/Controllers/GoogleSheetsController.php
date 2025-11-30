<?php

namespace App\Http\Controllers;

use App\Services\GoogleSheetsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class GoogleSheetsController extends Controller
{
    private $googleSheetsService;

    public function __construct(GoogleSheetsService $googleSheetsService)
    {
        $this->googleSheetsService = $googleSheetsService;
    }

    /**
     * Test Google Sheets connection
     */
    public function testConnection(): JsonResponse
    {
        try {
            $result = $this->googleSheetsService->testConnection();
            
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result
            ], $result['success'] ? 200 : 400);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to test connection: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new Google Sheet for residents
     */
    public function createSheet(Request $request): JsonResponse
    {
        try {
            $title = $request->input('title', 'RWA Residents - ' . now()->format('Y-m-d H:i:s'));
            
            $result = $this->googleSheetsService->createResidentsSheet($title);
            
            return response()->json([
                'status' => 'success',
                'message' => $result['message'],
                'data' => $result
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create sheet: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Push all residents to Google Sheets
     */
    public function pushResidents(): JsonResponse
    {
        try {
            // Increase execution time for this operation
            set_time_limit(300); // 5 minutes
            
            $result = $this->googleSheetsService->pushAllResidents();
            
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result
            ]);

        } catch (Exception $e) {
            \Log::error('Failed to push residents to Google Sheets', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to push residents: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Google Sheets configuration status
     */
    public function getConfigStatus(): JsonResponse
    {
        try {
            $serviceAccountPath = config('google.service_account_json_path');
            $spreadsheetId = config('google.sheets.spreadsheet_id');
            
            $status = [
                'service_account_configured' => !empty($serviceAccountPath) && file_exists($serviceAccountPath),
                'service_account_path' => $serviceAccountPath,
                'spreadsheet_id_configured' => !empty($spreadsheetId),
                'spreadsheet_id' => $spreadsheetId,
                'spreadsheet_url' => !empty($spreadsheetId) ? "https://docs.google.com/spreadsheets/d/{$spreadsheetId}/edit" : null
            ];
            
            return response()->json([
                'status' => 'success',
                'data' => $status
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get config status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync residents from Google Sheets to database
     */
    public function syncFromSheets(): JsonResponse
    {
        try {
            $result = $this->googleSheetsService->syncResidentsFromSheet();
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => [
                        'synced_count' => $result['synced_count'],
                        'skipped_count' => $result['skipped_count'],
                        'errors' => $result['errors']
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'data' => [
                        'synced_count' => $result['synced_count'],
                        'skipped_count' => $result['skipped_count'],
                        'errors' => $result['errors']
                    ]
                ], 400);
            }

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync from Google Sheets: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview what would be synced from Google Sheets (for debugging)
     */
    public function previewSync(): JsonResponse
    {
        try {
            $result = $this->googleSheetsService->previewSyncFromSheet();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Preview data from Google Sheets',
                'data' => $result
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to preview sync data: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
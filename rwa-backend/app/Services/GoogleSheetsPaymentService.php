<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Resident;
use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Exception;

class GoogleSheetsPaymentService
{
    protected $client;
    protected $service;
    protected $spreadsheetId;
    protected $sheetName;
    protected $config;

    public function __construct()
    {
        $this->config = config('googlesheets');
        $this->spreadsheetId = $this->config['spreadsheet_id'] ?? null;
        $this->sheetName = $this->config['payment_sheet_name'] ?? 'Payments';
        
        // Initialize Google client only if credentials are available
        $this->initializeGoogleClient();
    }

    protected function initializeGoogleClient()
    {
        try {
            // Check if credentials file exists
            $credentialsPath = $this->config['credentials_path'] ?? null;
            if (!$credentialsPath || !file_exists($credentialsPath)) {
                Log::warning('Google Sheets credentials not found. Google Sheets functionality will be disabled.');
                $this->client = null;
                $this->service = null;
                return;
            }

            // Check if spreadsheet ID is configured
            if (empty($this->spreadsheetId)) {
                Log::warning('Google Sheets spreadsheet ID not configured. Google Sheets functionality will be disabled.');
                $this->client = null;
                $this->service = null;
                return;
            }

            $this->client = new Client();
            $this->client->setApplicationName('RWA Payment System');
            $this->client->setScopes([Sheets::SPREADSHEETS]);
            $this->client->setAuthConfig($credentialsPath);
            $this->client->setAccessType('offline');
            
            $this->service = new Sheets($this->client);
            Log::info('Google Sheets service initialized successfully');
        } catch (Exception $e) {
            Log::warning('Failed to initialize Google Sheets client: ' . $e->getMessage() . '. Google Sheets functionality will be disabled.');
            $this->client = null;
            $this->service = null;
        }
    }

    /**
     * Check if Google Sheets service is available
     */
    public function isAvailable(): bool
    {
        return $this->service !== null && $this->client !== null;
    }

    /**
     * Sync all payments from database to Google Sheets
     */
    public function syncDatabaseToSheets()
    {
        if (!$this->isAvailable()) {
            throw new Exception('Google Sheets service is not available. Please check your configuration.');
        }

        try {
            Log::info('Starting database to Google Sheets sync');
            
            $payments = Payment::with('resident')
                ->orderBy('payment_month', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            if ($payments->isEmpty()) {
                Log::info('No payments to sync');
                return ['success' => true, 'message' => 'No payments to sync', 'synced_count' => 0];
            }

            // Clear existing data (except headers)
            $this->clearSheetData();

            // Prepare data for batch update
            $sheetData = [];
            $rowNumber = $this->config['first_data_row'];

            foreach ($payments as $payment) {
                $sheetData[] = $this->convertPaymentToSheetRow($payment, $rowNumber);
                $rowNumber++;
            }

            // Write data to sheet in batch
            $this->batchUpdateSheet($sheetData);

            // Update sync timestamps
            Payment::whereIn('id', $payments->pluck('id'))
                ->update(['last_synced_at' => now()]);

            Log::info('Database to Google Sheets sync completed', ['synced_count' => count($sheetData)]);
            
            return [
                'success' => true, 
                'message' => 'Successfully synced to Google Sheets', 
                'synced_count' => count($sheetData)
            ];

        } catch (Exception $e) {
            Log::error('Database to Google Sheets sync failed: ' . $e->getMessage());
            throw new Exception('Sync to Google Sheets failed: ' . $e->getMessage());
        }
    }

    /**
     * Sync changes from Google Sheets to database
     */
    public function syncSheetsToDatabase()
    {
        if (!$this->isAvailable()) {
            throw new Exception('Google Sheets service is not available. Please check your configuration.');
        }

        try {
            Log::info('Starting Google Sheets to database sync');
            
            $sheetData = $this->readSheetData();
            
            if (empty($sheetData)) {
                return ['success' => true, 'message' => 'No data in sheet', 'processed_count' => 0];
            }

            $processedCount = 0;
            $errors = [];

            foreach ($sheetData as $rowIndex => $row) {
                try {
                    $rowNumber = $rowIndex + $this->config['first_data_row'];
                    $result = $this->processSheetRow($row, $rowNumber);
                    
                    if ($result['processed']) {
                        $processedCount++;
                    }
                } catch (Exception $e) {
                    $errors[] = "Row {$rowNumber}: " . $e->getMessage();
                    Log::warning("Error processing sheet row {$rowNumber}: " . $e->getMessage());
                }
            }

            Log::info('Google Sheets to database sync completed', [
                'processed_count' => $processedCount,
                'errors_count' => count($errors)
            ]);

            return [
                'success' => true,
                'message' => 'Sync from Google Sheets completed',
                'processed_count' => $processedCount,
                'errors' => $errors
            ];

        } catch (Exception $e) {
            Log::error('Google Sheets to database sync failed: ' . $e->getMessage());
            throw new Exception('Sync from Google Sheets failed: ' . $e->getMessage());
        }
    }

    /**
     * Bidirectional sync: Sheets -> Database -> Sheets
     */
    public function bidirectionalSync()
    {
        if (!$this->isAvailable()) {
            throw new Exception('Google Sheets service is not available. Please check your configuration.');
        }

        try {
            Log::info('Starting bidirectional sync');
            
            // First, sync from sheets to database to capture any manual changes
            $sheetsResult = $this->syncSheetsToDatabase();
            
            // Then sync from database back to sheets to ensure consistency
            $databaseResult = $this->syncDatabaseToSheets();
            
            return [
                'success' => true,
                'message' => 'Bidirectional sync completed',
                'sheets_to_db' => $sheetsResult,
                'db_to_sheets' => $databaseResult
            ];

        } catch (Exception $e) {
            Log::error('Bidirectional sync failed: ' . $e->getMessage());
            throw new Exception('Bidirectional sync failed: ' . $e->getMessage());
        }
    }

    /**
     * Convert Payment model to Google Sheets row data
     */
    protected function convertPaymentToSheetRow(Payment $payment, $rowNumber)
    {
        $columns = $this->config['columns'];
        
        return [
            $columns['payment_date'] => $payment->payment_date ? $payment->payment_date->format('Y-m-d') : '',
            $columns['house_number'] => $payment->resident->house_number ?? '',
            $columns['floor'] => $payment->resident->floor ?? '',
            // resident_name and phone are auto-populated by formulas in the sheet
            $columns['payment_month'] => $payment->payment_month,
            $columns['amount_due'] => $payment->amount_due,
            $columns['amount_paid'] => $payment->amount_paid,
            $columns['payment_method'] => $payment->payment_method,
            $columns['status'] => $payment->status,
            $columns['payment_description'] => $payment->payment_description ?? '',
            $columns['late_fee'] => $payment->late_fee ?? 0,
            $columns['notes'] => $payment->notes ?? ''
        ];
    }

    /**
     * Process a single row from Google Sheets
     */
    protected function processSheetRow($row, $rowNumber)
    {
        $columns = $this->config['columns'];
        
        // Extract data from row
        $houseNumber = $this->getColumnValue($row, $columns['house_number']);
        $floor = $this->getColumnValue($row, $columns['floor']);
        $paymentMonth = $this->getColumnValue($row, $columns['payment_month']);
        
        if (empty($houseNumber) || empty($paymentMonth)) {
            return ['processed' => false, 'message' => 'Missing required fields'];
        }

        // Find resident
        $resident = Resident::where('house_number', $houseNumber)
            ->where('floor', $floor)
            ->first();
            
        if (!$resident) {
            throw new Exception("Resident not found for house {$houseNumber}, floor {$floor}");
        }

        // Prepare payment data
        $paymentData = [
            'resident_id' => $resident->id,
            'payment_month' => $paymentMonth,
            'amount_due' => $this->getColumnValue($row, $columns['amount_due']) ?: 0,
            'amount_paid' => $this->getColumnValue($row, $columns['amount_paid']) ?: 0,
            'payment_method' => $this->getColumnValue($row, $columns['payment_method']) ?: 'Cash',
            'status' => $this->getColumnValue($row, $columns['status']) ?: 'Pending',
            'payment_description' => $this->getColumnValue($row, $columns['payment_description']),
            'late_fee' => $this->getColumnValue($row, $columns['late_fee']) ?: 0,
            'notes' => $this->getColumnValue($row, $columns['notes']),
            'sheet_row_id' => $rowNumber,
            'last_synced_at' => now()
        ];

        // Handle payment date
        $paymentDateValue = $this->getColumnValue($row, $columns['payment_date']);
        if ($paymentDateValue) {
            try {
                $paymentData['payment_date'] = Carbon::parse($paymentDateValue);
            } catch (Exception $e) {
                // If date parsing fails, leave it null
                $paymentData['payment_date'] = null;
            }
        }

        // Create or update payment
        $payment = Payment::updateOrCreate(
            [
                'resident_id' => $resident->id,
                'payment_month' => $paymentMonth
            ],
            $paymentData
        );

        return ['processed' => true, 'payment_id' => $payment->id];
    }

    /**
     * Clear sheet data (keeping headers)
     */
    protected function clearSheetData()
    {
        $range = $this->sheetName . '!' . $this->config['first_data_row'] . ':' . $this->config['first_data_row'] + 1000;
        
        $body = new ValueRange([
            'values' => [[]]
        ]);
        
        $this->service->spreadsheets_values->clear(
            $this->spreadsheetId,
            $range,
            new \Google\Service\Sheets\ClearValuesRequest()
        );
    }

    /**
     * Batch update sheet with payment data
     */
    protected function batchUpdateSheet($sheetData)
    {
        if (empty($sheetData)) {
            return;
        }

        $values = [];
        foreach ($sheetData as $rowData) {
            $row = [];
            foreach ($this->config['columns'] as $column) {
                $row[] = $rowData[$column] ?? '';
            }
            $values[] = $row;
        }

        $range = $this->sheetName . '!' . $this->config['first_data_row'] . ':' . $this->config['first_data_row'] + count($values);
        
        $body = new ValueRange([
            'values' => $values
        ]);
        
        $params = [
            'valueInputOption' => 'RAW'
        ];
        
        $this->service->spreadsheets_values->update(
            $this->spreadsheetId,
            $range,
            $body,
            $params
        );
    }

    /**
     * Read data from Google Sheets
     */
    protected function readSheetData()
    {
        $range = $this->sheetName . '!' . $this->config['payment_range'];
        
        $response = $this->service->spreadsheets_values->get(
            $this->spreadsheetId,
            $range
        );
        
        $values = $response->getValues();
        
        if (empty($values)) {
            return [];
        }
        
        // Remove header row
        array_shift($values);
        
        return $values;
    }

    /**
     * Get column value by column letter
     */
    protected function getColumnValue($row, $columnLetter)
    {
        $columnIndex = ord(strtoupper($columnLetter)) - ord('A');
        return isset($row[$columnIndex]) ? trim($row[$columnIndex]) : '';
    }

    /**
     * Get sync status and statistics
     */
    public function getSyncStatus()
    {
        if (!$this->isAvailable()) {
            return [
                'success' => false,
                'message' => 'Google Sheets service is not available',
                'total_payments' => Payment::count(),
                'synced_payments' => 0,
                'last_sync_at' => null,
                'sync_percentage' => 0
            ];
        }

        $totalPayments = Payment::count();
        $syncedPayments = Payment::whereNotNull('last_synced_at')->count();
        $lastSync = Payment::max('last_synced_at');
        
        return [
            'total_payments' => $totalPayments,
            'synced_payments' => $syncedPayments,
            'unsynced_payments' => $totalPayments - $syncedPayments,
            'last_sync_at' => $lastSync,
            'sync_percentage' => $totalPayments > 0 ? round(($syncedPayments / $totalPayments) * 100, 2) : 0
        ];
    }

    /**
     * Test Google Sheets connection
     */
    public function testConnection()
    {
        if (!$this->isAvailable()) {
            return [
                'success' => false,
                'message' => 'Google Sheets service is not available. Please check your configuration.'
            ];
        }

        try {
            $response = $this->service->spreadsheets->get($this->spreadsheetId);
            return [
                'success' => true,
                'message' => 'Connection successful',
                'spreadsheet_title' => $response->getProperties()->getTitle()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage()
            ];
        }
    }
}
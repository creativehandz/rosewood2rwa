<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Google Sheets Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Google Sheets API integration
    |
    */

    'credentials_path' => env('GOOGLE_SHEETS_CREDENTIALS_PATH', storage_path('app/google-credentials.json')),
    'spreadsheet_id' => env('GOOGLE_SHEETS_SPREADSHEET_ID'),
    'payment_sheet_name' => env('GOOGLE_SHEETS_PAYMENT_SHEET_NAME', 'Monthly Maintenance'),
    'payment_range' => env('GOOGLE_SHEETS_PAYMENT_RANGE', 'A:M'), // A to M columns
    
    // Column mappings for Google Sheets
    'columns' => [
        'payment_date' => 'A',
        'house_number' => 'B',
        'floor' => 'C',
        'resident_name' => 'D',  // Auto-populated via formula
        'phone' => 'E',          // Auto-populated via formula
        'payment_month' => 'F',
        'amount_due' => 'G',
        'amount_paid' => 'H',
        'payment_method' => 'I',
        'status' => 'J',
        'payment_description' => 'K',
        'late_fee' => 'L',
        'notes' => 'M'
    ],
    
    // Header row number (1-based)
    'header_row' => 1,
    
    // First data row number (1-based)
    'first_data_row' => 2,
    
    // Sync settings
    'sync' => [
        'batch_size' => 100,
        'auto_sync_enabled' => env('GOOGLE_SHEETS_AUTO_SYNC', true),
        'sync_interval_minutes' => env('GOOGLE_SHEETS_SYNC_INTERVAL', 15),
    ]
];
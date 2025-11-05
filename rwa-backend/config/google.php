<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Google API Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your Google API settings. You will need to
    | provide the path to your service account JSON file and other settings.
    |
    */

    'service_account_json_path' => env('GOOGLE_SERVICE_ACCOUNT_JSON_PATH', storage_path('app/google-service-account.json')),
    
    'sheets' => [
        'application_name' => env('GOOGLE_SHEETS_APPLICATION_NAME', 'RWA Management System'),
        'spreadsheet_id' => env('GOOGLE_SHEETS_SPREADSHEET_ID', ''),
        'range' => env('GOOGLE_SHEETS_RANGE', 'Sheet1'),
    ],

    'drive' => [
        'folder_id' => env('GOOGLE_DRIVE_FOLDER_ID', ''),
    ],

    'scopes' => [
        'https://www.googleapis.com/auth/spreadsheets',
        'https://www.googleapis.com/auth/drive.file',
    ],
];
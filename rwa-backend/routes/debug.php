<?php

use Illuminate\Support\Facades\Route;

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
        'all_files_in_base' => scandir($baseDir),
    ]);
});
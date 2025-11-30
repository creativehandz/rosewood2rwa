<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Payment;

echo "Payment status analysis:\n";
echo "=======================\n";

$month = '2025-11';

// Check what status values exist
$statuses = Payment::where('payment_month', $month)
    ->select('status')
    ->distinct()
    ->pluck('status')
    ->toArray();

echo "Distinct status values in database:\n";
foreach($statuses as $status) {
    $count = Payment::where('payment_month', $month)->where('status', $status)->count();
    echo "- '{$status}': {$count} records\n";
}

echo "\nTotal payments for {$month}: " . Payment::where('payment_month', $month)->count() . "\n";
<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Payment;

echo "Payment data for November 2025:\n";
echo "==============================\n";

$month = '2025-11';
$totalPayments = Payment::where('payment_month', $month)->count();
echo "Total payments for {$month}: {$totalPayments}\n";

// Check pagination settings
$perPageDefault = 25; // From controller
echo "Default per page: {$perPageDefault}\n";
echo "Pages needed: " . ceil($totalPayments / $perPageDefault) . "\n";

// Check if there are actual payment records
$firstFew = Payment::where('payment_month', $month)
    ->with('resident')
    ->limit(3)
    ->get();

echo "\nFirst 3 payments:\n";
foreach($firstFew as $payment) {
    echo "- Payment ID: {$payment->id}, Resident: {$payment->resident->owner_name}, Amount: {$payment->amount_due}\n";
}
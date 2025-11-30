<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Payment;
use Carbon\Carbon;

echo "Creating sample overdue payments...\n";

$month = '2025-11';

// Get some pending payments and make them overdue
$pendingPayments = Payment::where('payment_month', $month)
    ->where('status', 'Pending')
    ->limit(20)
    ->get();

$updatedCount = 0;
foreach($pendingPayments as $payment) {
    // Make payment overdue if it should have been paid (simulate late payment)
    $payment->update([
        'status' => 'Overdue',
        'remarks' => 'Payment overdue - past due date'
    ]);
    $updatedCount++;
}

echo "Updated {$updatedCount} payments to Overdue status\n";

// Check new status distribution
echo "\nNew status distribution for {$month}:\n";
$statuses = ['Paid', 'Pending', 'Overdue', 'Partial'];
foreach($statuses as $status) {
    $count = Payment::where('payment_month', $month)->where('status', $status)->count();
    echo "{$status}: {$count} payments\n";
}
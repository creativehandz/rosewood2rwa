<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Payment;

echo "Updating payment methods to UPI and Cash only...\n";
echo "===============================================\n";

// Convert all 'Bank Transfer' to 'UPI' (since people now scan QR codes)
$bankTransferCount = Payment::where('payment_method', 'Bank Transfer')->count();
echo "Converting {$bankTransferCount} 'Bank Transfer' payments to 'UPI'...\n";

Payment::where('payment_method', 'Bank Transfer')
    ->update(['payment_method' => 'UPI']);

echo "Successfully converted Bank Transfer payments to UPI.\n";

// Check updated distribution
echo "\nUpdated payment methods:\n";
$methods = Payment::whereNotNull('payment_method')
    ->select('payment_method')
    ->distinct()
    ->pluck('payment_method')
    ->toArray();

foreach($methods as $method) {
    $count = Payment::where('payment_method', $method)->count();
    echo "- '{$method}': {$count} payments\n";
}

$nullMethods = Payment::whereNull('payment_method')->count();
echo "- NULL (no method): {$nullMethods} payments\n";

// Calculate percentages for paid payments
$totalPaidPayments = Payment::whereNotNull('payment_method')->count();
$upiCount = Payment::where('payment_method', 'UPI')->count();
$cashCount = Payment::where('payment_method', 'Cash')->count();

if ($totalPaidPayments > 0) {
    $upiPercentage = round(($upiCount / $totalPaidPayments) * 100, 1);
    $cashPercentage = round(($cashCount / $totalPaidPayments) * 100, 1);
    
    echo "\nPayment method distribution:\n";
    echo "- UPI (QR Scanner): {$upiPercentage}%\n";
    echo "- Cash: {$cashPercentage}%\n";
}
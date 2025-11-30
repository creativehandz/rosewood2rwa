<?php
require_once 'vendor/autoload.php';
require_once 'bootstrap/app.php';

use App\Models\Payment;

echo "Payments by month:\n";
echo "================\n";

$months = ['2025-06', '2025-07', '2025-08', '2025-09', '2025-10', '2025-11'];

foreach($months as $month) {
    $count = Payment::where('payment_month', $month)->count();
    echo $month . ': ' . $count . " payments\n";
}

echo "\nTotal payments in database: " . Payment::count() . "\n";

// Check if there are payments with different month formats
echo "\nAll unique payment months in database:\n";
$uniqueMonths = Payment::select('payment_month')
    ->distinct()
    ->orderBy('payment_month')
    ->pluck('payment_month')
    ->toArray();

foreach($uniqueMonths as $month) {
    $count = Payment::where('payment_month', $month)->count();
    echo $month . ': ' . $count . " payments\n";
}
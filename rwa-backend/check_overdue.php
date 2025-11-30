<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Payment;

echo "Overdue payments analysis:\n";
echo "=========================\n";

// Check all months for overdue payments
$months = Payment::select('payment_month')->distinct()->orderBy('payment_month')->pluck('payment_month');

foreach($months as $month) {
    $overdueCount = Payment::where('payment_month', $month)->where('status', 'Overdue')->count();
    if ($overdueCount > 0) {
        echo "{$month}: {$overdueCount} overdue payments\n";
    }
}

echo "\nChecking for case variations:\n";
$statusVariations = ['overdue', 'Overdue', 'OVERDUE', 'Overdue Payment'];
foreach($statusVariations as $status) {
    $count = Payment::where('status', $status)->count();
    if ($count > 0) {
        echo "'{$status}': {$count} records\n";
    }
}
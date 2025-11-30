<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Payment;

echo "Available months in database:\n";
echo "============================\n";

$months = Payment::select('payment_month')
    ->distinct()
    ->orderBy('payment_month')
    ->pluck('payment_month')
    ->toArray();

if (empty($months)) {
    echo "No payment data found in database.\n";
} else {
    foreach ($months as $month) {
        $count = Payment::where('payment_month', $month)->count();
        echo $month . ': ' . $count . " payments\n";
    }
}

echo "\nCurrent month (what controller uses by default): " . \Carbon\Carbon::now()->format('Y-m') . "\n";
echo "PaymentSeeder generates months from: " . \Carbon\Carbon::now()->subMonths(5)->format('Y-m') . " to " . \Carbon\Carbon::now()->format('Y-m') . "\n";
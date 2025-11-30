<?php
// Simple script to check payment data
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Current month: " . \Carbon\Carbon::now()->format('Y-m') . "\n";
echo "Total payments: " . \App\Models\Payment::count() . "\n";
echo "November 2025 payments: " . \App\Models\Payment::where('payment_month', '2025-11')->count() . "\n";

echo "\nFirst 5 payment months in database:\n";
$payments = \App\Models\Payment::select('payment_month')->limit(5)->get();
foreach($payments as $payment) {
    echo "- " . $payment->payment_month . "\n";
}

echo "\nDistinct months in database:\n";
$months = \App\Models\Payment::select('payment_month')->distinct()->orderBy('payment_month', 'desc')->limit(10)->get();
foreach($months as $month) {
    echo "- " . $month->payment_month . "\n";
}
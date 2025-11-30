<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Checking database contents...\n";
echo "Residents count: " . App\Models\Resident::count() . "\n";
echo "Payments count: " . App\Models\Payment::count() . "\n";

echo "\nResidents list:\n";
$residents = App\Models\Resident::all();
foreach ($residents as $resident) {
    echo "- {$resident->name} (Flat: {$resident->flat_number})\n";
}

echo "\nPayments summary:\n";
$payments = App\Models\Payment::selectRaw('status, COUNT(*) as count, SUM(amount_paid) as total')
    ->groupBy('status')
    ->get();
    
foreach ($payments as $payment) {
    echo "- {$payment->status}: {$payment->count} payments, Total: ₹{$payment->total}\n";
}
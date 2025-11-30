<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Payment;

echo "Current payment methods analysis:\n";
echo "================================\n";

// Check what payment methods exist
$methods = Payment::whereNotNull('payment_method')
    ->select('payment_method')
    ->distinct()
    ->pluck('payment_method')
    ->toArray();

echo "Current payment methods in database:\n";
foreach($methods as $method) {
    $count = Payment::where('payment_method', $method)->count();
    echo "- '{$method}': {$count} payments\n";
}

$nullMethods = Payment::whereNull('payment_method')->count();
echo "- NULL (no method): {$nullMethods} payments\n";

echo "\nTotal payments: " . Payment::count() . "\n";
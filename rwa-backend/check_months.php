<?php
require_once 'vendor/autoload.php';

use Carbon\Carbon;

echo "PaymentSeeder generates payments for these months:\n";
echo "=====================================\n";

// This is the exact logic from PaymentSeeder.php
$months = [];
for ($i = 5; $i >= 0; $i--) {
    $months[] = Carbon::now()->subMonths($i)->format('Y-m');
}

foreach ($months as $index => $month) {
    echo ($index + 1) . ". " . $month . "\n";
}

echo "\nCurrent month: " . Carbon::now()->format('Y-m') . "\n";
echo "Previous month: " . Carbon::now()->subMonth()->format('Y-m') . "\n";
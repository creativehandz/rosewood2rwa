<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Resident;
use App\Models\Payment;

echo "Creating residents without payments for demonstration...\n";

// Create a few new residents without payments
for ($i = 1; $i <= 5; $i++) {
    $houseNumber = 'H' . (900 + $i); // Use 900+ to avoid conflicts
    $resident = Resident::create([
        'flat_number' => 'A' . (900 + $i),
        'house_number' => $houseNumber,
        'floor' => 'ground_floor',
        'property_type' => '2bhk_flat',
        'owner_name' => 'New Resident ' . $i,
        'contact_number' => '98765' . sprintf('%05d', 43210 + $i),
        'email' => 'newresident' . $i . '@example.com',
        'status' => 'active',
        'occupation' => 'Professional',
        'number_of_family_members' => rand(2, 5),
        'emergency_contact_name' => 'Emergency Contact ' . $i,
        'emergency_contact_number' => '98765' . sprintf('%05d', 54321 + $i),
        'move_in_date' => now()->subDays(rand(1, 30)),
        'remarks' => 'New resident added for demo'
    ]);
    
    echo "Created resident: {$resident->owner_name} (ID: {$resident->id})\n";
}

// Check updated statistics
$totalResidents = Resident::where('status', 'active')->count();
$residentsWithPayments = Payment::where('payment_month', '2025-11')
    ->distinct('resident_id')
    ->count('resident_id');
$residentsWithoutPayments = $totalResidents - $residentsWithPayments;

echo "\nUpdated Statistics:\n";
echo "Total Active Residents: {$totalResidents}\n";
echo "Residents with Payments: {$residentsWithPayments}\n";
echo "Residents without Payments: {$residentsWithoutPayments}\n";
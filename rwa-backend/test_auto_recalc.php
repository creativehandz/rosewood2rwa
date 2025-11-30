<?php

require_once __DIR__ . '/bootstrap/app.php';

use App\Models\Payment;

// Test automatic recalculation by updating a payment amount_due
$payment = Payment::where('resident_id', 2)->where('payment_month', '2024-10')->first();

if ($payment) {
    echo "Before update:\n";
    echo "Payment Oct: Due={$payment->amount_due}, Paid={$payment->amount_paid}\n";
    
    // Get resident current maintenance
    $resident = $payment->resident;
    echo "Resident maintenance before: ₹{$resident->monthly_maintenance}\n";
    
    // Check future payments before
    $futurePayments = Payment::where('resident_id', 2)
        ->where('payment_month', '>', '2024-10')
        ->orderBy('payment_month')
        ->get();
    echo "Future payments before:\n";
    foreach ($futurePayments as $fp) {
        echo "{$fp->payment_month}: Due={$fp->amount_due}, Paid={$fp->amount_paid}\n";
    }
    
    // Update the payment with a different amount_due (simulate changing maintenance from 1000 to 1200)
    $payment->amount_due = 1600; // 1200 base + 400 carry-forward
    $payment->save();
    
    echo "\nAfter update:\n";
    $payment->refresh();
    echo "Payment Oct: Due={$payment->amount_due}, Paid={$payment->amount_paid}\n";
    
    // Check if resident maintenance was updated
    $resident->refresh();
    echo "Resident maintenance after: ₹{$resident->monthly_maintenance}\n";
    
    // Check future payments after
    $futurePayments = Payment::where('resident_id', 2)
        ->where('payment_month', '>', '2024-10')
        ->orderBy('payment_month')
        ->get();
    echo "Future payments after:\n";
    foreach ($futurePayments as $fp) {
        echo "{$fp->payment_month}: Due={$fp->amount_due}, Paid={$fp->amount_paid}\n";
    }
} else {
    echo "Payment not found\n";
}
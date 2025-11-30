<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Payment;

class ResetTestPaymentCommand extends Command
{
    protected $signature = 'reset:test-payment {resident_id=9}';
    protected $description = 'Reset payment to original values for testing';

    public function handle()
    {
        $residentId = $this->argument('resident_id');
        
        $payment = Payment::where('resident_id', $residentId)
            ->where('payment_month', '2025-10')
            ->first();
            
        if (!$payment) {
            $this->error("Payment not found");
            return;
        }
        
        // Reset to original values
        $payment->amount_due = 800;
        $payment->amount_paid = 400;
        $payment->save();
        
        $this->info("Payment reset: Due=₹{$payment->amount_due}, Paid=₹{$payment->amount_paid}");
    }
}
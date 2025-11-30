<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Payment;

class TestPaymentAmountChangeCommand extends Command
{
    protected $signature = 'test:payment-change {resident_id=9}';
    protected $description = 'Test automatic recalculation when payment amount is changed';

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
        
        $this->info("Before payment change:");
        $this->line("Payment Oct: Due=₹{$payment->amount_due}, Paid=₹{$payment->amount_paid}");
        
        // Check future payments before
        $futurePayments = Payment::where('resident_id', $residentId)
            ->where('payment_month', '>', '2025-10')
            ->orderBy('payment_month')
            ->get();
        $this->line("Future payments before:");
        foreach ($futurePayments as $fp) {
            $this->line("{$fp->payment_month}: Due=₹{$fp->amount_due}, Paid=₹{$fp->amount_paid}");
        }
        
        // Update the payment amount (simulate making a payment)
        $payment->amount_paid = 800; // Pay more, reducing carry-forward
        $payment->save();
        
        $this->info("\nAfter payment change:");
        $payment->refresh();
        $this->line("Payment Oct: Due=₹{$payment->amount_due}, Paid=₹{$payment->amount_paid}");
        
        // Check future payments after
        $futurePayments = Payment::where('resident_id', $residentId)
            ->where('payment_month', '>', '2025-10')
            ->orderBy('payment_month')
            ->get();
        $this->line("Future payments after:");
        foreach ($futurePayments as $fp) {
            $this->line("{$fp->payment_month}: Due=₹{$fp->amount_due}, Paid=₹{$fp->amount_paid}");
        }
        
        $this->info("Test completed! Payment change should cascade carry-forward recalculations.");
    }
}
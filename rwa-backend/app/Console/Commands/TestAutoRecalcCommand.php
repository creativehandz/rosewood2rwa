<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Payment;

class TestAutoRecalcCommand extends Command
{
    protected $signature = 'test:auto-recalc {resident_id=9}';
    protected $description = 'Test automatic recalculation when payment amount_due is updated';

    public function handle()
    {
        $residentId = $this->argument('resident_id');
        
        $payment = Payment::where('resident_id', $residentId)
            ->where('payment_month', '2025-10')
            ->first();
            
        if (!$payment) {
            $this->error("Payment not found for resident {$residentId} in 2025-10");
            return;
        }
        
        $this->info("Before update:");
        $this->line("Payment Oct: Due=₹{$payment->amount_due}, Paid=₹{$payment->amount_paid}");
        
        // Get resident current maintenance
        $resident = $payment->resident;
        $this->line("Resident maintenance before: ₹{$resident->monthly_maintenance}");
        
        // Check future payments before
        $futurePayments = Payment::where('resident_id', $residentId)
            ->where('payment_month', '>', '2025-10')
            ->orderBy('payment_month')
            ->get();
        $this->line("Future payments before:");
        foreach ($futurePayments as $fp) {
            $this->line("{$fp->payment_month}: Due=₹{$fp->amount_due}, Paid=₹{$fp->amount_paid}");
        }
        
        // Update the payment with a different amount_due (simulate changing maintenance from 800 to 1200)
        // Current: 800 base + 0 carry-forward = 800
        // New: 1200 base + 0 carry-forward = 1200  
        $oldAmountDue = $payment->amount_due;
        $payment->amount_due = 1200; // Change from 800 to 1200 (increase maintenance by 400)
        $payment->save();
        
        $this->info("\nAfter update:");
        $payment->refresh();
        $this->line("Payment Oct: Due=₹{$payment->amount_due}, Paid=₹{$payment->amount_paid}");
        
        // Check if resident maintenance was updated
        $resident->refresh();
        $this->line("Resident maintenance after: ₹{$resident->monthly_maintenance}");
        
        // Check future payments after
        $futurePayments = Payment::where('resident_id', $residentId)
            ->where('payment_month', '>', '2025-10')
            ->orderBy('payment_month')
            ->get();
        $this->line("Future payments after:");
        foreach ($futurePayments as $fp) {
            $this->line("{$fp->payment_month}: Due=₹{$fp->amount_due}, Paid=₹{$fp->amount_paid}");
        }
        
        $this->info("Test completed! Check if maintenance and future payments were updated automatically.");
    }
}
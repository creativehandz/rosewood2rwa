<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Payment;

class CheckPaymentCommand extends Command
{
    protected $signature = 'check:payment {resident_id} {month}';
    protected $description = 'Check a specific payment details';

    public function handle()
    {
        $residentId = $this->argument('resident_id');
        $month = $this->argument('month');
        
        $payment = Payment::where('resident_id', $residentId)
            ->where('payment_month', $month)
            ->first();
            
        if (!$payment) {
            $this->error("Payment not found");
            return;
        }
        
        $this->info("Payment details for {$month}:");
        $this->line("Due: ₹{$payment->amount_due}");
        $this->line("Paid: ₹{$payment->amount_paid}");
        $this->line("Status: {$payment->status}");
        $this->line("Remarks: {$payment->remarks}");
    }
}
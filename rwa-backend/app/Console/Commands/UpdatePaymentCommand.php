<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\Resident;
use Illuminate\Console\Command;

class UpdatePaymentCommand extends Command
{
    protected $signature = 'payment:update {resident} {month} {amount_paid}';
    protected $description = 'Update payment amount for testing';

    public function handle(): int
    {
        $residentName = $this->argument('resident');
        $month = $this->argument('month');
        $amountPaid = floatval($this->argument('amount_paid'));
        
        $resident = Resident::where('owner_name', 'LIKE', "%{$residentName}%")->first();
        
        if (!$resident) {
            $this->error("Resident not found");
            return self::FAILURE;
        }
        
        $payment = Payment::where('resident_id', $resident->id)
            ->where('payment_month', $month)
            ->first();
            
        if (!$payment) {
            $this->error("Payment not found for {$month}");
            return self::FAILURE;
        }
        
        $oldPaid = $payment->amount_paid;
        $payment->update([
            'amount_paid' => $amountPaid,
            'status' => $amountPaid >= $payment->amount_due ? 'paid' : ($amountPaid > 0 ? 'partial' : 'pending')
        ]);
        
        $this->info("Updated {$resident->owner_name} {$month}: ₹{$oldPaid} → ₹{$amountPaid}");
        
        return self::SUCCESS;
    }
}
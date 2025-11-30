<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Payment;

class DebugCarryForwardCommand extends Command
{
    protected $signature = 'debug:carry-forward {resident_id=9}';
    protected $description = 'Debug carry-forward calculations step by step';

    public function handle()
    {
        $residentId = $this->argument('resident_id');
        
        $payments = Payment::where('resident_id', $residentId)
            ->orderBy('payment_month')
            ->get();
            
        $this->info("Step-by-step carry-forward calculation:");
        
        $carryForward = 0;
        foreach ($payments as $payment) {
            $this->line("\nMonth: {$payment->payment_month}");
            $this->line("  Amount Due: ₹{$payment->amount_due}");
            $this->line("  Amount Paid: ₹{$payment->amount_paid}");
            $this->line("  Current Balance: ₹" . ($payment->amount_due - $payment->amount_paid));
            $this->line("  Carry-forward INTO this month: ₹{$carryForward}");
            
            // Calculate what the base maintenance should be
            $baseMaintenance = $payment->amount_due - $carryForward;
            $this->line("  Calculated base maintenance: ₹{$baseMaintenance}");
            
            // Calculate new carry-forward for next month
            $newCarryForward = max(0, $payment->amount_due - $payment->amount_paid);
            $this->line("  Carry-forward OUT of this month: ₹{$newCarryForward}");
            
            $carryForward = $newCarryForward;
        }
        
        $resident = $payments->first()->resident;
        $this->line("\nResident's monthly maintenance: ₹{$resident->monthly_maintenance}");
    }
}
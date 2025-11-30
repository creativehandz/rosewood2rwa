<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\Resident;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckResidentExtendedCommand extends Command
{
    protected $signature = 'payments:check-extended {name}';
    protected $description = 'Check payment details for a specific resident with extended months';

    public function handle(): int
    {
        $name = $this->argument('name');
        
        $resident = Resident::where('owner_name', 'LIKE', "%{$name}%")->first();
        
        if (!$resident) {
            $this->error("Resident with name containing '{$name}' not found");
            return self::FAILURE;
        }
        
        $this->info("Resident: {$resident->owner_name}");
        $this->info("House: " . ($resident->house_number ?? $resident->flat_number));
        $this->info("Monthly Maintenance: ₹" . number_format($resident->monthly_maintenance, 2));
        $this->newLine();
        
        $payments = Payment::where('resident_id', $resident->id)
            ->whereIn('payment_month', ['2025-10', '2025-11', '2025-12', '2026-01'])
            ->orderBy('payment_month')
            ->get();
            
        $this->table(
            ['Month', 'Amount Due', 'Amount Paid', 'Balance', 'Status'],
            $payments->map(function ($payment) {
                $balance = $payment->amount_due - $payment->amount_paid;
                return [
                    Carbon::parse($payment->payment_month . '-01')->format('M Y'),
                    '₹' . number_format($payment->amount_due, 2),
                    '₹' . number_format($payment->amount_paid, 2),
                    '₹' . number_format($balance, 2),
                    $payment->status
                ];
            })
        );
        
        // Manual calculation verification
        $this->newLine();
        $this->info('Manual Calculation Verification:');
        
        $months = ['2025-10', '2025-11', '2025-12', '2026-01'];
        $previousBalance = 0;
        
        foreach ($months as $i => $month) {
            $payment = $payments->where('payment_month', $month)->first();
            if (!$payment) continue;
            
            $monthName = Carbon::parse($month . '-01')->format('M Y');
            $balance = $payment->amount_due - $payment->amount_paid;
            
            if ($i == 0) {
                // First month
                $this->line("{$monthName}: Due=₹{$payment->amount_due}, Paid=₹{$payment->amount_paid}, Balance=₹{$balance}");
            } else {
                // Subsequent months
                $expectedDue = $resident->monthly_maintenance + $previousBalance;
                $actualDue = $payment->amount_due;
                
                $this->line("{$monthName}: Expected=₹{$expectedDue} (₹{$resident->monthly_maintenance} + ₹{$previousBalance}), Actual=₹{$actualDue}");
                
                if ($expectedDue != $actualDue) {
                    $this->error("  → {$monthName} calculation is WRONG! Difference: ₹" . ($actualDue - $expectedDue));
                } else {
                    $this->info("  → {$monthName} calculation is correct");
                }
            }
            
            $previousBalance = $balance;
        }
        
        return self::SUCCESS;
    }
}
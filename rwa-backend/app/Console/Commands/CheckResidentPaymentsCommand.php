<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\Resident;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckResidentPaymentsCommand extends Command
{
    protected $signature = 'payments:check-resident {name}';
    protected $description = 'Check payment details for a specific resident';

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
            ->whereIn('payment_month', ['2025-10', '2025-11', '2025-12'])
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
        
        // Manual calculation
        $this->newLine();
        $this->info('Manual Calculation Verification:');
        
        $octPayment = $payments->where('payment_month', '2025-10')->first();
        $novPayment = $payments->where('payment_month', '2025-11')->first();
        $decPayment = $payments->where('payment_month', '2025-12')->first();
        
        if ($octPayment) {
            $octBalance = $octPayment->amount_due - $octPayment->amount_paid;
            $this->line("October Balance: ₹{$octPayment->amount_due} - ₹{$octPayment->amount_paid} = ₹{$octBalance}");
        }
        
        if ($novPayment && $octPayment) {
            $expectedNovDue = $resident->monthly_maintenance + ($octPayment->amount_due - $octPayment->amount_paid);
            $actualNovDue = $novPayment->amount_due;
            $this->line("November Expected: ₹{$resident->monthly_maintenance} + ₹{$octBalance} = ₹{$expectedNovDue}");
            $this->line("November Actual: ₹{$actualNovDue}");
            
            if ($expectedNovDue != $actualNovDue) {
                $this->error("November calculation is WRONG!");
            } else {
                $this->info("November calculation is correct");
            }
        }
        
        if ($decPayment && $novPayment) {
            $novBalance = $novPayment->amount_due - $novPayment->amount_paid;
            $expectedDecDue = $resident->monthly_maintenance + $novBalance;
            $actualDecDue = $decPayment->amount_due;
            $this->line("December Expected: ₹{$resident->monthly_maintenance} + ₹{$novBalance} = ₹{$expectedDecDue}");
            $this->line("December Actual: ₹{$actualDecDue}");
            
            if ($expectedDecDue != $actualDecDue) {
                $this->error("December calculation is WRONG!");
            } else {
                $this->info("December calculation is correct");
            }
        }
        
        return self::SUCCESS;
    }
}
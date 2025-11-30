<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\Resident;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculateMaintenanceCommand extends Command
{
    protected $signature = 'payments:recalculate-maintenance 
                          {resident_name} 
                          {from_month} 
                          {--dry-run : Show what would be changed without making changes}';
    
    protected $description = 'Recalculate all payments from a specific month after maintenance change';

    public function handle(): int
    {
        $residentName = $this->argument('resident_name');
        $fromMonth = $this->argument('from_month');
        $dryRun = $this->option('dry-run');
        
        // Find resident
        $resident = Resident::where('owner_name', 'LIKE', "%{$residentName}%")->first();
        
        if (!$resident) {
            $this->error("Resident not found");
            return self::FAILURE;
        }
        
        $this->info("Recalculating payments for: {$resident->owner_name}");
        $this->info("From month: {$fromMonth}");
        $this->info("Current maintenance: ₹" . number_format($resident->monthly_maintenance, 2));
        $this->newLine();
        
        // Get all payments from the specified month onwards
        $payments = Payment::where('resident_id', $resident->id)
            ->where('payment_month', '>=', $fromMonth)
            ->orderBy('payment_month')
            ->get();
            
        if ($payments->isEmpty()) {
            $this->info("No payments found from {$fromMonth} onwards");
            return self::SUCCESS;
        }
        
        $changes = [];
        
        $updatedPayments = []; // Track updated payments for sequential calculation
        
        foreach ($payments as $payment) {
            // Calculate carry-forward from previous month
            $previousMonth = Carbon::parse($payment->payment_month . '-01')->subMonth()->format('Y-m');
            
            // Check if we have an updated payment from this run, otherwise use DB
            $previousPayment = $updatedPayments[$previousMonth] ?? 
                Payment::where('resident_id', $resident->id)
                    ->where('payment_month', $previousMonth)
                    ->first();
                
            $carryForward = 0;
            if ($previousPayment) {
                if (is_array($previousPayment)) {
                    // This is an updated payment from our changes
                    $carryForward = max(0, $previousPayment['amount_due'] - $previousPayment['amount_paid']);
                } else {
                    // This is from the database
                    $carryForward = max(0, $previousPayment->amount_due - $previousPayment->amount_paid);
                }
            }
            
            // Calculate new amount due
            $newAmountDue = $resident->monthly_maintenance + $carryForward;
            $oldAmountDue = $payment->amount_due;
            
            if ($newAmountDue != $oldAmountDue) {
                $changes[] = [
                    'month' => $payment->payment_month,
                    'old_due' => $oldAmountDue,
                    'new_due' => $newAmountDue,
                    'carry_forward' => $carryForward,
                    'payment' => $payment
                ];
                
                // Track this payment for sequential calculations
                $updatedPayments[$payment->payment_month] = [
                    'amount_due' => $newAmountDue,
                    'amount_paid' => $payment->amount_paid
                ];
            } else {
                // Even if no change, track it for sequential calculations
                $updatedPayments[$payment->payment_month] = [
                    'amount_due' => $payment->amount_due,
                    'amount_paid' => $payment->amount_paid
                ];
            }
        }
        
        if (empty($changes)) {
            $this->info("No changes needed - all payments are already correct");
            return self::SUCCESS;
        }
        
        // Show changes
        $this->table(
            ['Month', 'Old Amount Due', 'New Amount Due', 'Carry Forward', 'Difference'],
            array_map(function ($change) {
                $diff = $change['new_due'] - $change['old_due'];
                return [
                    Carbon::parse($change['month'] . '-01')->format('M Y'),
                    '₹' . number_format($change['old_due'], 2),
                    '₹' . number_format($change['new_due'], 2),
                    '₹' . number_format($change['carry_forward'], 2),
                    ($diff >= 0 ? '+' : '') . '₹' . number_format($diff, 2)
                ];
            }, $changes)
        );
        
        if ($dryRun) {
            $this->newLine();
            $this->warn('DRY RUN - No changes made');
            return self::SUCCESS;
        }
        
        $this->newLine();
        if (!$this->confirm("Apply these changes to {$resident->owner_name}'s payments?")) {
            $this->info('Operation cancelled');
            return self::SUCCESS;
        }
        
        // Apply changes
        try {
            DB::beginTransaction();
            
            $this->info('Updating payment records...');
            $bar = $this->output->createProgressBar(count($changes));
            $bar->start();
            
            foreach ($changes as $change) {
                $payment = $change['payment'];
                $payment->update([
                    'amount_due' => $change['new_due'],
                    'remarks' => $this->generateRemarks($resident->monthly_maintenance, $change['carry_forward'])
                ]);
                
                // Update status based on new amount due
                if ($payment->amount_paid >= $change['new_due']) {
                    $payment->update(['status' => 'paid']);
                } elseif ($payment->amount_paid > 0) {
                    $payment->update(['status' => 'partial']);
                }
                
                $bar->advance();
            }
            
            $bar->finish();
            $this->newLine();
            
            DB::commit();
            
            $this->newLine();
            $this->info("✅ Successfully updated " . count($changes) . " payment records");
            
        } catch (\Exception $e) {
            DB::rollback();
            $this->error('Error updating payments: ' . $e->getMessage());
            return self::FAILURE;
        }
        
        return self::SUCCESS;
    }
    
    private function generateRemarks(float $baseMaintenance, float $carryForward): string
    {
        $remarks = [];
        if ($carryForward > 0) {
            $remarks[] = "Includes carry-forward of ₹" . number_format($carryForward, 2) . " from previous month";
        }
        $remarks[] = "Base maintenance: ₹" . number_format($baseMaintenance, 2);
        
        return implode('. ', $remarks);
    }
}
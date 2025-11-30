<?php

namespace App\Console\Commands;

use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetPaymentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'payments:reset 
                          {months* : Months to reset (YYYY-MM format, e.g., 2025-10 2025-11)}
                          {--dry-run : Show what would be changed without actually changing}';

    /**
     * The console command description.
     */
    protected $description = 'Reset payments to unpaid status for specified months';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $months = $this->argument('months');
        $dryRun = $this->option('dry-run');

        if (empty($months)) {
            $this->error('Please specify at least one month to reset (e.g., 2025-10 2025-11)');
            return self::FAILURE;
        }

        // Validate month formats
        foreach ($months as $month) {
            if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
                $this->error("Invalid month format: {$month}. Use YYYY-MM format (e.g., 2025-10)");
                return self::FAILURE;
            }
        }

        $this->info("Reset Payments Command");
        $this->info("Months to reset: " . implode(', ', $months));
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        try {
            // Get payments to reset
            $paymentsToReset = Payment::whereIn('payment_month', $months)->get();

            if ($paymentsToReset->isEmpty()) {
                $this->info('No payment records found for the specified months');
                return self::SUCCESS;
            }

            // Show summary by month
            $monthlyBreakdown = $paymentsToReset->groupBy('payment_month')
                ->map(function ($payments, $month) {
                    $paidCount = $payments->where('status', '!=', 'pending')->count();
                    $totalPaid = $payments->sum('amount_paid');
                    
                    return [
                        'month' => $month,
                        'total_count' => $payments->count(),
                        'paid_count' => $paidCount,
                        'total_amount_paid' => $totalPaid,
                        'payments_with_methods' => $payments->whereNotNull('payment_method')->count(),
                        'payments_with_transactions' => $payments->whereNotNull('transaction_id')->count()
                    ];
                })
                ->sortBy('month');

            $this->table(
                ['Month', 'Total Records', 'Currently Paid', 'Amount Paid', 'With Methods', 'With Transactions'],
                $monthlyBreakdown->map(function ($data) {
                    return [
                        Carbon::parse($data['month'] . '-01')->format('M Y'),
                        $data['total_count'],
                        $data['paid_count'],
                        '₹' . number_format($data['total_amount_paid'], 2),
                        $data['payments_with_methods'],
                        $data['payments_with_transactions']
                    ];
                })
            );

            if ($dryRun) {
                $this->newLine();
                $this->info('Changes that would be made:');
                $this->line('• Set amount_paid = 0.00 for all records');
                $this->line('• Set status = "pending" for all records');
                $this->line('• Clear payment_method (set to NULL)');
                $this->line('• Clear transaction_id (set to NULL)');
                $this->line('• Clear payment_date (set to NULL)');
                $this->warn('DRY RUN - No actual changes made');
                return self::SUCCESS;
            }

            // Confirmation
            $totalRecords = $paymentsToReset->count();
            $totalCurrentlyPaid = $paymentsToReset->sum('amount_paid');
            
            $this->newLine();
            $this->warn("This will reset {$totalRecords} payment records across " . count($months) . " months");
            $this->warn("Current total paid amount: ₹" . number_format($totalCurrentlyPaid, 2));
            
            if (!$this->confirm('Are you sure you want to reset all these payments to unpaid status?')) {
                $this->info('Operation cancelled.');
                return self::SUCCESS;
            }

            // Perform reset in transaction
            DB::beginTransaction();

            $this->info('Resetting payment records...');
            $bar = $this->output->createProgressBar($totalRecords);
            $bar->start();

            $updatedCount = 0;
            foreach ($paymentsToReset as $payment) {
                $payment->update([
                    'amount_paid' => 0.00,
                    'status' => 'pending',
                    'payment_method' => null,
                    'transaction_id' => null,
                    'payment_date' => null,
                    'remarks' => null
                ]);
                $updatedCount++;
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();

            DB::commit();

            $this->newLine();
            $this->info("✅ Successfully reset {$updatedCount} payment records");
            $this->info("✅ All payments for " . implode(', ', $months) . " are now unpaid with pending status");
            $this->info("✅ Cleared payment methods, transaction IDs, and payment dates");

            // Show updated summary
            $this->newLine();
            $this->info('Updated Summary:');
            foreach ($months as $month) {
                $count = Payment::where('payment_month', $month)->count();
                $pendingCount = Payment::where('payment_month', $month)->where('status', 'pending')->count();
                $totalDue = Payment::where('payment_month', $month)->sum('amount_due');
                
                $monthName = Carbon::parse($month . '-01')->format('F Y');
                $this->line("• {$monthName}: {$pendingCount}/{$count} pending, ₹" . number_format($totalDue, 2) . " total due");
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error resetting payments: ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
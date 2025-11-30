<?php

namespace App\Console\Commands;

use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupPaymentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'payments:cleanup 
                          {--before= : Remove payments before this month (YYYY-MM format)}
                          {--dry-run : Show what would be deleted without actually deleting}
                          {--confirm : Skip confirmation prompt}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up old payment records before a specified month';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $beforeMonth = $this->option('before');
        $dryRun = $this->option('dry-run');
        $skipConfirm = $this->option('confirm');

        if (!$beforeMonth) {
            $beforeMonth = $this->ask('Enter the month to clean before (YYYY-MM format, e.g., 2025-10)');
        }

        // Validate month format
        if (!preg_match('/^\d{4}-\d{2}$/', $beforeMonth)) {
            $this->error('Invalid month format. Please use YYYY-MM format (e.g., 2025-10)');
            return self::FAILURE;
        }

        try {
            // Check what will be deleted
            $paymentsToDelete = Payment::where('payment_month', '<', $beforeMonth)->get();
            $deleteCount = $paymentsToDelete->count();

            if ($deleteCount === 0) {
                $this->info("No payments found before {$beforeMonth}");
                return self::SUCCESS;
            }

            $this->info("Found {$deleteCount} payment records before {$beforeMonth}");

            // Show summary by month
            $monthlyBreakdown = $paymentsToDelete->groupBy('payment_month')
                ->map(function ($payments, $month) {
                    return [
                        'month' => $month,
                        'count' => $payments->count(),
                        'total_due' => $payments->sum('amount_due'),
                        'total_paid' => $payments->sum('amount_paid')
                    ];
                })
                ->sortBy('month');

            $this->table(
                ['Month', 'Records', 'Total Due', 'Total Paid'],
                $monthlyBreakdown->map(function ($data) {
                    return [
                        Carbon::parse($data['month'] . '-01')->format('M Y'),
                        $data['count'],
                        '₹' . number_format($data['total_due'], 2),
                        '₹' . number_format($data['total_paid'], 2)
                    ];
                })
            );

            if ($dryRun) {
                $this->warn('DRY RUN MODE - No records will be deleted');
                return self::SUCCESS;
            }

            // Confirmation
            if (!$skipConfirm) {
                if (!$this->confirm("Are you sure you want to DELETE {$deleteCount} payment records before {$beforeMonth}?")) {
                    $this->info('Operation cancelled.');
                    return self::SUCCESS;
                }
            }

            // Delete payments
            DB::beginTransaction();
            
            $deleted = Payment::where('payment_month', '<', $beforeMonth)->delete();
            
            DB::commit();

            $this->info("✅ Successfully deleted {$deleted} payment records before {$beforeMonth}");
            
            return self::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Error cleaning up payments: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
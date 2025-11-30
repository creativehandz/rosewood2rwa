<?php

namespace App\Console\Commands;

use App\Models\Resident;
use App\Models\Payment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupTestResidentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'residents:cleanup-test 
                          {--dry-run : Show what would be deleted without actually deleting}
                          {--confirm : Skip confirmation prompt}';

    /**
     * The console command description.
     */
    protected $description = 'Remove test residents with names like "New Resident 1", "New Resident 2", etc.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $confirm = $this->option('confirm');

        $this->info('Cleanup Test Residents Command');
        $this->info('===========================');
        
        // Find test residents
        $testResidents = Resident::where('owner_name', 'LIKE', 'New Resident%')
            ->orWhere('owner_name', 'REGEXP', '^New Resident [0-9]+$')
            ->get();

        if ($testResidents->isEmpty()) {
            $this->info('No test residents found with names like "New Resident X"');
            return self::SUCCESS;
        }

        $this->info("Found {$testResidents->count()} test residents:");
        $this->newLine();

        // Show residents to be deleted
        $headers = ['ID', 'Name', 'House/Flat', 'Contact', 'Monthly Maintenance', 'Payments Count'];
        $rows = [];

        foreach ($testResidents as $resident) {
            $paymentsCount = Payment::where('resident_id', $resident->id)->count();
            
            $rows[] = [
                $resident->id,
                $resident->owner_name,
                $resident->house_number ?? $resident->flat_number ?? '-',
                $resident->contact_number ?? '-',
                $resident->monthly_maintenance ? '₹' . number_format($resident->monthly_maintenance, 2) : '-',
                $paymentsCount
            ];
        }

        $this->table($headers, $rows);

        // Count related payments
        $totalPayments = 0;
        foreach ($testResidents as $resident) {
            $totalPayments += Payment::where('resident_id', $resident->id)->count();
        }

        if ($totalPayments > 0) {
            $this->warn("Warning: These residents have {$totalPayments} associated payment records that will also be deleted!");
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('DRY RUN MODE - No records will be deleted');
            $this->info("Would delete:");
            $this->line("• {$testResidents->count()} test residents");
            $this->line("• {$totalPayments} associated payment records");
            return self::SUCCESS;
        }

        $this->newLine();
        $this->warn("This will permanently delete {$testResidents->count()} test residents and {$totalPayments} payment records!");
        
        if (!$confirm && !$this->confirm('Are you sure you want to delete these test residents?')) {
            $this->info('Operation cancelled.');
            return self::SUCCESS;
        }

        // Perform deletion in transaction
        try {
            DB::beginTransaction();

            $this->info('Deleting test residents and their payments...');
            $bar = $this->output->createProgressBar($testResidents->count());
            $bar->start();

            $deletedResidents = 0;
            $deletedPayments = 0;

            foreach ($testResidents as $resident) {
                // Delete associated payments first
                $paymentsDeleted = Payment::where('resident_id', $resident->id)->delete();
                $deletedPayments += $paymentsDeleted;

                // Delete resident
                $resident->delete();
                $deletedResidents++;

                $bar->advance();
            }

            $bar->finish();
            $this->newLine();

            DB::commit();

            $this->newLine();
            $this->info("✅ Successfully deleted {$deletedResidents} test residents");
            $this->info("✅ Successfully deleted {$deletedPayments} associated payment records");
            
            // Show updated resident count
            $remainingResidents = Resident::count();
            $this->info("✅ Remaining active residents: {$remainingResidents}");

        } catch (\Exception $e) {
            DB::rollback();
            $this->error('Error during deletion: ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\Resident;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateMonthlyPayments extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'payments:generate-monthly 
                          {month? : The month in YYYY-MM format (defaults to current month)} 
                          {--force : Force generation even if payments already exist}
                          {--dry-run : Show what would be generated without creating records}';

    /**
     * The console command description.
     */
    protected $description = 'Generate monthly maintenance payments for all residents with carry-forward logic for unpaid amounts';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $month = $this->argument('month') ?: Carbon::now()->format('Y-m');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');

        // Validate month format
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $this->error('Invalid month format. Please use YYYY-MM format (e.g., 2025-12)');
            return Command::FAILURE;
        }

        $this->info("Generating monthly payments for: {$month}");
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No records will be created');
        }

        try {
            DB::beginTransaction();

            // Check if payments already exist for this month
            $existingCount = Payment::where('payment_month', $month)->count();
            
            if ($existingCount > 0 && !$force) {
                $this->warn("Found {$existingCount} existing payments for {$month}.");
                if (!$this->confirm('Do you want to continue and update existing payments?')) {
                    return self::INVALID;
                }
            }

            // Get all active residents
            $residents = Resident::where('status', 'active')->get();
            
            if ($residents->isEmpty()) {
                $this->warn('No active residents found.');
                return Command::FAILURE;
            }

            $this->info("Processing {$residents->count()} active residents...");

            $stats = [
                'created' => 0,
                'updated' => 0,
                'total_carry_forward' => 0,
                'total_amount_due' => 0
            ];

            $progressBar = $this->output->createProgressBar($residents->count());
            $progressBar->start();

            foreach ($residents as $resident) {
                $result = $this->generatePaymentForResident($resident, $month, $dryRun);
                
                $stats['created'] += $result['created'];
                $stats['updated'] += $result['updated'];
                $stats['total_carry_forward'] += $result['carry_forward'];
                $stats['total_amount_due'] += $result['amount_due'];

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine();

            if (!$dryRun) {
                DB::commit();
                $this->info('✅ Monthly payments generated successfully!');
            } else {
                DB::rollBack();
                $this->info('✅ Dry run completed - no changes made');
            }

            // Display statistics
            $this->displayStatistics($stats, $month);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Error generating payments: ' . $e->getMessage());
            Log::error('Monthly payment generation failed', [
                'month' => $month,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Generate payment for a specific resident with carry-forward logic
     */
    private function generatePaymentForResident(Resident $resident, string $month, bool $dryRun): array
    {
        $result = [
            'created' => 0,
            'updated' => 0,
            'carry_forward' => 0,
            'amount_due' => 0
        ];

        // Calculate carry-forward from previous unpaid amounts
        $carryForward = $this->calculateCarryForward($resident, $month);
        
        // Base maintenance amount for this month
        $baseAmount = $resident->monthly_maintenance ?? 0;
        
        // Total amount due = base maintenance + carry-forward
        $totalAmountDue = $baseAmount + $carryForward;

        $result['carry_forward'] = $carryForward;
        $result['amount_due'] = $totalAmountDue;

        if ($dryRun) {
            $this->line("\nResident: {$resident->house_number} ({$resident->owner_name})");
            $this->line("  Base Amount: ₹" . number_format($baseAmount, 2));
            if ($carryForward > 0) {
                $this->line("  Carry-forward: ₹" . number_format($carryForward, 2));
            }
            $this->line("  Total Due: ₹" . number_format($totalAmountDue, 2));
            return $result;
        }

        // Check if payment already exists for this month
        $existingPayment = Payment::where('resident_id', $resident->id)
                                ->where('payment_month', $month)
                                ->first();

        $remarks = [];
        if ($carryForward > 0) {
            $remarks[] = "Includes carry-forward of ₹" . number_format($carryForward, 2) . " from previous months";
        }
        $remarks[] = "Base maintenance: ₹" . number_format($baseAmount, 2);

        if ($existingPayment) {
            // Update existing payment with new carry-forward calculation
            $existingPayment->update([
                'amount_due' => $totalAmountDue,
                'remarks' => implode('. ', $remarks),
                'updated_at' => now()
            ]);
            
            // Recalculate status based on current amount_paid vs new amount_due
            $existingPayment->updateStatus();
            
            $result['updated'] = 1;
        } else {
            // Create new payment record
            Payment::create([
                'resident_id' => $resident->id,
                'payment_month' => $month,
                'amount_due' => $totalAmountDue,
                'amount_paid' => 0,
                'payment_date' => null,
                'payment_method' => null,
                'transaction_id' => null,
                'status' => 'Pending',
                'remarks' => implode('. ', $remarks)
            ]);
            
            $result['created'] = 1;
        }

        return $result;
    }

    /**
     * Calculate carry-forward amount from previous month's unpaid balance only
     */
    private function calculateCarryForward(Resident $resident, string $currentMonth): float
    {
        // Get the previous month in YYYY-MM format
        $previousMonth = \Carbon\Carbon::parse($currentMonth . '-01')->subMonth()->format('Y-m');
        
        // Get the previous month's payment record
        $previousPayment = Payment::where('resident_id', $resident->id)
            ->where('payment_month', $previousMonth)
            ->first();

        if (!$previousPayment) {
            return 0; // No previous payment record
        }

        // Calculate balance from previous month only
        $balance = $previousPayment->amount_due - $previousPayment->amount_paid;
        
        return $balance > 0 ? $balance : 0;
    }

    /**
     * Display generation statistics
     */
    private function displayStatistics(array $stats, string $month): void
    {
        $this->newLine();
        $this->info("📊 Generation Summary for {$month}:");
        
        $this->table(
            ['Metric', 'Count/Amount'],
            [
                ['New payments created', $stats['created']],
                ['Existing payments updated', $stats['updated']],
                ['Total carry-forward amount', '₹' . number_format($stats['total_carry_forward'], 2)],
                ['Total amount due', '₹' . number_format($stats['total_amount_due'], 2)],
                ['Average amount per resident', ($stats['created'] + $stats['updated']) > 0 ? '₹' . number_format($stats['total_amount_due'] / ($stats['created'] + $stats['updated']), 2) : '₹0.00']
            ]
        );

        if ($stats['total_carry_forward'] > 0) {
            $carryForwardPercent = ($stats['total_carry_forward'] / $stats['total_amount_due']) * 100;
            $this->warn("⚠️ {$carryForwardPercent}% of total amount due is from carry-forward balances");
        }
    }

    /**
     * Get the carry-forward breakdown for a resident (for debugging)
     */
    public function getCarryForwardBreakdown(Resident $resident, string $currentMonth): array
    {
        $unpaidPayments = Payment::where('resident_id', $resident->id)
            ->where('payment_month', '<', $currentMonth)
            ->whereRaw('amount_paid < amount_due')
            ->orderBy('payment_month')
            ->get();

        $breakdown = [];
        foreach ($unpaidPayments as $payment) {
            $balance = $payment->amount_due - $payment->amount_paid;
            if ($balance > 0) {
                $breakdown[] = [
                    'month' => $payment->payment_month,
                    'amount_due' => $payment->amount_due,
                    'amount_paid' => $payment->amount_paid,
                    'balance' => $balance,
                    'status' => $payment->status
                ];
            }
        }

        return $breakdown;
    }
}
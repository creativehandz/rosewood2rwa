<?php

namespace App\Console\Commands;

use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckPaymentStatusCommand extends Command
{
    protected $signature = 'payments:check-status {months*}';
    protected $description = 'Check payment status for specified months';

    public function handle(): int
    {
        $months = $this->argument('months') ?: ['2025-10', '2025-11', '2025-12'];
        
        $this->info('Payment Status Summary:');
        $this->info('====================');
        $this->newLine();
        
        foreach ($months as $month) {
            $monthName = Carbon::parse($month . '-01')->format('F Y');
            
            $total = Payment::where('payment_month', $month)->count();
            $pending = Payment::where('payment_month', $month)->where('status', 'pending')->count();
            $paid = Payment::where('payment_month', $month)->where('status', 'paid')->count();
            $totalDue = Payment::where('payment_month', $month)->sum('amount_due');
            $totalPaid = Payment::where('payment_month', $month)->sum('amount_paid');
            $withMethods = Payment::where('payment_month', $month)->whereNotNull('payment_method')->count();
            $withTransactions = Payment::where('payment_month', $month)->whereNotNull('transaction_id')->count();
            
            $this->info($monthName . ':');
            $this->line("  Total Records: {$total}");
            $this->line("  Pending: {$pending}");
            $this->line("  Paid: {$paid}");
            $this->line("  Amount Due: ₹" . number_format($totalDue, 2));
            $this->line("  Amount Paid: ₹" . number_format($totalPaid, 2));
            $this->line("  With Payment Methods: {$withMethods}");
            $this->line("  With Transaction IDs: {$withTransactions}");
            $this->newLine();
        }
        
        return self::SUCCESS;
    }
}
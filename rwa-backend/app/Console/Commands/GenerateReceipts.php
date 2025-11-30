<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Payment;
use App\Services\ReceiptService;

class GenerateReceipts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'receipts:generate {--missing : Generate only missing receipts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate receipts for paid payments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $receiptService = new ReceiptService();
        
        if ($this->option('missing')) {
            $this->info('Generating missing receipts...');
            $result = $receiptService->generateMissingReceipts();
            
            $this->info("Generated {$result['total']} receipts");
            
            if (!empty($result['generated'])) {
                $this->table(['Receipt Numbers'], array_map(fn($r) => [$r], $result['generated']));
            }
            
            if (!empty($result['failed'])) {
                $this->error("Failed payments: " . implode(', ', $result['failed']));
            }
        } else {
            // Generate for all paid payments
            $paidPayments = Payment::where('status', 'Paid')->with('resident')->get();
            $generated = 0;
            
            foreach ($paidPayments as $payment) {
                $receipt = $receiptService->generateReceiptForPayment($payment);
                if ($receipt) {
                    $this->line("Generated receipt {$receipt->receipt_number} for payment {$payment->id}");
                    $generated++;
                }
            }
            
            $this->info("Generated {$generated} receipts total");
        }
        
        return Command::SUCCESS;
    }
}

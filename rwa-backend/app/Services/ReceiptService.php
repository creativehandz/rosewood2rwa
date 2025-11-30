<?php

namespace App\Services;

use App\Models\Receipt;
use App\Models\Payment;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class ReceiptService
{
    /**
     * Auto-generate receipt for a paid payment
     */
    public function generateReceiptForPayment(Payment $payment): ?Receipt
    {
        try {
            // Only generate receipt for paid payments
            if ($payment->status !== 'Paid') {
                Log::info("Skipping receipt generation for payment {$payment->id} - status is not 'Paid'");
                return null;
            }

            // Check if receipt already exists
            $existingReceipt = $payment->receipt;
            if ($existingReceipt) {
                Log::info("Receipt already exists for payment {$payment->id}");
                return $existingReceipt;
            }

            // Create receipt
            $receipt = Receipt::create([
                'payment_id' => $payment->id,
                'receipt_number' => Receipt::generateReceiptNumber(),
                'receipt_date' => $payment->payment_date ?? Carbon::now(),
                'amount' => $payment->amount_paid,
                'tax_amount' => 0, // Can be calculated if needed
                'total_amount' => $payment->amount_paid,
                'notes' => "Payment for {$payment->payment_month}"
            ]);

            Log::info("Receipt {$receipt->receipt_number} generated for payment {$payment->id}");

            return $receipt;

        } catch (\Exception $e) {
            Log::error("Failed to generate receipt for payment {$payment->id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate PDF for a receipt
     */
    public function generatePDF(Receipt $receipt)
    {
        $payment = $receipt->payment()->with('resident')->first();
        
        $data = [
            'receipt' => $receipt,
            'payment' => $payment,
            'resident' => $payment->resident,
            'organizationName' => 'Rosewood RWA',
            'organizationAddress' => 'Your Society Address',
            'organizationPhone' => 'Contact Number',
            'organizationEmail' => 'email@example.com'
        ];

        $pdf = Pdf::loadView('receipts.payment-receipt', $data);
        
        return $pdf;
    }

    /**
     * Bulk generate receipts for all paid payments without receipts
     */
    public function generateMissingReceipts(): array
    {
        $paidPayments = Payment::where('status', 'Paid')
                              ->doesntHave('receipt')
                              ->get();

        $generated = [];
        $failed = [];

        foreach ($paidPayments as $payment) {
            $receipt = $this->generateReceiptForPayment($payment);
            
            if ($receipt) {
                $generated[] = $receipt->receipt_number;
            } else {
                $failed[] = $payment->id;
            }
        }

        return [
            'generated' => $generated,
            'failed' => $failed,
            'total' => count($generated),
            'errors' => count($failed)
        ];
    }
}

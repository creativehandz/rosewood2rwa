<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Payment;
use App\Models\Resident;
use App\Services\PaymentService;
use App\Services\ReceiptService;
use Illuminate\Foundation\Application;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing Receipt Auto-Generation\n";
echo "==============================\n\n";

// Get first resident
$resident = Resident::first();

if (!$resident) {
    echo "No residents found. Please add residents first.\n";
    exit(1);
}

echo "Using resident: {$resident->owner_name} (House: {$resident->house_number})\n\n";

// Create a PaymentService with ReceiptService
$receiptService = new ReceiptService();
$paymentService = new PaymentService($receiptService);

// Test 1: Create a paid payment (should auto-generate receipt)
echo "Test 1: Creating paid payment (should auto-generate receipt)\n";
$paymentData = [
    'resident_id' => $resident->id,
    'payment_month' => '2025-03',
    'amount_due' => 5000.00,
    'amount_paid' => 5000.00,
    'status' => 'Paid',
    'payment_date' => now(),
    'payment_method' => 'Cash'
];

$result = $paymentService->createPayment($paymentData);

if ($result['success']) {
    $payment = $result['data'];
    echo "✓ Payment created successfully: ID {$payment->id}\n";
    
    // Check if receipt was auto-generated
    $receipt = $payment->receipt;
    if ($receipt) {
        echo "✓ Receipt auto-generated: {$receipt->receipt_number}\n";
        echo "  Receipt Date: {$receipt->receipt_date->format('Y-m-d')}\n";
        echo "  Amount: ₹{$receipt->total_amount}\n";
    } else {
        echo "✗ No receipt was generated\n";
    }
} else {
    echo "✗ Failed to create payment: {$result['message']}\n";
}

echo "\n";

// Test 2: Update a pending payment to paid (should auto-generate receipt)
echo "Test 2: Creating pending payment then updating to paid\n";

$pendingPaymentData = [
    'resident_id' => $resident->id,
    'payment_month' => '2025-04',
    'amount_due' => 4500.00,
    'amount_paid' => 0,
    'status' => 'Pending'
];

$pendingResult = $paymentService->createPayment($pendingPaymentData);

if ($pendingResult['success']) {
    $pendingPayment = $pendingResult['data'];
    echo "✓ Pending payment created: ID {$pendingPayment->id}\n";
    
    // Update to paid
    $updateResult = $paymentService->updatePayment($pendingPayment, [
        'amount_paid' => 4500.00,
        'status' => 'Paid',
        'payment_date' => now()
    ]);
    
    if ($updateResult['success']) {
        $updatedPayment = $updateResult['data'];
        echo "✓ Payment updated to paid\n";
        
        $receipt = $updatedPayment->receipt;
        if ($receipt) {
            echo "✓ Receipt auto-generated on update: {$receipt->receipt_number}\n";
        } else {
            echo "✗ No receipt was generated on update\n";
        }
    } else {
        echo "✗ Failed to update payment: {$updateResult['message']}\n";
    }
} else {
    echo "✗ Failed to create pending payment: {$pendingResult['message']}\n";
}

echo "\n";

// Test 3: Generate PDF for receipt
echo "Test 3: Testing PDF generation\n";
$firstReceipt = App\Models\Receipt::first();

if ($firstReceipt) {
    echo "✓ Found receipt: {$firstReceipt->receipt_number}\n";
    
    try {
        $pdf = $receiptService->generatePDF($firstReceipt);
        echo "✓ PDF generated successfully\n";
        echo "  PDF size: " . strlen($pdf->output()) . " bytes\n";
    } catch (Exception $e) {
        echo "✗ PDF generation failed: {$e->getMessage()}\n";
    }
} else {
    echo "✗ No receipts found for PDF test\n";
}

echo "\nReceipt auto-generation test completed!\n";
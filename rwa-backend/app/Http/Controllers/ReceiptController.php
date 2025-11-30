<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use App\Models\Payment;
use App\Services\ReceiptService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ReceiptController extends Controller
{
    protected $receiptService;

    public function __construct(ReceiptService $receiptService)
    {
        $this->receiptService = $receiptService;
    }

    /**
     * Get receipt by payment ID
     */
    public function getByPayment(int $paymentId): JsonResponse
    {
        $payment = Payment::findOrFail($paymentId);
        $receipt = $payment->receipt;

        if (!$receipt) {
            return response()->json([
                'status' => 'error',
                'message' => 'No receipt found for this payment'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $receipt
        ]);
    }

    /**
     * Generate receipt for payment
     */
    public function generate(int $paymentId): JsonResponse
    {
        $payment = Payment::with('resident')->findOrFail($paymentId);
        
        $receipt = $this->receiptService->generateReceiptForPayment($payment);

        if (!$receipt) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate receipt. Payment must be in Paid status.'
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Receipt generated successfully',
            'data' => $receipt
        ]);
    }

    /**
     * View receipt PDF
     */
    public function view(Receipt $receipt): Response
    {
        $pdf = $this->receiptService->generatePDF($receipt);
        
        return $pdf->stream("receipt-{$receipt->receipt_number}.pdf");
    }

    /**
     * Download receipt PDF
     */
    public function download(Receipt $receipt): Response
    {
        $pdf = $this->receiptService->generatePDF($receipt);
        
        return $pdf->download("receipt-{$receipt->receipt_number}.pdf");
    }

    /**
     * Get all receipts with filtering
     */
    public function index(Request $request): JsonResponse
    {
        $query = Receipt::with(['payment.resident']);

        // Filter by receipt number
        if ($request->has('receipt_number')) {
            $query->where('receipt_number', 'like', '%' . $request->receipt_number . '%');
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('receipt_date', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('receipt_date', '<=', $request->to_date);
        }

        // Filter by resident
        if ($request->has('resident_id')) {
            $query->whereHas('payment', function($q) use ($request) {
                $q->where('resident_id', $request->resident_id);
            });
        }

        $perPage = $request->get('per_page', 10);
        $receipts = $query->orderBy('receipt_date', 'desc')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $receipts
        ]);
    }

    /**
     * Generate receipts for all paid payments without receipts
     */
    public function generateMissing(): JsonResponse
    {
        $result = $this->receiptService->generateMissingReceipts();

        return response()->json([
            'status' => 'success',
            'message' => "Generated {$result['total']} receipts. {$result['errors']} failed.",
            'data' => $result
        ]);
    }
}

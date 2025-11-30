<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Resident;
use App\Services\GoogleSheetsPaymentService;
use App\Services\PaymentService;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $googleSheetsService;
    protected $paymentService;

    public function __construct(
        GoogleSheetsPaymentService $googleSheetsService,
        PaymentService $paymentService
    ) {
        $this->googleSheetsService = $googleSheetsService;
        $this->paymentService = $paymentService;
    }
    /**
     * Display a listing of payments with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $query = Payment::withResident();

        // Apply filters
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        if ($request->has('payment_month')) {
            $query->forMonth($request->payment_month);
        }

        if ($request->has('resident_id')) {
            $query->forResident($request->resident_id);
        }

        if ($request->has('house_number')) {
            $query->whereHas('resident', function($q) use ($request) {
                $q->where('house_number', $request->house_number);
            });
        }

        // Date range filter
        if ($request->has('date_from') && $request->has('date_to')) {
            $query->whereBetween('payment_date', [$request->date_from, $request->date_to]);
        }

        $payments = $query->orderBy('payment_date', 'desc')
                          ->paginate($request->get('per_page', 15));

        return response()->json([
            'status' => 'success',
            'data' => $payments,
            'message' => 'Payments retrieved successfully'
        ]);
    }

    /**
     * Store a newly created payment
     */
    public function store(StorePaymentRequest $request): JsonResponse
    {
        $result = $this->paymentService->createPayment($request->validated());

        if (!$result['success']) {
            return response()->json([
                'status' => 'error',
                'message' => $result['message'],
                'data' => $result['data']
            ], $result['data'] ? 409 : 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => $result['message'],
            'data' => $result['data']
        ], 201);
    }

    /**
     * Display the specified payment
     */
    public function show(Payment $payment): JsonResponse
    {
        $payment->load('resident');

        return response()->json([
            'status' => 'success',
            'data' => $payment
        ]);
    }

    /**
     * Update the specified payment
     */
    public function update(UpdatePaymentRequest $request, Payment $payment): JsonResponse
    {
        $result = $this->paymentService->updatePayment($payment, $request->validated());

        if (!$result['success']) {
            return response()->json([
                'status' => 'error',
                'message' => $result['message']
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => $result['message'],
            'data' => $result['data']
        ]);
    }

    /**
     * Remove the specified payment
     */
    public function destroy(Payment $payment): JsonResponse
    {
        $payment->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Payment deleted successfully'
        ]);
    }

    /**
     * Get payments by status
     */
    public function getByStatus(string $status): JsonResponse
    {
        $payments = Payment::withResident()
                          ->byStatus($status)
                          ->orderBy('payment_date', 'desc')
                          ->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $payments,
            'message' => "Retrieved {$status} payments"
        ]);
    }

    /**
     * Get payments by month
     */
    public function getByMonth(string $month): JsonResponse
    {
        $payments = Payment::withResident()
                          ->forMonth($month)
                          ->orderBy('payment_date', 'desc')
                          ->get();

        return response()->json([
            'status' => 'success',
            'data' => $payments,
            'message' => "Retrieved payments for {$month}"
        ]);
    }

    /**
     * Get overdue payments
     */
    public function getOverdue(): JsonResponse
    {
        $overduePayments = Payment::withResident()
                                 ->overdue()
                                 ->orderBy('payment_date', 'asc')
                                 ->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $overduePayments,
            'message' => 'Retrieved overdue payments'
        ]);
    }

    /**
     * Get payment summary statistics
     */
    public function getSummary(Request $request): JsonResponse
    {
        $month = $request->get('month', date('Y-m'));
        
        try {
            $summary = $this->paymentService->getPaymentStatistics($month);

            return response()->json([
                'status' => 'success',
                'data' => $summary,
                'message' => "Payment summary for {$month}"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get payment summary: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process partial payment
     */
    public function processPartialPayment(Request $request, Payment $payment): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'nullable|date|before_or_equal:today',
            'payment_method' => 'nullable|in:Cash,UPI,Bank Transfer',
            'transaction_id' => 'nullable|string|max:255|unique:payments,transaction_id',
            'remarks' => 'nullable|string|max:1000'
        ]);

        $result = $this->paymentService->processPartialPayment(
            $payment, 
            $validated['amount'], 
            $validated
        );

        if (!$result['success']) {
            return response()->json([
                'status' => 'error',
                'message' => $result['message']
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => $result['message'],
            'data' => $result['data']
        ]);
    }

    /**
     * Bulk create monthly payments
     */
    public function bulkCreateMonthlyPayments(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_month' => 'required|string|regex:/^\d{4}-\d{2}$/',
            'default_amount' => 'required|numeric|min:0'
        ]);

        $result = $this->paymentService->bulkCreateMonthlyPayments(
            $validated['payment_month'],
            $validated['default_amount']
        );

        return response()->json([
            'status' => $result['success'] ? 'success' : 'error',
            'message' => $result['message'],
            'data' => $result['data']
        ], $result['success'] ? 200 : 500);
    }

    /**
     * Get defaulters list
     */
    public function getDefaultersList(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'months_back' => 'nullable|integer|min:1|max:12'
        ]);

        $monthsBack = $validated['months_back'] ?? 3;

        try {
            $defaulters = $this->paymentService->getDefaultersList($monthsBack);

            return response()->json([
                'status' => 'success',
                'data' => $defaulters,
                'message' => "Defaulters list for last {$monthsBack} months"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get defaulters list: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update overdue payments
     */
    public function updateOverduePayments(): JsonResponse
    {
        $result = $this->paymentService->updateOverduePayments();

        return response()->json([
            'status' => $result['success'] ? 'success' : 'error',
            'message' => $result['message'],
            'data' => $result['data']
        ], $result['success'] ? 200 : 500);
    }

    /**
     * Generate payment report
     */
    public function generateReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'month' => 'nullable|string|regex:/^\d{4}-\d{2}$/',
            'status' => 'nullable|in:Pending,Paid,Partial,Overdue',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'resident_search' => 'nullable|string|max:255'
        ]);

        try {
            $report = $this->paymentService->generatePaymentReport($validated);

            return response()->json([
                'status' => 'success',
                'data' => $report,
                'message' => 'Payment report generated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment analytics
     */
    public function getAnalytics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'months' => 'nullable|integer|min:1|max:12'
        ]);

        try {
            $analytics = $this->paymentService->getPaymentAnalytics($validated);

            return response()->json([
                'status' => 'success',
                'data' => $analytics,
                'message' => 'Payment analytics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get analytics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Advanced search for payments
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|array',
            'status.*' => 'in:Pending,Paid,Partial,Overdue',
            'payment_method' => 'nullable|array',
            'payment_method.*' => 'in:Cash,UPI,Bank Transfer',
            'amount_min' => 'nullable|numeric|min:0',
            'amount_max' => 'nullable|numeric|min:0',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'month' => 'nullable|string|regex:/^\d{4}-\d{2}$/',
            'sort_by' => 'nullable|in:payment_date,amount_due,amount_paid,created_at',
            'sort_order' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        try {
            $query = Payment::withResident();

            // Apply search filter
            if (!empty($validated['search'])) {
                $query->searchResident($validated['search']);
            }

            // Apply status filter
            if (!empty($validated['status'])) {
                $query->whereIn('status', $validated['status']);
            }

            // Apply payment method filter
            if (!empty($validated['payment_method'])) {
                $query->whereIn('payment_method', $validated['payment_method']);
            }

            // Apply amount range filter
            if (isset($validated['amount_min']) || isset($validated['amount_max'])) {
                if (isset($validated['amount_min'])) {
                    $query->where('amount_due', '>=', $validated['amount_min']);
                }
                if (isset($validated['amount_max'])) {
                    $query->where('amount_due', '<=', $validated['amount_max']);
                }
            }

            // Apply date range filter
            if (isset($validated['date_from']) && isset($validated['date_to'])) {
                $query->dateRange($validated['date_from'], $validated['date_to']);
            }

            // Apply month filter
            if (!empty($validated['month'])) {
                $query->forMonth($validated['month']);
            }

            // Apply sorting
            $sortBy = $validated['sort_by'] ?? 'payment_date';
            $sortOrder = $validated['sort_order'] ?? 'desc';
            $query->orderBy($sortBy, $sortOrder);

            $perPage = $validated['per_page'] ?? 15;
            $payments = $query->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'data' => $payments,
                'message' => 'Search completed successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Search failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment for specific resident and month
     */
    public function getResidentPayment(int $residentId, string $month): JsonResponse
    {
        $payment = Payment::withResident()
                         ->forResident($residentId)
                         ->forMonth($month)
                         ->first();

        if (!$payment) {
            return response()->json([
                'status' => 'error',
                'message' => 'Payment not found for this resident and month'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $payment
        ]);
    }

    /**
     * Bulk update payments from Google Sheets sync
     */
    public function bulkSync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payments' => 'required|array',
            'payments.*.house_number' => 'required|string',
            'payments.*.floor' => 'required|string',
            'payments.*.payment_month' => 'required|string',
            'payments.*.amount_due' => 'required|numeric|min:0',
            'payments.*.amount_paid' => 'required|numeric|min:0',
            'payments.*.payment_date' => 'nullable|date',
            'payments.*.payment_method' => 'nullable|string',
            'payments.*.transaction_id' => 'nullable|string',
            'payments.*.status' => 'nullable|string',
            'payments.*.remarks' => 'nullable|string',
            'payments.*.sheet_row_id' => 'nullable|integer'
        ]);

        $results = [
            'created' => 0,
            'updated' => 0,
            'errors' => []
        ];

        foreach ($validated['payments'] as $paymentData) {
            try {
                // Find resident by house number and floor
                $resident = Resident::where('house_number', $paymentData['house_number'])
                                  ->where('floor', $paymentData['floor'])
                                  ->first();

                if (!$resident) {
                    $results['errors'][] = "Resident not found: {$paymentData['house_number']} - {$paymentData['floor']}";
                    continue;
                }

                $paymentData['resident_id'] = $resident->id;
                unset($paymentData['house_number'], $paymentData['floor']);

                // Check if payment exists
                $existingPayment = Payment::where('resident_id', $resident->id)
                                         ->where('payment_month', $paymentData['payment_month'])
                                         ->first();

                if ($existingPayment) {
                    $existingPayment->update($paymentData);
                    $results['updated']++;
                } else {
                    Payment::create($paymentData);
                    $results['created']++;
                }

            } catch (\Exception $e) {
                $results['errors'][] = "Error processing payment: " . $e->getMessage();
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Bulk sync completed',
            'data' => $results
        ]);
    }

    /**
     * Sync payments from database to Google Sheets
     */
    public function syncToSheets(): JsonResponse
    {
        try {
            $result = $this->googleSheetsService->syncDatabaseToSheets();
            
            return response()->json([
                'status' => 'success',
                'message' => $result['message'],
                'data' => [
                    'synced_count' => $result['synced_count']
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to sync to Google Sheets',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync payments from Google Sheets to database
     */
    public function syncFromSheets(): JsonResponse
    {
        try {
            $result = $this->googleSheetsService->syncSheetsToDatabase();
            
            return response()->json([
                'status' => 'success',
                'message' => $result['message'],
                'data' => [
                    'processed_count' => $result['processed_count'],
                    'errors' => $result['errors'] ?? []
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to sync from Google Sheets',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bidirectional sync between database and Google Sheets
     */
    public function bidirectionalSync(): JsonResponse
    {
        try {
            $result = $this->googleSheetsService->bidirectionalSync();
            
            return response()->json([
                'status' => 'success',
                'message' => $result['message'],
                'data' => [
                    'sheets_to_db' => $result['sheets_to_db'],
                    'db_to_sheets' => $result['db_to_sheets']
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bidirectional sync failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Google Sheets sync status
     */
    public function getSyncStatus(): JsonResponse
    {
        try {
            $status = $this->googleSheetsService->getSyncStatus();
            
            return response()->json([
                'status' => 'success',
                'data' => $status
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get sync status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test Google Sheets connection
     */
    public function testSheetsConnection(): JsonResponse
    {
        try {
            $result = $this->googleSheetsService->testConnection();
            
            return response()->json([
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message'],
                'data' => $result['success'] ? [
                    'spreadsheet_title' => $result['spreadsheet_title']
                ] : null
            ], $result['success'] ? 200 : 500);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Connection test failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
/ /   U p d a t e d   v i a   C I / C D   -   B a c k e n d   T e s t  
 
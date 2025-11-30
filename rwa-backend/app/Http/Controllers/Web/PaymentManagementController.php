<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Resident;
use App\Models\MaintenanceCharge;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Carbon\Carbon;

class PaymentManagementController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Display the payment management dashboard
     */
    public function index(Request $request): View
    {
        $month = $request->get('month', Carbon::now()->format('Y-m'));
        $status = $request->get('status');
        $search = $request->get('search');
        $perPage = $request->get('per_page', 25);
        
        // Handle "All" option and validate per_page values
        $allowedPerPage = [10, 25, 50, 100, 200, 500, 1000, 9999];
        if (!in_array((int)$perPage, $allowedPerPage)) {
            $perPage = 25; // Default fallback
        }
        $perPage = (int)$perPage;

        // Get summary statistics
        $stats = $this->getDashboardStats($month);

        // Build query for payments
        $query = Payment::with('resident')
            ->where('payment_month', $month);

        // Apply filters
        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->whereHas('resident', function($q) use ($search) {
                $q->where('owner_name', 'LIKE', "%{$search}%")
                  ->orWhere('flat_number', 'LIKE', "%{$search}%")
                  ->orWhere('house_number', 'LIKE', "%{$search}%")
                  ->orWhere('contact_number', 'LIKE', "%{$search}%");
            });
        }

        // Handle pagination or "All" option
        if ($perPage == 9999) {
            // For "All" option, get all results but still use pagination for consistent interface
            $totalCount = $query->count();
            $payments = $query->orderBy('created_at', 'desc')->paginate($totalCount, ['*'], 'page', 1);
        } else {
            $payments = $query->orderBy('created_at', 'desc')->paginate($perPage);
        }

        // Get all available months for filter
        $availableMonths = Payment::select('payment_month')
            ->distinct()
            ->orderBy('payment_month', 'desc')
            ->pluck('payment_month')
            ->values();

        return view('payment-management.index', compact(
            'payments', 
            'stats', 
            'month', 
            'status', 
            'search', 
            'perPage',
            'availableMonths'
        ));
    }

    /**
     * Show payment details
     */
    public function show(Payment $payment): View
    {
        $payment->load('resident');
        
        // Get payment history for this resident
        $paymentHistory = Payment::where('resident_id', $payment->resident_id)
            ->orderBy('payment_month', 'desc')
            ->limit(10)
            ->get();

        return view('payment-management.show', compact('payment', 'paymentHistory'));
    }

    /**
     * Show edit form for a payment
     */
    public function edit(Payment $payment)
    {
        $payment->load('resident');
        return view('payment-management.edit', compact('payment'));
    }

    /**
     * Update a payment
     */
    public function update(Request $request, Payment $payment)
    {
        $validator = \Validator::make($request->all(), [
            'remarks' => 'nullable|string|max:500',
            'status' => 'required|in:Paid,Pending,Overdue,Partial',
            'amount_due' => 'required|numeric|min:0',
            'amount_paid' => 'required|numeric|min:0',
            'transaction_id' => 'nullable|string|max:255',
            'payment_method' => 'nullable|in:UPI,Cash',
        ]);

        // Handle AJAX requests
        if ($request->ajax() || $request->wantsJson()) {
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
        } else {
            $validator->validate();
        }

        $data = $validator->validated();

        $payment->remarks = $data['remarks'] ?? null;
        $payment->status = $data['status'];
        $payment->amount_due = $data['amount_due'];
        $payment->amount_paid = $data['amount_paid'];
        $payment->transaction_id = $data['transaction_id'] ?? null;
        if (isset($data['payment_method'])) {
            $payment->payment_method = $data['payment_method'];
        }

        // If marked paid and payment_date is null, set payment_date to now
        if ($payment->status === 'Paid' && !$payment->payment_date) {
            $payment->payment_date = \Carbon\Carbon::now();
        }

        // Check if amount_due changed - this might indicate maintenance change
        $amountDueChanged = $payment->isDirty('amount_due');
        $amountPaidChanged = $payment->isDirty('amount_paid');
        $oldAmountDue = $payment->getOriginal('amount_due');
        $newAmountDue = $payment->amount_due;

        $payment->save();

        // Recalculate carry-forwards for future months if amounts changed
        if ($amountPaidChanged || $amountDueChanged) {
            $this->recalculateCarryForwards($payment->resident_id, $payment->payment_month);
            
            // If amount_due changed significantly, it might be a maintenance change
            // Recalculate the base maintenance for this resident
            if ($amountDueChanged) {
                $this->updateResidentMaintenanceFromPayment($payment, $oldAmountDue, $newAmountDue);
            }
        }

        // Return JSON for AJAX requests
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Payment updated successfully.',
                'payment' => $payment->load('resident')
            ]);
        }

        // Traditional redirect for form submissions
        return redirect()->route('payment-management.index', ['month' => $payment->payment_month])
            ->with('success', 'Payment updated successfully.');
    }

    /**
     * Show residents with unpaid amounts
     */
    public function unpaidResidents(Request $request): View
    {
        $month = $request->get('month', Carbon::now()->format('Y-m'));
        $search = $request->get('search');
        $perPage = $request->get('per_page', 25);

        // Get residents with unpaid amounts for the selected month
        $query = Resident::select([
                'residents.*',
                'payments.amount_due',
                'payments.amount_paid',
                'payments.payment_month',
                'payments.status as payment_status'
            ])
            ->join('payments', 'residents.id', '=', 'payments.resident_id')
            ->where('residents.status', 'active')
            ->where('payments.payment_month', $month)
            ->whereRaw('payments.amount_paid < payments.amount_due'); // Only unpaid amounts

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('residents.owner_name', 'LIKE', "%{$search}%")
                  ->orWhere('residents.house_number', 'LIKE', "%{$search}%")
                  ->orWhere('residents.contact_number', 'LIKE', "%{$search}%");
            });
        }

        $unpaidResidents = $query->orderBy('residents.house_number')
            ->orderBy('residents.floor')
            ->paginate($perPage);

        // Calculate total unpaid amount
        $totalUnpaidAmount = Resident::join('payments', 'residents.id', '=', 'payments.resident_id')
            ->where('residents.status', 'active')
            ->where('payments.payment_month', $month)
            ->whereRaw('payments.amount_paid < payments.amount_due')
            ->sum(\DB::raw('payments.amount_due - payments.amount_paid'));

        // Get all available months for filter
        $availableMonths = Payment::select('payment_month')
            ->distinct()
            ->orderBy('payment_month', 'desc')
            ->pluck('payment_month')
            ->values();

        return view('payment-management.unpaid', compact(
            'unpaidResidents',
            'month',
            'search',
            'perPage',
            'availableMonths',
            'totalUnpaidAmount'
        ));
    }

    /**
     * Show defaulters list
     */
    public function defaulters(): View
    {
        $defaulters = $this->paymentService->getDefaultersList();
        
        return view('payment-management.defaulters', compact('defaulters'));
    }

    /**
     * Show payment analytics
     */
    public function analytics(Request $request): View
    {
        $startMonth = $request->get('start_month', Carbon::now()->subMonths(5)->format('Y-m'));
        $endMonth = $request->get('end_month', Carbon::now()->format('Y-m'));

        // Get analytics data
        $analytics = $this->paymentService->getPaymentAnalytics([
            'start_month' => $startMonth,
            'end_month' => $endMonth
        ]);

        // Get monthly trends
        $monthlyTrends = $this->getMonthlyTrends($startMonth, $endMonth);

        return view('payment-management.analytics', compact(
            'analytics',
            'monthlyTrends',
            'startMonth',
            'endMonth'
        ));
    }

    /**
     * Export payments data
     */
    public function export(Request $request)
    {
        $month = $request->get('month', Carbon::now()->format('Y-m'));
        $status = $request->get('status');

        $query = Payment::with('resident')
            ->where('payment_month', $month);

        if ($status) {
            $query->where('status', $status);
        }

        $payments = $query->get();

        $csvData = [];
        $csvData[] = [
            'Flat Number',
            'Resident Name',
            'Contact Number',
            'Amount Due',
            'Amount Paid',
            'Payment Status',
            'Payment Date',
            'Payment Method',
            'Due Date',
            'Late Fee'
        ];

        foreach ($payments as $payment) {
            $csvData[] = [
                $payment->resident->flat_number,
                $payment->resident->owner_name,
                $payment->resident->contact_number,
                $payment->amount_due,
                $payment->amount_paid,
                ucfirst($payment->status),
                $payment->payment_date ? $payment->payment_date->format('Y-m-d') : 'N/A',
                $payment->payment_method ?: 'N/A',
                $payment->due_date->format('Y-m-d'),
                $payment->late_fee ?: '0.00'
            ];
        }

        $filename = "payments_export_{$month}.csv";
        
        $callback = function() use ($csvData) {
            $file = fopen('php://output', 'w');
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ]);
    }

    /**
     * Get dashboard statistics for a specific month
     */
    private function getDashboardStats(string $month): array
    {
        $totalResidents = Resident::where('status', 'active')->count();
        
        $paymentStats = Payment::where('payment_month', $month)
            ->selectRaw('
                status,
                COUNT(*) as count,
                SUM(amount_due) as total_due,
                SUM(amount_paid) as total_paid
            ')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $totalDue = $paymentStats->sum('total_due') ?: 0;
        $totalPaid = $paymentStats->sum('total_paid') ?: 0;
        $collectionRate = $totalDue > 0 ? round(($totalPaid / $totalDue) * 100, 2) : 0;

        return [
            'month' => $month,
            'total_residents' => $totalResidents,
            'total_due' => $totalDue,
            'total_paid' => $totalPaid,
            'total_pending' => $totalDue - $totalPaid,
            'collection_rate' => $collectionRate,
            'paid_count' => $paymentStats->get('Paid', (object)['count' => 0])->count,
            'pending_count' => $paymentStats->get('Pending', (object)['count' => 0])->count,
            'overdue_count' => $paymentStats->get('Overdue', (object)['count' => 0])->count,
            'partial_count' => $paymentStats->get('Partial', (object)['count' => 0])->count,
            'unpaid_residents' => $totalResidents - $paymentStats->sum('count'),
        ];
    }

    /**
     * Get monthly trends data
     */
    private function getMonthlyTrends(string $startMonth, string $endMonth): array
    {
        // Get distinct payment months in the range
        $paymentMonths = Payment::whereBetween('payment_month', [$startMonth, $endMonth])
            ->select('payment_month')
            ->distinct()
            ->orderBy('payment_month')
            ->pluck('payment_month');

        $trends = [];
        
        foreach ($paymentMonths as $month) {
            $paymentStats = Payment::where('payment_month', $month)
                ->selectRaw('
                    SUM(amount_due) as total_due,
                    SUM(amount_paid) as total_paid,
                    COUNT(*) as payment_count
                ')
                ->first();

            $totalDue = $paymentStats->total_due ?: 0;
            $totalPaid = $paymentStats->total_paid ?: 0;
            $collectionRate = $totalDue > 0 ? round(($totalPaid / $totalDue) * 100, 2) : 0;

            $trends[] = [
                'month' => $month,
                'month_name' => Carbon::parse($month . '-01')->format('M Y'),
                'total_due' => $totalDue,
                'total_paid' => $totalPaid,
                'collection_rate' => $collectionRate,
                'payment_count' => $paymentStats->payment_count ?: 0,
            ];
        }

        return $trends;
    }

    /**
     * Recalculate carry-forwards for future months when a payment is updated
     */
    private function recalculateCarryForwards(int $residentId, string $updatedMonth): void
    {
        // Get all payments for this resident after the updated month
        $futurePayments = Payment::where('resident_id', $residentId)
            ->where('payment_month', '>', $updatedMonth)
            ->orderBy('payment_month')
            ->get();

        foreach ($futurePayments as $futurePayment) {
            // Calculate carry-forward up to this payment's month
            $carryForward = $this->calculateCarryForward($residentId, $futurePayment->payment_month);
            
            // Get base maintenance amount
            $resident = $futurePayment->resident;
            $baseAmount = $resident->monthly_maintenance ?? 0;
            
            // New total amount due = base + carry-forward
            $newAmountDue = $baseAmount + $carryForward;
            
            // Update the payment if amount_due changed
            if ($futurePayment->amount_due != $newAmountDue) {
                $futurePayment->amount_due = $newAmountDue;
                
                // Update remarks to reflect carry-forward
                $remarks = [];
                if ($carryForward > 0) {
                    $remarks[] = "Includes carry-forward of ₹" . number_format($carryForward, 2) . " from previous months";
                }
                $remarks[] = "Base maintenance: ₹" . number_format($baseAmount, 2);
                $futurePayment->remarks = implode('. ', $remarks);
                
                // Update payment status based on new amount_due vs amount_paid
                $futurePayment->updateStatus();
                $futurePayment->save();
            }
        }
    }

    /**
     * Calculate carry-forward amount from previous month's unpaid balance only
     */
    private function calculateCarryForward(int $residentId, string $currentMonth): float
    {
        // Get the previous month in YYYY-MM format
        $previousMonth = \Carbon\Carbon::parse($currentMonth . '-01')->subMonth()->format('Y-m');
        
        // Get the previous month's payment record
        $previousPayment = Payment::where('resident_id', $residentId)
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
     * Update resident maintenance if payment amount_due changed significantly
     */
    private function updateResidentMaintenanceFromPayment(Payment $payment, float $oldAmountDue, float $newAmountDue): void
    {
        $resident = $payment->resident;
        
        // Calculate current carry-forward for this payment
        $previousMonth = \Carbon\Carbon::parse($payment->payment_month . '-01')->subMonth()->format('Y-m');
        $previousPayment = Payment::where('resident_id', $resident->id)
            ->where('payment_month', $previousMonth)
            ->first();
            
        $carryForward = 0;
        if ($previousPayment) {
            $carryForward = max(0, $previousPayment->amount_due - $previousPayment->amount_paid);
        }
        
        // Calculate what the base maintenance should be
        $newBaseMaintenance = $newAmountDue - $carryForward;
        $oldBaseMaintenance = $oldAmountDue - $carryForward;
        
        // Only update if the change is significant (more than ₹10) and positive
        if ($newBaseMaintenance > 0 && abs($newBaseMaintenance - $oldBaseMaintenance) >= 10) {
            
            // Update resident's monthly maintenance
            $resident->update(['monthly_maintenance' => $newBaseMaintenance]);
            
            // Log this change for audit trail
            \Log::info('Maintenance updated via payment edit', [
                'resident_id' => $resident->id,
                'resident_name' => $resident->owner_name,
                'old_maintenance' => $oldBaseMaintenance,
                'new_maintenance' => $newBaseMaintenance,
                'payment_month' => $payment->payment_month,
                'updated_by' => auth()->user()->id ?? 'system'
            ]);
            
            // Recalculate future months with new maintenance amount
            $this->recalculateFutureMonthsFromMaintenance($resident->id, $payment->payment_month, $newBaseMaintenance);
        }
    }

    /**
     * Recalculate future months when maintenance amount changes
     */
    private function recalculateFutureMonthsFromMaintenance(int $residentId, string $fromMonth, float $newMaintenance): void
    {
        // Get next month after the current one
        $nextMonth = \Carbon\Carbon::parse($fromMonth . '-01')->addMonth()->format('Y-m');
        
        // Get all future payments
        $futurePayments = Payment::where('resident_id', $residentId)
            ->where('payment_month', '>=', $nextMonth)
            ->orderBy('payment_month')
            ->get();
            
        if ($futurePayments->isEmpty()) {
            return;
        }
        
        foreach ($futurePayments as $futurePayment) {
            // Calculate carry-forward from previous month
            $previousMonth = \Carbon\Carbon::parse($futurePayment->payment_month . '-01')->subMonth()->format('Y-m');
            $previousPayment = Payment::where('resident_id', $residentId)
                ->where('payment_month', $previousMonth)
                ->first();
                
            $carryForward = 0;
            if ($previousPayment) {
                $carryForward = max(0, $previousPayment->amount_due - $previousPayment->amount_paid);
            }
            
            // Calculate new amount due with updated maintenance
            $newAmountDue = $newMaintenance + $carryForward;
            
            // Update the payment
            $futurePayment->update([
                'amount_due' => $newAmountDue,
                'remarks' => $this->generatePaymentRemarks($newMaintenance, $carryForward)
            ]);
            
            // Update status if payment amount makes it fully paid
            if ($futurePayment->amount_paid >= $newAmountDue) {
                $futurePayment->update(['status' => 'Paid']);
            } elseif ($futurePayment->amount_paid > 0) {
                $futurePayment->update(['status' => 'Partial']);
            }
        }
    }

    /**
     * Generate payment remarks
     */
    private function generatePaymentRemarks(float $baseMaintenance, float $carryForward): string
    {
        $remarks = [];
        if ($carryForward > 0) {
            $remarks[] = "Includes carry-forward of ₹" . number_format($carryForward, 2) . " from previous month";
        }
        $remarks[] = "Base maintenance: ₹" . number_format($baseMaintenance, 2);
        
        return implode('. ', $remarks);
    }

    /**
     * Handle automatic recalculation when payment is updated (called from model events)
     */
    public function handlePaymentUpdateRecalculation(Payment $payment): void
    {
        $originalAmountDue = $payment->getOriginal('amount_due');
        $originalAmountPaid = $payment->getOriginal('amount_paid');
        
        // Check if amount_due changed significantly (maintenance change)
        if ($payment->isDirty('amount_due') && abs($payment->amount_due - $originalAmountDue) >= 10) {
            $this->updateResidentMaintenanceFromPayment($payment, $originalAmountDue, $payment->amount_due);
        }
        
        // Check if amount_paid changed (payment made/updated)
        if ($payment->isDirty('amount_paid')) {
            // Recalculate carry-forwards for future months
            $this->recalculateCarryForwards($payment->resident_id, $payment->payment_month);
        }
    }

    /**
     * Get carry-forward breakdown for a payment (only previous month's balance)
     */
    public function getCarryForwardBreakdown(Payment $payment): JsonResponse
    {
        // Get the previous month in YYYY-MM format
        $previousMonth = \Carbon\Carbon::parse($payment->payment_month . '-01')->subMonth()->format('Y-m');
        
        // Get the previous month's payment record
        $previousPayment = Payment::where('resident_id', $payment->resident_id)
            ->where('payment_month', $previousMonth)
            ->first();

        $breakdown = [];
        $totalCarryForward = 0;

        if ($previousPayment) {
            $balance = $previousPayment->amount_due - $previousPayment->amount_paid;
            
            if ($balance > 0) {
                $breakdown[] = [
                    'month' => $previousPayment->payment_month,
                    'amount_due' => $previousPayment->amount_due,
                    'amount_paid' => $previousPayment->amount_paid,
                    'balance' => $balance,
                    'status' => $previousPayment->status
                ];
                
                $totalCarryForward = $balance;
            }
        }

        // Get base maintenance for this resident
        $baseMaintenance = $payment->resident->monthly_maintenance ?? 0;
        $calculatedTotalDue = $baseMaintenance + $totalCarryForward;

        return response()->json([
            'success' => true,
            'payment_month' => $payment->payment_month,
            'resident_name' => $payment->resident->owner_name,
            'base_maintenance' => $baseMaintenance,
            'total_carryforward' => $totalCarryForward,
            'calculated_total_due' => $calculatedTotalDue,
            'current_amount_due' => $payment->amount_due,
            'breakdown' => $breakdown,
            'has_carryforward' => $totalCarryForward > 0,
            'breakdown_count' => count($breakdown)
        ]);
    }
}
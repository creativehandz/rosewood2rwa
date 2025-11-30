<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Resident;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    /**
     * Create a new payment with validation and business logic
     */
    public function createPayment(array $data): array
    {
        try {
            DB::beginTransaction();

            // Validate resident exists
            $resident = Resident::findOrFail($data['resident_id']);

            // Check for duplicate payment
            $existingPayment = Payment::where('resident_id', $data['resident_id'])
                                    ->where('payment_month', $data['payment_month'])
                                    ->first();

            if ($existingPayment) {
                return [
                    'success' => false,
                    'message' => 'Payment already exists for this resident and month',
                    'data' => $existingPayment
                ];
            }

            // Auto-calculate status if not provided
            if (!isset($data['status'])) {
                $data['status'] = $this->calculatePaymentStatus(
                    $data['amount_due'], 
                    $data['amount_paid'] ?? 0
                );
            }

            // Set payment date if not provided
            if (!isset($data['payment_date']) && ($data['amount_paid'] ?? 0) > 0) {
                $data['payment_date'] = now();
            }

            $payment = Payment::create($data);
            $payment->load('resident');

            DB::commit();

            return [
                'success' => true,
                'message' => 'Payment created successfully',
                'data' => $payment
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment creation failed: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to create payment: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Update payment with business rule validation
     */
    public function updatePayment(Payment $payment, array $data): array
    {
        try {
            DB::beginTransaction();

            $originalAmountPaid = $payment->amount_paid;
            $originalStatus = $payment->status;

            $payment->update($data);

            // Auto-update status if amounts changed
            if (isset($data['amount_due']) || isset($data['amount_paid'])) {
                $payment->updateStatusByRules();
            }

            // Set payment date if payment was made
            if (isset($data['amount_paid']) && $data['amount_paid'] > $originalAmountPaid && !$payment->payment_date) {
                $payment->payment_date = now();
                $payment->save();
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Payment updated successfully',
                'data' => $payment->fresh('resident')
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment update failed: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to update payment: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Process partial payment
     */
    public function processPartialPayment(Payment $payment, float $amount, array $details = []): array
    {
        try {
            if ($amount <= 0) {
                return [
                    'success' => false,
                    'message' => 'Payment amount must be greater than zero',
                    'data' => null
                ];
            }

            $newTotalPaid = $payment->amount_paid + $amount;
            
            if ($newTotalPaid > $payment->amount_due) {
                return [
                    'success' => false,
                    'message' => 'Payment amount exceeds the due amount',
                    'data' => null
                ];
            }

            DB::beginTransaction();

            $payment->amount_paid = $newTotalPaid;
            
            if (!empty($details)) {
                $payment->payment_date = $details['payment_date'] ?? now();
                $payment->payment_method = $details['payment_method'] ?? $payment->payment_method;
                $payment->transaction_id = $details['transaction_id'] ?? $payment->transaction_id;
                $payment->remarks = $this->appendRemark($payment->remarks, $details['remarks'] ?? null);
            }

            $payment->updateStatusByRules();

            DB::commit();

            return [
                'success' => true,
                'message' => 'Partial payment processed successfully',
                'data' => $payment->fresh('resident')
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Partial payment processing failed: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to process partial payment: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Bulk create payments for a specific month
     */
    public function bulkCreateMonthlyPayments(string $paymentMonth, float $defaultAmount): array
    {
        try {
            DB::beginTransaction();

            $residents = Resident::where('current_state', 'Occupied')->get();
            $created = 0;
            $skipped = 0;
            $errors = [];

            foreach ($residents as $resident) {
                // Check if payment already exists
                $existingPayment = Payment::where('resident_id', $resident->id)
                                         ->where('payment_month', $paymentMonth)
                                         ->first();

                if ($existingPayment) {
                    $skipped++;
                    continue;
                }

                try {
                    Payment::create([
                        'resident_id' => $resident->id,
                        'payment_month' => $paymentMonth,
                        'amount_due' => $defaultAmount,
                        'amount_paid' => 0,
                        'status' => 'Pending'
                    ]);
                    $created++;
                } catch (\Exception $e) {
                    $errors[] = "Failed to create payment for {$resident->house_number}: " . $e->getMessage();
                }
            }

            DB::commit();

            return [
                'success' => true,
                'message' => "Bulk payment creation completed. Created: {$created}, Skipped: {$skipped}",
                'data' => [
                    'created' => $created,
                    'skipped' => $skipped,
                    'errors' => $errors
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk payment creation failed: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Bulk payment creation failed: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Calculate payment statistics for a given month
     */
    public function getPaymentStatistics(string $month = null): array
    {
        $month = $month ?? now()->format('Y-m');

        $payments = Payment::forMonth($month)->with('resident')->get();

        $stats = [
            'month' => $month,
            'total_units' => $payments->count(),
            'total_amount_due' => $payments->sum('amount_due'),
            'total_amount_paid' => $payments->sum('amount_paid'),
            'total_balance_due' => $payments->sum(function($payment) {
                return $payment->amount_due - $payment->amount_paid;
            }),
            'collection_percentage' => 0,
            'payment_counts' => [
                'paid' => $payments->where('status', 'Paid')->count(),
                'partial' => $payments->where('status', 'Partial')->count(),
                'pending' => $payments->where('status', 'Pending')->count(),
                'overdue' => $payments->where('status', 'Overdue')->count()
            ],
            'payment_methods' => [
                'cash' => $payments->where('payment_method', 'Cash')->sum('amount_paid'),
                'upi' => $payments->where('payment_method', 'UPI')->sum('amount_paid'),
                'bank_transfer' => $payments->where('payment_method', 'Bank Transfer')->sum('amount_paid')
            ],
            'late_fees_applicable' => 0
        ];

        // Calculate collection percentage
        if ($stats['total_amount_due'] > 0) {
            $stats['collection_percentage'] = round(
                ($stats['total_amount_paid'] / $stats['total_amount_due']) * 100, 
                2
            );
        }

        // Calculate total late fees
        $stats['late_fees_applicable'] = $payments->sum(function($payment) {
            return $payment->calculateLateFee();
        });

        return $stats;
    }

    /**
     * Get defaulter list with outstanding amounts
     */
    public function getDefaultersList(int $monthsBack = 3): Collection
    {
        $cutoffDate = now()->subMonths($monthsBack)->format('Y-m');

        return Payment::with('resident')
                     ->where('payment_month', '<=', $cutoffDate)
                     ->where('status', '!=', 'Paid')
                     ->orderBy('payment_month', 'asc')
                     ->get()
                     ->groupBy('resident_id')
                     ->map(function ($payments) {
                         $resident = $payments->first()->resident;
                         $totalDue = $payments->sum('amount_due');
                         $totalPaid = $payments->sum('amount_paid');
                         $totalBalance = $totalDue - $totalPaid;
                         
                         return [
                             'resident' => $resident,
                             'total_due' => $totalDue,
                             'total_paid' => $totalPaid,
                             'total_balance' => $totalBalance,
                             'overdue_months' => $payments->count(),
                             'oldest_due_month' => $payments->min('payment_month'),
                             'latest_due_month' => $payments->max('payment_month'),
                             'payments' => $payments
                         ];
                     })
                     ->sortByDesc('total_balance')
                     ->values();
    }

    /**
     * Auto-update overdue payments
     */
    public function updateOverduePayments(): array
    {
        try {
            $currentMonth = now()->format('Y-m');
            $lastMonth = now()->subMonth()->format('Y-m');

            // Update payments that should be marked as overdue
            $overdueCount = Payment::where('payment_month', '<=', $lastMonth)
                                  ->where('status', 'Pending')
                                  ->whereRaw('amount_paid < amount_due')
                                  ->update(['status' => 'Overdue']);

            return [
                'success' => true,
                'message' => "Updated {$overdueCount} payments to overdue status",
                'data' => ['updated_count' => $overdueCount]
            ];

        } catch (\Exception $e) {
            Log::error('Failed to update overdue payments: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to update overdue payments: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Generate payment report for export
     */
    public function generatePaymentReport(array $filters = []): array
    {
        $query = Payment::with('resident');

        // Apply filters
        if (isset($filters['month'])) {
            $query->forMonth($filters['month']);
        }

        if (isset($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (isset($filters['date_from']) && isset($filters['date_to'])) {
            $query->whereBetween('payment_date', [$filters['date_from'], $filters['date_to']]);
        }

        if (isset($filters['resident_search'])) {
            $query->searchResident($filters['resident_search']);
        }

        $payments = $query->orderBy('payment_month', 'desc')
                         ->orderBy('house_number', 'asc')
                         ->get();

        return [
            'payments' => $payments->map(function($payment) {
                return $payment->toExportArray();
            })->toArray(),
            'summary' => [
                'total_records' => $payments->count(),
                'total_amount_due' => $payments->sum('amount_due'),
                'total_amount_paid' => $payments->sum('amount_paid'),
                'total_balance_due' => $payments->sum(function($p) { 
                    return $p->amount_due - $p->amount_paid; 
                })
            ]
        ];
    }

    /**
     * Helper method to calculate payment status
     */
    private function calculatePaymentStatus(float $amountDue, float $amountPaid): string
    {
        if ($amountPaid >= $amountDue) {
            return 'Paid';
        } elseif ($amountPaid > 0) {
            return 'Partial';
        } else {
            return 'Pending';
        }
    }

    /**
     * Helper method to append remarks
     */
    private function appendRemark(?string $existingRemarks, ?string $newRemark): ?string
    {
        if (empty($newRemark)) {
            return $existingRemarks;
        }

        if (empty($existingRemarks)) {
            return $newRemark;
        }

        return $existingRemarks . ' | ' . $newRemark;
    }

    /**
     * Validate payment data
     */
    public function validatePaymentData(array $data): array
    {
        $errors = [];

        // Validate resident exists
        if (isset($data['resident_id'])) {
            $resident = Resident::find($data['resident_id']);
            if (!$resident) {
                $errors[] = 'Resident not found';
            }
        }

        // Validate payment month format
        if (isset($data['payment_month'])) {
            if (!preg_match('/^\d{4}-\d{2}$/', $data['payment_month'])) {
                $errors[] = 'Payment month must be in YYYY-MM format';
            }
        }

        // Validate amounts
        if (isset($data['amount_due']) && $data['amount_due'] < 0) {
            $errors[] = 'Amount due cannot be negative';
        }

        if (isset($data['amount_paid']) && $data['amount_paid'] < 0) {
            $errors[] = 'Amount paid cannot be negative';
        }

        if (isset($data['amount_due']) && isset($data['amount_paid'])) {
            if ($data['amount_paid'] > $data['amount_due']) {
                $errors[] = 'Amount paid cannot exceed amount due';
            }
        }

        // Validate payment method
        if (isset($data['payment_method'])) {
            $validMethods = ['Cash', 'UPI', 'Bank Transfer'];
            if (!in_array($data['payment_method'], $validMethods)) {
                $errors[] = 'Invalid payment method';
            }
        }

        // Validate status
        if (isset($data['status'])) {
            $validStatuses = ['Pending', 'Paid', 'Partial', 'Overdue'];
            if (!in_array($data['status'], $validStatuses)) {
                $errors[] = 'Invalid status';
            }
        }

        return $errors;
    }

    /**
     * Get payment analytics for dashboard
     */
    public function getPaymentAnalytics(array $options = []): array
    {
        $months = $options['months'] ?? 6;
        $analytics = [];

        for ($i = 0; $i < $months; $i++) {
            $month = now()->subMonths($i)->format('Y-m');
            $analytics[$month] = $this->getPaymentStatistics($month);
        }

        return array_reverse($analytics, true);
    }
}
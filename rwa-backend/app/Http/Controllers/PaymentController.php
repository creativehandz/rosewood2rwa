<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Resident;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Payment::with('resident');

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('payment_month')) {
            $query->where('payment_month', $request->payment_month);
        }

        if ($request->has('resident_id')) {
            $query->where('resident_id', $request->resident_id);
        }

        if ($request->has('date_from') && $request->has('date_to')) {
            $query->whereBetween('payment_date', [$request->date_from, $request->date_to]);
        }

        $payments = $query->orderBy('payment_date', 'desc')
                          ->paginate($request->get('per_page', 15));

        return response()->json([
            'status' => 'success',
            'data' => $payments
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'resident_id' => 'required|exists:residents,id',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'due_date' => 'required|date',
            'payment_month' => 'required|string|regex:/^\d{4}-\d{2}$/',
            'status' => 'required|in:paid,pending,overdue',
            'payment_method' => 'nullable|string|max:100',
            'transaction_reference' => 'nullable|string|max:255',
            'remarks' => 'nullable|string'
        ]);

        $payment = Payment::create($validated);
        $payment->load('resident');

        return response()->json([
            'status' => 'success',
            'message' => 'Payment recorded successfully',
            'data' => $payment
        ], 201);
    }

    /**
     * Display the specified resource.
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
     * Update the specified resource in storage.
     */
    public function update(Request $request, Payment $payment): JsonResponse
    {
        $validated = $request->validate([
            'resident_id' => 'required|exists:residents,id',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'due_date' => 'required|date',
            'payment_month' => 'required|string|regex:/^\d{4}-\d{2}$/',
            'status' => 'required|in:paid,pending,overdue',
            'payment_method' => 'nullable|string|max:100',
            'transaction_reference' => 'nullable|string|max:255',
            'remarks' => 'nullable|string'
        ]);

        $payment->update($validated);
        $payment->load('resident');

        return response()->json([
            'status' => 'success',
            'message' => 'Payment updated successfully',
            'data' => $payment
        ]);
    }

    /**
     * Remove the specified resource from storage.
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
        $payments = Payment::with('resident')
                          ->where('status', $status)
                          ->orderBy('payment_date', 'desc')
                          ->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $payments
        ]);
    }

    /**
     * Get payments by month
     */
    public function getByMonth(string $month): JsonResponse
    {
        $payments = Payment::with('resident')
                          ->where('payment_month', $month)
                          ->orderBy('payment_date', 'desc')
                          ->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $payments
        ]);
    }

    /**
     * Get overdue payments
     */
    public function getOverdue(): JsonResponse
    {
        $overduePayments = Payment::with('resident')
                                 ->where('status', 'pending')
                                 ->where('due_date', '<', now())
                                 ->orderBy('due_date', 'asc')
                                 ->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $overduePayments
        ]);
    }
}

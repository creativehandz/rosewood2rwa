<?php

namespace App\Http\Controllers;

use App\Models\Resident;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class ResidentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Resident::with(['payments' => function ($query) {
            $query->latest('payment_date')->limit(1);
        }]);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('payment_status')) {
            if ($request->payment_status === 'payers') {
                $query->whereHas('payments', function ($q) {
                    $q->where('status', 'paid');
                });
            } elseif ($request->payment_status === 'non_payers') {
                $query->whereDoesntHave('payments', function ($q) {
                    $q->where('status', 'paid');
                });
            }
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('flat_number', 'like', "%{$search}%")
                  ->orWhere('owner_name', 'like', "%{$search}%")
                  ->orWhere('contact_number', 'like', "%{$search}%");
            });
        }

        $residents = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'status' => 'success',
            'data' => $residents
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'flat_number' => 'required|string|unique:residents,flat_number',
            'owner_name' => 'required|string|max:255',
            'contact_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'monthly_maintenance' => 'required|numeric|min:0',
            'status' => 'nullable|in:active,inactive'
        ]);

        $resident = Resident::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Resident created successfully',
            'data' => $resident
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Resident $resident): JsonResponse
    {
        $resident->load(['payments' => function ($query) {
            $query->orderBy('payment_date', 'desc');
        }]);

        return response()->json([
            'status' => 'success',
            'data' => $resident
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Resident $resident): JsonResponse
    {
        $validated = $request->validate([
            'flat_number' => ['required', 'string', Rule::unique('residents')->ignore($resident->id)],
            'owner_name' => 'required|string|max:255',
            'contact_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'monthly_maintenance' => 'required|numeric|min:0',
            'status' => 'nullable|in:active,inactive'
        ]);

        $resident->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Resident updated successfully',
            'data' => $resident
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Resident $resident): JsonResponse
    {
        $resident->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Resident deleted successfully'
        ]);
    }

    /**
     * Get payments for a specific resident
     */
    public function getPayments(Resident $resident): JsonResponse
    {
        $payments = $resident->payments()
            ->orderBy('payment_date', 'desc')
            ->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $payments
        ]);
    }

    /**
     * Get residents who are payers
     */
    public function getPayers(): JsonResponse
    {
        $payers = Resident::whereHas('payments', function ($query) {
            $query->where('status', 'paid');
        })->with(['payments' => function ($query) {
            $query->latest('payment_date')->limit(1);
        }])->get();

        return response()->json([
            'status' => 'success',
            'data' => $payers
        ]);
    }

    /**
     * Get residents who are non-payers
     */
    public function getNonPayers(): JsonResponse
    {
        $nonPayers = Resident::whereDoesntHave('payments', function ($query) {
            $query->where('status', 'paid');
        })->get();

        return response()->json([
            'status' => 'success',
            'data' => $nonPayers
        ]);
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(): JsonResponse
    {
        $totalResidents = Resident::count();
        $activeResidents = Resident::where('status', 'active')->count();
        $totalPayers = Resident::whereHas('payments', function ($query) {
            $query->where('status', 'paid');
        })->count();
        $totalNonPayers = $totalResidents - $totalPayers;
        
        $currentMonth = now()->format('Y-m');
        $currentMonthPayments = Payment::where('payment_month', $currentMonth)->where('status', 'paid')->sum('amount');
        $pendingPayments = Payment::where('status', 'pending')->count();
        $overduePayments = Payment::where('status', 'pending')->where('due_date', '<', now())->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_residents' => $totalResidents,
                'active_residents' => $activeResidents,
                'total_payers' => $totalPayers,
                'total_non_payers' => $totalNonPayers,
                'current_month_collection' => $currentMonthPayments,
                'pending_payments' => $pendingPayments,
                'overdue_payments' => $overduePayments,
                'collection_percentage' => $totalResidents > 0 ? round(($totalPayers / $totalResidents) * 100, 2) : 0
            ]
        ]);
    }

    /**
     * Sync data from Google Sheets (placeholder for future implementation)
     */
    public function syncFromGoogleSheets(Request $request): JsonResponse
    {
        // This will be implemented later with Google Sheets API integration
        return response()->json([
            'status' => 'success',
            'message' => 'Google Sheets sync functionality will be implemented in the next phase'
        ]);
    }
}

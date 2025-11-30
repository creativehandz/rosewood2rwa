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

        // By default, only show active residents unless specifically requested
        if (!$request->has('include_inactive')) {
            $query->where('status', 'active');
        }

        // Apply additional filters
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
        try {
            $validated = $request->validate([
                'house_number' => [
                    'required', 
                    'string', 
                    'max:10',
                    Rule::unique('residents')->where(function ($query) use ($request) {
                        return $query->where('house_number', $request->house_number)
                                   ->where('floor', $request->floor);
                    })
                ],
                'property_type' => 'required|in:house,3bhk_flat,villa,2bhk_flat,1bhk_flat,estonia_1,estonia_2,plot',
                'floor' => 'nullable|in:ground_floor,1st_floor,2nd_floor',
                'owner_name' => 'required|string|max:255|min:2',
                'contact_number' => 'required|string|max:20|regex:/^[0-9\-\+\s\(\)]+$/',
                'email' => 'nullable|email|max:255|unique:residents,email',
                'monthly_maintenance' => 'required|numeric|min:0|max:999999',
                'status' => 'nullable|in:active,inactive',
                'current_state' => 'required|in:vacant,occupied',
                'move_in_date' => 'nullable|date',
                'emergency_contact' => 'nullable|string|max:255',
                'emergency_phone' => 'nullable|string|max:20|regex:/^[0-9\-\+\s\(\)]+$/',
                'remarks' => 'nullable|array'
            ], [
                'house_number.required' => 'House number is required',
                'house_number.unique' => 'This house number and floor combination is already registered',
                'property_type.required' => 'Property type is required',
                'property_type.in' => 'Please select a valid property type',
                'floor.in' => 'Please select a valid floor option',
                'owner_name.required' => 'Owner name is required',
                'owner_name.min' => 'Owner name must be at least 2 characters',
                'contact_number.required' => 'Contact number is required',
                'contact_number.regex' => 'Please enter a valid contact number',
                'email.email' => 'Please enter a valid email address',
                'email.unique' => 'This email is already registered',
                'monthly_maintenance.required' => 'Monthly maintenance amount is required',
                'monthly_maintenance.min' => 'Monthly maintenance must be a positive amount',
                'current_state.required' => 'Current state is required',
                'current_state.in' => 'Current state must be either vacant or occupied',
                'move_in_date.date' => 'Please enter a valid date for move-in date',
                'emergency_contact.max' => 'Emergency contact name cannot exceed 255 characters',
                'emergency_phone.regex' => 'Please enter a valid emergency phone number'
            ]);

            // Set default values if not provided
            $validated['status'] = $validated['status'] ?? 'active';
            
            // For backward compatibility, also save as flat_number
            $validated['flat_number'] = $validated['house_number'];

            $resident = Resident::create($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Resident created successfully',
                'data' => $resident
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create resident',
                'error' => $e->getMessage()
            ], 500);
        }
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
        try {
            $validated = $request->validate([
                'house_number' => [
                    'required', 
                    'string', 
                    'max:10',
                    Rule::unique('residents')
                        ->ignore($resident->id)
                        ->where(function ($query) use ($request) {
                            return $query->where('house_number', $request->house_number)
                                       ->where('floor', $request->floor);
                        })
                ],
                'property_type' => 'required|in:house,3bhk_flat,villa,2bhk_flat,1bhk_flat,estonia_1,estonia_2,plot',
                'floor' => 'nullable|in:ground_floor,1st_floor,2nd_floor',
                'owner_name' => 'required|string|max:255|min:2',
                'contact_number' => 'required|string|max:20|regex:/^[0-9\-\+\s\(\)]+$/',
                'email' => ['nullable', 'email', 'max:255', Rule::unique('residents')->ignore($resident->id)],
                'monthly_maintenance' => 'required|numeric|min:0|max:999999',
                'status' => 'nullable|in:active,inactive',
                'current_state' => 'required|in:vacant,occupied',
                'move_in_date' => 'nullable|date',
                'emergency_contact' => 'nullable|string|max:255',
                'emergency_phone' => 'nullable|string|max:20|regex:/^[0-9\-\+\s\(\)]+$/',
                'remarks' => 'nullable|array'
            ], [
                'house_number.required' => 'House number is required',
                'house_number.unique' => 'This house number and floor combination is already registered',
                'property_type.required' => 'Property type is required',
                'property_type.in' => 'Please select a valid property type',
                'floor.in' => 'Please select a valid floor option',
                'owner_name.required' => 'Owner name is required',
                'owner_name.min' => 'Owner name must be at least 2 characters',
                'contact_number.required' => 'Contact number is required',
                'contact_number.regex' => 'Please enter a valid contact number',
                'email.email' => 'Please enter a valid email address',
                'email.unique' => 'This email is already registered',
                'monthly_maintenance.required' => 'Monthly maintenance amount is required',
                'monthly_maintenance.min' => 'Monthly maintenance must be a positive amount',
                'current_state.required' => 'Current state is required',
                'current_state.in' => 'Current state must be either vacant or occupied',
                'move_in_date.date' => 'Please enter a valid date for move-in date',
                'emergency_contact.max' => 'Emergency contact name cannot exceed 255 characters',
                'emergency_phone.regex' => 'Please enter a valid emergency phone number'
            ]);

            // For backward compatibility, also update flat_number
            $validated['flat_number'] = $validated['house_number'];

            $resident->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Resident updated successfully',
                'data' => $resident->fresh()
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update resident',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Resident $resident): JsonResponse
    {
        try {
            // Check if resident has any payments for informational purposes
            $hasPayments = $resident->payments()->exists();
            $paymentCount = $resident->payments()->count();
            
            // Hard delete the resident (payments will be cascade deleted due to foreign key constraint)
            $resident->delete();

            $message = $hasPayments 
                ? "Resident and {$paymentCount} associated payment record(s) deleted successfully"
                : 'Resident deleted successfully';

            return response()->json([
                'status' => 'success',
                'message' => $message
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete resident',
                'error' => $e->getMessage()
            ], 500);
        }
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
            $query->where('status', 'Paid');
        })->count();
        $totalNonPayers = $totalResidents - $totalPayers;
        
        $currentMonth = now()->format('Y-m');
        $currentMonthPayments = Payment::where('payment_month', $currentMonth)->where('status', 'Paid')->sum('amount_paid');
        $pendingPayments = Payment::where('status', 'Pending')->count();
        // Calculate overdue payments as pending payments from previous months
        $overduePayments = Payment::where('status', 'Pending')
            ->where('payment_month', '<', $currentMonth)
            ->count();

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

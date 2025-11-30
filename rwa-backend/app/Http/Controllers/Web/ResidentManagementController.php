<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Resident;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Carbon\Carbon;

class ResidentManagementController extends Controller
{
    /**
     * Display all residents
     */
    public function index(Request $request): View
    {
        $search = $request->get('search');
        $status = $request->get('status', 'all');
        $perPage = $request->get('per_page', 25);

        $query = Resident::query();

        // Apply search filter
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('owner_name', 'LIKE', "%{$search}%")
                  ->orWhere('flat_number', 'LIKE', "%{$search}%")
                  ->orWhere('house_number', 'LIKE', "%{$search}%")
                  ->orWhere('contact_number', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        // Apply status filter
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $residents = $query->orderBy('flat_number')
                          ->orderBy('house_number')
                          ->paginate($perPage);

        // Set pagination path to ensure correct URLs and preserve query parameters
        $residents->withPath(request()->url())->appends($request->except('page'));

        // Get statistics
        $stats = $this->getResidentStats();

        return view('resident-management.index', compact(
            'residents',
            'search',
            'status',
            'perPage',
            'stats'
        ));
    }

    /**
     * Display individual resident details
     */
    public function show(Resident $resident): View
    {
        // Get recent payments for this resident
        $recentPayments = Payment::where('resident_id', $resident->id)
                                ->with('resident')
                                ->orderBy('payment_month', 'desc')
                                ->take(12)
                                ->get();

        // Get payment statistics for this resident
        $paymentStats = $this->getResidentPaymentStats($resident);

        return view('resident-management.show', compact(
            'resident',
            'recentPayments',
            'paymentStats'
        ));
    }

    /**
     * Show create resident form
     */
    public function create(): View
    {
        return view('resident-management.create');
    }

    /**
     * Store new resident
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'house_number' => 'required|string|max:50',
            'floor' => 'nullable|in:ground_floor,1st_floor,2nd_floor',
            'property_type' => 'required|in:house,3bhk_flat,villa,2bhk_flat,1bhk_flat,estonia_1,estonia_2,plot',
            'owner_name' => 'required|string|max:255',
            'contact_number' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive,pending',
            'current_state' => 'nullable|in:occupied,vacant',
            'monthly_maintenance' => 'nullable|numeric|min:0',
            'move_in_date' => 'nullable|date',
            'emergency_contact' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:20',
            'remarks' => 'nullable|string|max:1000',
        ]);

        // Handle remarks conversion from text to array format
        if (!empty($validated['remarks'])) {
            // Split by lines and create proper array format
            $remarkLines = array_filter(explode("\n", $validated['remarks']));
            $remarksArray = [];
            foreach ($remarkLines as $line) {
                $remarksArray[] = [
                    'text' => trim($line),
                    'added_by' => 'Admin',
                    'added_at' => now()->toISOString()
                ];
            }
            $validated['remarks'] = $remarksArray;
        } else {
            $validated['remarks'] = [];
        }

        Resident::create($validated);

        return redirect('/residents')->with('success', 'Resident created successfully.');
    }

    /**
     * Show edit resident form
     */
    public function edit(Resident $resident): View
    {
        return view('resident-management.edit', compact('resident'));
    }

    /**
     * Update resident
     */
    public function update(Request $request, Resident $resident)
    {
        $validated = $request->validate([
            'house_number' => 'required|string|max:50',
            'floor' => 'nullable|in:ground_floor,1st_floor,2nd_floor',
            'property_type' => 'required|in:house,3bhk_flat,villa,2bhk_flat,1bhk_flat,estonia_1,estonia_2,plot',
            'owner_name' => 'required|string|max:255',
            'contact_number' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive,pending',
            'current_state' => 'nullable|in:occupied,vacant',
            'monthly_maintenance' => 'nullable|numeric|min:0',
            'move_in_date' => 'nullable|date',
            'emergency_contact' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:20',
            'remarks' => 'nullable|string|max:1000',
        ]);

        // Handle remarks conversion from text to array format
        if (!empty($validated['remarks'])) {
            // Split by lines and create proper array format
            $remarkLines = array_filter(explode("\n", $validated['remarks']));
            $remarksArray = [];
            foreach ($remarkLines as $line) {
                $remarksArray[] = [
                    'text' => trim($line),
                    'added_by' => 'Admin',
                    'added_at' => now()->toISOString()
                ];
            }
            $validated['remarks'] = $remarksArray;
        } else {
            $validated['remarks'] = [];
        }

        $resident->update($validated);

        return redirect('/residents')->with('success', 'Resident updated successfully.');
    }

    /**
     * Delete resident
     */
    public function destroy(Resident $resident)
    {
        // Check if resident has any payments
        $paymentCount = Payment::where('resident_id', $resident->id)->count();
        
        if ($paymentCount > 0) {
            return back()->withErrors(['delete' => 'Cannot delete resident with existing payment records.']);
        }

        $resident->delete();

        return redirect('/residents')->with('success', 'Resident deleted successfully.');
    }

    /**
     * Export residents to CSV
     */
    public function export(Request $request)
    {
        $search = $request->get('search');
        $status = $request->get('status', 'all');

        $query = Resident::query();

        // Apply filters
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('owner_name', 'LIKE', "%{$search}%")
                  ->orWhere('flat_number', 'LIKE', "%{$search}%")
                  ->orWhere('house_number', 'LIKE', "%{$search}%")
                  ->orWhere('contact_number', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $residents = $query->orderBy('flat_number')->orderBy('house_number')->get();

        $csvData = [];
        $csvData[] = [
            'Flat Number',
            'House Number', 
            'Owner Name',
            'Contact Number',
            'Email',
            'Status',
            'Family Members',
            'Parking Slots',
            'Created Date'
        ];

        foreach ($residents as $resident) {
            $csvData[] = [
                $resident->flat_number,
                $resident->house_number,
                $resident->owner_name,
                $resident->contact_number,
                $resident->email ?: 'N/A',
                ucfirst($resident->status),
                $resident->family_members ?: '0',
                $resident->parking_slots ?: '0',
                $resident->created_at->format('Y-m-d')
            ];
        }

        $filename = "residents_export_" . date('Y-m-d') . ".csv";
        
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
     * Get resident statistics
     */
    private function getResidentStats(): array
    {
        $totalResidents = Resident::count();
        $activeResidents = Resident::where('status', 'active')->count();
        $inactiveResidents = Resident::where('status', 'inactive')->count();

        // Get current month payment statistics
        $currentMonth = Carbon::now()->format('Y-m');
        $paidResidents = Payment::where('payment_month', $currentMonth)
                              ->where('status', 'paid')
                              ->distinct('resident_id')
                              ->count();

        return [
            'total_residents' => $totalResidents,
            'active_residents' => $activeResidents,
            'inactive_residents' => $inactiveResidents,
            'paid_this_month' => $paidResidents,
            'unpaid_this_month' => $activeResidents - $paidResidents,
        ];
    }

    /**
     * Get payment statistics for a specific resident
     */
    private function getResidentPaymentStats(Resident $resident): array
    {
        $totalPayments = Payment::where('resident_id', $resident->id)->count();
        $paidPayments = Payment::where('resident_id', $resident->id)->where('status', 'paid')->count();
        $totalAmountPaid = Payment::where('resident_id', $resident->id)->sum('amount_paid');
        $totalAmountDue = Payment::where('resident_id', $resident->id)->sum('amount_due');
        
        // Get latest payment
        $latestPayment = Payment::where('resident_id', $resident->id)
                              ->orderBy('payment_month', 'desc')
                              ->first();

        return [
            'total_payments' => $totalPayments,
            'paid_payments' => $paidPayments,
            'pending_payments' => $totalPayments - $paidPayments,
            'total_amount_paid' => $totalAmountPaid,
            'total_amount_due' => $totalAmountDue,
            'outstanding_balance' => $totalAmountDue - $totalAmountPaid,
            'latest_payment' => $latestPayment,
            'payment_completion_rate' => $totalPayments > 0 ? round(($paidPayments / $totalPayments) * 100, 1) : 0,
        ];
    }
}
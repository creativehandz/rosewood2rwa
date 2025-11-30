<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Resident;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display the main dashboard
     */
    public function index()
    {
        $currentMonth = Carbon::now()->format('Y-m');
        
        // Get dashboard statistics
        $stats = $this->getDashboardStats();
        
        return view('dashboard.index', compact('stats'));
    }

    /**
     * Get dashboard statistics
     */
    private function getDashboardStats(): array
    {
        $currentMonth = Carbon::now()->format('Y-m');
        
        // Total residents
        $totalResidents = Resident::where('status', 'active')->count();
        
        // Current month payment stats
        $currentMonthPayments = Payment::where('payment_month', $currentMonth)
            ->selectRaw('
                COUNT(DISTINCT resident_id) as paying_residents,
                SUM(CASE WHEN status = "paid" THEN amount_paid ELSE 0 END) as total_collected,
                SUM(amount_due) as total_due
            ')
            ->first();

        $payingResidents = $currentMonthPayments->paying_residents ?? 0;
        $totalCollected = $currentMonthPayments->total_collected ?? 0;
        $totalDue = $currentMonthPayments->total_due ?? 0;
        
        // Calculate collection rate
        $collectionRate = $totalDue > 0 ? round(($totalCollected / $totalDue) * 100, 1) : 0;

        return [
            'total_residents' => $totalResidents,
            'paying_residents' => $payingResidents,
            'collection_rate' => $collectionRate,
            'monthly_collection' => $totalCollected,
            'total_due' => $totalDue,
            'current_month' => Carbon::now()->format('F Y'),
        ];
    }

    /**
     * Get dashboard stats API endpoint
     */
    public function getStats()
    {
        $stats = $this->getDashboardStats();
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'total_residents' => $stats['total_residents'],
                'total_payers' => $stats['paying_residents'],
                'collection_percentage' => $stats['collection_rate'],
                'current_month_collection' => $stats['monthly_collection'],
            ]
        ]);
    }
}
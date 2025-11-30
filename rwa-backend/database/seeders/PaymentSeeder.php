<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\Resident;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all residents
        $residents = Resident::all();
        
        if ($residents->isEmpty()) {
            $this->command->info('No residents found. Please run ResidentSeeder first.');
            return;
        }

        $this->command->info('Creating payments for ' . $residents->count() . ' residents...');

        // Generate payments for last 6 months
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $months[] = Carbon::now()->subMonths($i)->format('Y-m');
        }

        $paymentTypes = [
            'paid' => 60,      // 60% fully paid
            'partial' => 15,   // 15% partial payments
            'pending' => 15,   // 15% pending payments
            'overdue' => 10    // 10% overdue payments
        ];

        $totalPayments = 0;

        foreach ($residents as $resident) {
            foreach ($months as $month) {
                // Determine payment type based on distribution
                $rand = rand(1, 100);
                $cumulativePercentage = 0;
                $paymentType = 'pending';

                foreach ($paymentTypes as $type => $percentage) {
                    $cumulativePercentage += $percentage;
                    if ($rand <= $cumulativePercentage) {
                        $paymentType = $type;
                        break;
                    }
                }

                // Adjust overdue logic - only for older months
                if ($paymentType === 'overdue' && $month >= Carbon::now()->subMonth()->format('Y-m')) {
                    $paymentType = 'pending';
                }

                // Create payment based on type
                $amountDue = rand(2000, 5000);
                
                switch ($paymentType) {
                    case 'paid':
                        $amountPaid = $amountDue;
                        $status = 'Paid';
                        $paymentDate = Carbon::now()->subDays(rand(1, 30));
                        // 70% UPI (QR Scanner), 30% Cash - realistic distribution for RWA
                        $paymentMethod = (rand(1, 100) <= 70) ? 'UPI' : 'Cash';
                        $transactionId = $paymentMethod === 'UPI' ? 'UPI' . rand(100000000000, 999999999999) : 'CASH' . rand(1000, 9999);
                        break;
                    case 'partial':
                        $amountPaid = $amountDue * (rand(20, 80) / 100);
                        $status = 'Partial';
                        $paymentDate = Carbon::now()->subDays(rand(1, 30));
                        // 70% UPI (QR Scanner), 30% Cash - realistic distribution for RWA
                        $paymentMethod = (rand(1, 100) <= 70) ? 'UPI' : 'Cash';
                        $transactionId = $paymentMethod === 'UPI' ? 'UPI' . rand(100000000000, 999999999999) : 'CASH' . rand(1000, 9999);
                        break;
                    case 'pending':
                        $amountPaid = 0;
                        $status = 'Pending';
                        $paymentDate = null;
                        $paymentMethod = null;
                        $transactionId = null;
                        break;
                    case 'overdue':
                        $amountPaid = 0;
                        $status = 'Overdue';
                        $paymentDate = null;
                        $paymentMethod = null;
                        $transactionId = null;
                        break;
                }

                Payment::create([
                    'resident_id' => $resident->id,
                    'payment_month' => $month,
                    'amount_due' => $amountDue,
                    'amount_paid' => $amountPaid,
                    'payment_date' => $paymentDate,
                    'payment_method' => $paymentMethod,
                    'transaction_id' => $transactionId,
                    'status' => $status,
                    'remarks' => $paymentType === 'overdue' ? 'Payment overdue' : null,
                    'sheet_row_id' => rand(70, 90) <= 80 ? rand(2, 100) : null,
                    'last_synced_at' => rand(70, 90) <= 80 ? Carbon::now()->subDays(rand(1, 7)) : null
                ]);
                
                $totalPayments++;
            }
        }

        $this->command->info("Created {$totalPayments} payment records.");

        // Create some additional test scenarios
        $this->createTestScenarios($residents);
        
        // Display statistics
        $this->generateStatistics();
    }

    /**
     * Create specific test scenarios for development and testing
     */
    private function createTestScenarios($residents): void
    {
        if ($residents->count() < 5) return;

        $this->command->info('Creating test scenarios...');

        // Scenario 1: Consistent payer (always pays on time)
        $consistentPayer = $residents->first();
        $months = ['2024-07', '2024-08', '2024-09', '2024-10', '2024-11'];
        foreach ($months as $month) {
            Payment::create([
                'resident_id' => $consistentPayer->id,
                'payment_month' => $month,
                'amount_due' => 3000,
                'amount_paid' => 3000,
                'payment_date' => Carbon::createFromFormat('Y-m', $month)->addDays(5),
                'payment_method' => 'UPI',
                'transaction_id' => 'UPI' . rand(100000000000, 999999999999),
                'status' => 'Paid',
                'sheet_row_id' => rand(2, 100),
                'last_synced_at' => Carbon::now()->subDays(rand(1, 7))
            ]);
        }

        // Scenario 2: Chronic defaulter (multiple overdue payments)
        $defaulter = $residents->skip(1)->first();
        $oldMonths = ['2024-06', '2024-07', '2024-08', '2024-09'];
        foreach ($oldMonths as $month) {
            Payment::create([
                'resident_id' => $defaulter->id,
                'payment_month' => $month,
                'amount_due' => 3500,
                'amount_paid' => 0,
                'payment_date' => null,
                'payment_method' => null,
                'transaction_id' => null,
                'status' => 'Overdue',
                'remarks' => 'Payment overdue - multiple notices sent'
            ]);
        }

        // Scenario 3: Partial payment pattern
        $partialPayer = $residents->skip(2)->first();
        $recentMonths = ['2024-09', '2024-10', '2024-11'];
        foreach ($recentMonths as $month) {
            $amountDue = 4000;
            $amountPaid = $amountDue * 0.6; // 60% paid
            
            Payment::create([
                'resident_id' => $partialPayer->id,
                'payment_month' => $month,
                'amount_due' => $amountDue,
                'amount_paid' => $amountPaid,
                'payment_date' => Carbon::createFromFormat('Y-m', $month)->addDays(10),
                'payment_method' => 'Cash',
                'transaction_id' => 'CASH' . rand(1000, 9999),
                'status' => 'Partial',
                'remarks' => 'Partial payment - balance pending'
            ]);
        }

        // Scenario 4: High-value payments (premium units)
        if ($residents->count() > 3) {
            $premiumResident = $residents->skip(3)->first();
            foreach (['2024-10', '2024-11'] as $month) {
                Payment::create([
                    'resident_id' => $premiumResident->id,
                    'payment_month' => $month,
                    'amount_due' => 8000,
                    'amount_paid' => 8000,
                    'payment_date' => Carbon::createFromFormat('Y-m', $month)->addDays(3),
                    'payment_method' => 'UPI',
                    'transaction_id' => 'UPI' . rand(100000000000, 999999999999),
                    'status' => 'Paid',
                    'sheet_row_id' => rand(2, 100),
                    'last_synced_at' => Carbon::now()->subDays(rand(1, 7))
                ]);
            }
        }

        // Scenario 5: Recent payments with different methods
        if ($residents->count() > 4) {
            $methodTester = $residents->skip(4)->first();
            $methods = ['Cash', 'UPI']; // Only Cash and UPI for RWA
            foreach ($methods as $index => $method) {
                Payment::create([
                    'resident_id' => $methodTester->id,
                    'payment_month' => '2024-' . sprintf('%02d', 9 + $index),
                    'amount_due' => 3000,
                    'amount_paid' => 3000,
                    'payment_date' => Carbon::now()->subDays(rand(5, 25)),
                    'payment_method' => $method,
                    'transaction_id' => $method === 'UPI' ? 'UPI' . rand(100000000000, 999999999999) : 'CASH' . rand(1000, 9999),
                    'status' => 'Paid'
                ]);
            }
        }

        $this->command->info('Test scenarios created successfully.');
    }

    /**
     * Generate payment statistics for information
     */
    private function generateStatistics(): void
    {
        $stats = [
            'total_payments' => Payment::count(),
            'total_amount_due' => Payment::sum('amount_due'),
            'total_amount_paid' => Payment::sum('amount_paid'),
            'status_breakdown' => [
                'paid' => Payment::where('status', 'Paid')->count(),
                'partial' => Payment::where('status', 'Partial')->count(),
                'pending' => Payment::where('status', 'Pending')->count(),
                'overdue' => Payment::where('status', 'Overdue')->count(),
            ],
            'method_breakdown' => [
                'cash' => Payment::where('payment_method', 'Cash')->count(),
                'upi' => Payment::where('payment_method', 'UPI')->count(),
                'no_method' => Payment::whereNull('payment_method')->count(),
            ]
        ];

        $this->command->table(
            ['Metric', 'Value'],
            [
                ['Total Payments', $stats['total_payments']],
                ['Total Amount Due', '₹' . number_format($stats['total_amount_due'], 2)],
                ['Total Amount Paid', '₹' . number_format($stats['total_amount_paid'], 2)],
                ['Collection Rate', round(($stats['total_amount_paid'] / $stats['total_amount_due']) * 100, 2) . '%'],
            ]
        );

        $this->command->table(
            ['Status', 'Count'],
            [
                ['Paid', $stats['status_breakdown']['paid']],
                ['Partial', $stats['status_breakdown']['partial']],
                ['Pending', $stats['status_breakdown']['pending']],
                ['Overdue', $stats['status_breakdown']['overdue']],
            ]
        );

        $this->command->table(
            ['Payment Method', 'Count'],
            [
                ['UPI (QR Scanner)', $stats['method_breakdown']['upi']],
                ['Cash', $stats['method_breakdown']['cash']],
                ['No Method Set', $stats['method_breakdown']['no_method']],
            ]
        );
    }
}
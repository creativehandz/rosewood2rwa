<?php

namespace Database\Seeders;

use App\Models\MaintenanceCharge;
use App\Models\Payment;
use App\Models\Resident;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Faker\Factory as Faker;

class MaintenanceChargeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        
        // Create maintenance charges for the last 6 months
        $months = [
            '2024-06', '2024-07', '2024-08', '2024-09', '2024-10', '2024-11'
        ];

        foreach ($months as $month) {
            $maintenanceCharge = MaintenanceCharge::create([
                'month' => $month,
                'basic_maintenance' => 2500.00,
                'additional_charges' => $faker->randomFloat(2, 0, 500),
                'discount' => $faker->randomFloat(2, 0, 100),
                'penalty' => $faker->randomFloat(2, 0, 200),
                'due_date' => Carbon::parse($month . '-15'),
                'description' => 'Monthly maintenance charges for ' . Carbon::parse($month . '-01')->format('F Y'),
                'status' => 'active'
            ]);

            $this->command->info("Created maintenance charge for {$month}");

            // Create payment records for residents
            $this->createPaymentsForMonth($maintenanceCharge, $faker);
        }
    }

    /**
     * Create payment records for a specific month
     */
    private function createPaymentsForMonth(MaintenanceCharge $maintenanceCharge, $faker): void
    {
        $residents = Resident::where('status', 'active')->get();
        $totalResidents = $residents->count();
        
        if ($totalResidents == 0) {
            $this->command->warn("No active residents found!");
            return;
        }

        // Calculate how many residents should have each status
        $paidCount = (int) ($totalResidents * 0.65); // 65% paid
        $partialCount = (int) ($totalResidents * 0.10); // 10% partial
        $pendingCount = (int) ($totalResidents * 0.15); // 15% pending
        $overdueCount = (int) ($totalResidents * 0.10); // 10% overdue
        
        // Shuffle residents to randomize assignment
        $residents = $residents->shuffle();
        
        $processed = 0;
        
        // Create PAID payments
        for ($i = 0; $i < $paidCount && $processed < $totalResidents; $i++, $processed++) {
            $resident = $residents[$processed];
            $this->createPayment($resident, $maintenanceCharge, 'Paid', $faker);
        }
        
        // Create PARTIAL payments
        for ($i = 0; $i < $partialCount && $processed < $totalResidents; $i++, $processed++) {
            $resident = $residents[$processed];
            $this->createPayment($resident, $maintenanceCharge, 'Partial', $faker);
        }
        
        // Create PENDING payments
        for ($i = 0; $i < $pendingCount && $processed < $totalResidents; $i++, $processed++) {
            $resident = $residents[$processed];
            $this->createPayment($resident, $maintenanceCharge, 'Pending', $faker);
        }
        
        // Create OVERDUE payments
        for ($i = 0; $i < $overdueCount && $processed < $totalResidents; $i++, $processed++) {
            $resident = $residents[$processed];
            $this->createPayment($resident, $maintenanceCharge, 'Overdue', $faker);
        }
        
        // Handle remaining residents (make them paid)
        while ($processed < $totalResidents) {
            $resident = $residents[$processed];
            $this->createPayment($resident, $maintenanceCharge, 'Paid', $faker);
            $processed++;
        }

        $this->command->info("Created {$processed} payment records for {$maintenanceCharge->month}");
    }

    /**
     * Create a payment record for a resident
     */
    private function createPayment(Resident $resident, MaintenanceCharge $maintenanceCharge, string $status, $faker): void
    {
        $baseAmount = $resident->monthly_maintenance ?: 2500.00;
        $totalDue = $baseAmount + $maintenanceCharge->additional_charges - $maintenanceCharge->discount;
        
        // Add late fee for overdue payments
        if ($status === 'Overdue') {
            $totalDue += $faker->randomFloat(2, 50, 200);
        }

        // Calculate amount paid based on status
        $amountPaid = 0;
        $paymentDate = null;
        $paymentMethod = null;
        $transactionId = null;

        switch ($status) {
            case 'Paid':
                $amountPaid = $totalDue;
                $paymentDate = $faker->dateTimeBetween(
                    Carbon::parse($maintenanceCharge->month . '-01'),
                    Carbon::parse($maintenanceCharge->month . '-01')->addDays(20)
                );
                $paymentMethod = $faker->randomElement(['Cash', 'UPI', 'Bank Transfer']);
                $transactionId = $paymentMethod !== 'Cash' 
                    ? 'TXN' . $faker->numberBetween(100000, 999999) 
                    : null;
                break;
                
            case 'Partial':
                $amountPaid = $faker->randomFloat(2, $totalDue * 0.3, $totalDue * 0.8);
                $paymentDate = $faker->dateTimeBetween(
                    Carbon::parse($maintenanceCharge->month . '-01'),
                    Carbon::parse($maintenanceCharge->month . '-01')->addDays(25)
                );
                $paymentMethod = $faker->randomElement(['Cash', 'UPI', 'Bank Transfer']);
                $transactionId = $paymentMethod !== 'Cash' 
                    ? 'TXN' . $faker->numberBetween(100000, 999999) 
                    : null;
                break;
                
            case 'Pending':
            case 'Overdue':
                $amountPaid = 0;
                // No payment date, method, or transaction ID
                break;
        }

        // Create payment record using the correct table structure
        Payment::create([
            'resident_id' => $resident->id,
            'amount_due' => $totalDue,
            'amount_paid' => $amountPaid,
            'payment_month' => $maintenanceCharge->month,
            'payment_date' => $paymentDate,
            'status' => $status,
            'payment_method' => $paymentMethod,
            'transaction_id' => $transactionId,
            'remarks' => $status === 'Partial' 
                ? 'Partial payment received. Remaining: ₹' . number_format($totalDue - $amountPaid, 2)
                : ($status === 'Overdue' 
                    ? 'Payment overdue. Late fee applied.' 
                    : null),
        ]);
    }
}

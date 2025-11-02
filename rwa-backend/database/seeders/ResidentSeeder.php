<?php

namespace Database\Seeders;

use App\Models\Resident;
use App\Models\Payment;
use App\Models\MaintenanceCharge;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ResidentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create maintenance charge for current month
        MaintenanceCharge::create([
            'month' => Carbon::now()->format('Y-m'),
            'basic_maintenance' => 2500.00,
            'additional_charges' => 0.00,
            'discount' => 0.00,
            'penalty' => 0.00,
            'due_date' => Carbon::now()->addDays(15),
            'description' => 'Monthly maintenance charges for ' . Carbon::now()->format('F Y'),
            'status' => 'active'
        ]);

        // Sample residents data
        $residents = [
            [
                'flat_number' => 'A-101',
                'owner_name' => 'Rajesh Kumar',
                'contact_number' => '9876543210',
                'email' => 'rajesh@example.com',
                'address' => 'Flat A-101, Rosewood Apartment',
                'monthly_maintenance' => 2500.00,
                'status' => 'active'
            ],
            [
                'flat_number' => 'A-102',
                'owner_name' => 'Priya Sharma',
                'contact_number' => '9876543211',
                'email' => 'priya@example.com',
                'address' => 'Flat A-102, Rosewood Apartment',
                'monthly_maintenance' => 2500.00,
                'status' => 'active'
            ],
            [
                'flat_number' => 'A-103',
                'owner_name' => 'Amit Patel',
                'contact_number' => '9876543212',
                'email' => 'amit@example.com',
                'address' => 'Flat A-103, Rosewood Apartment',
                'monthly_maintenance' => 2500.00,
                'status' => 'active'
            ],
            [
                'flat_number' => 'B-201',
                'owner_name' => 'Sunita Gupta',
                'contact_number' => '9876543213',
                'email' => 'sunita@example.com',
                'address' => 'Flat B-201, Rosewood Apartment',
                'monthly_maintenance' => 3000.00,
                'status' => 'active'
            ],
            [
                'flat_number' => 'B-202',
                'owner_name' => 'Vikram Singh',
                'contact_number' => '9876543214',
                'email' => 'vikram@example.com',
                'address' => 'Flat B-202, Rosewood Apartment',
                'monthly_maintenance' => 3000.00,
                'status' => 'active'
            ],
            [
                'flat_number' => 'C-301',
                'owner_name' => 'Meera Jain',
                'contact_number' => '9876543215',
                'email' => 'meera@example.com',
                'address' => 'Flat C-301, Rosewood Apartment',
                'monthly_maintenance' => 3500.00,
                'status' => 'active'
            ]
        ];

        foreach ($residents as $residentData) {
            $resident = Resident::create($residentData);
            
            // Create some sample payments (some paid, some pending)
            $isPayer = rand(0, 1); // 50% chance of being a payer
            
            if ($isPayer) {
                // Create paid payment for current month
                Payment::create([
                    'resident_id' => $resident->id,
                    'amount' => $resident->monthly_maintenance,
                    'payment_date' => Carbon::now()->subDays(rand(1, 15)),
                    'due_date' => Carbon::now()->addDays(15),
                    'payment_month' => Carbon::now()->format('Y-m'),
                    'status' => 'paid',
                    'payment_method' => ['cash', 'online', 'cheque'][rand(0, 2)],
                    'transaction_reference' => 'TXN' . rand(100000, 999999),
                    'remarks' => 'Payment received on time'
                ]);
                
                // Create previous month payment as well
                Payment::create([
                    'resident_id' => $resident->id,
                    'amount' => $resident->monthly_maintenance,
                    'payment_date' => Carbon::now()->subMonth()->subDays(rand(1, 10)),
                    'due_date' => Carbon::now()->subMonth()->addDays(15),
                    'payment_month' => Carbon::now()->subMonth()->format('Y-m'),
                    'status' => 'paid',
                    'payment_method' => ['cash', 'online', 'cheque'][rand(0, 2)],
                    'transaction_reference' => 'TXN' . rand(100000, 999999),
                    'remarks' => 'Previous month payment'
                ]);
            } else {
                // Create pending payment for current month
                Payment::create([
                    'resident_id' => $resident->id,
                    'amount' => $resident->monthly_maintenance,
                    'payment_date' => null,
                    'due_date' => Carbon::now()->addDays(15),
                    'payment_month' => Carbon::now()->format('Y-m'),
                    'status' => 'pending',
                    'payment_method' => null,
                    'transaction_reference' => null,
                    'remarks' => 'Payment pending'
                ]);
            }
        }
    }
}

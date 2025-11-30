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

        // Sample residents data with new schema
        $residents = [
            [
                'flat_number' => 'A-101',
                'house_number' => 'A-101',
                'floor' => '1st_floor',
                'owner_name' => 'Rajesh Kumar',
                'contact_number' => '9876543210',
                'email' => 'rajesh@example.com',
                'current_state' => 'Occupied',
                'address' => 'A-101, Rosewood Apartments',
                'status' => 'active',
                'monthly_maintenance' => 2500.00,
                'remarks' => 'Active resident'
            ],
            [
                'flat_number' => 'A-102',
                'house_number' => 'A-102',
                'floor' => '1st_floor',
                'owner_name' => 'Priya Sharma',
                'contact_number' => '9876543211',
                'email' => 'priya@example.com',
                'current_state' => 'Occupied',
                'address' => 'A-102, Rosewood Apartments',
                'status' => 'active',
                'monthly_maintenance' => 2500.00,
                'remarks' => 'Active resident'
            ],
            [
                'flat_number' => 'A-103',
                'house_number' => 'A-103',
                'floor' => '1st_floor',
                'owner_name' => 'Amit Patel',
                'contact_number' => '9876543212',
                'email' => 'amit@example.com',
                'current_state' => 'Occupied',
                'address' => 'A-103, Rosewood Apartments',
                'status' => 'active',
                'monthly_maintenance' => 2500.00,
                'remarks' => 'Active resident'
            ],
            [
                'flat_number' => 'B-201',
                'house_number' => 'B-201',
                'floor' => '2nd_floor',
                'owner_name' => 'Sunita Gupta',
                'contact_number' => '9876543213',
                'email' => 'sunita@example.com',
                'current_state' => 'Occupied',
                'address' => 'B-201, Rosewood Apartments',
                'status' => 'active',
                'monthly_maintenance' => 3000.00,
                'remarks' => 'Active resident'
            ],
            [
                'flat_number' => 'B-202',
                'house_number' => 'B-202',
                'floor' => '2nd_floor',
                'owner_name' => 'Vikram Singh',
                'contact_number' => '9876543214',
                'email' => 'vikram@example.com',
                'current_state' => 'Occupied',
                'address' => 'B-202, Rosewood Apartments',
                'status' => 'active',
                'monthly_maintenance' => 3000.00,
                'remarks' => 'Active resident'
            ],
            [
                'flat_number' => 'C-301',
                'house_number' => 'C-301',
                'floor' => 'ground_floor',
                'owner_name' => 'Meera Jain',
                'contact_number' => '9876543215',
                'email' => 'meera@example.com',
                'current_state' => 'Occupied',
                'address' => 'C-301, Rosewood Apartments',
                'status' => 'active',
                'monthly_maintenance' => 3500.00,
                'remarks' => 'Active resident'
            ],
            [
                'flat_number' => 'C-302',
                'house_number' => 'C-302',
                'floor' => 'ground_floor',
                'owner_name' => 'Arjun Verma',
                'contact_number' => '9876543216',
                'email' => 'arjun@example.com',
                'current_state' => 'Vacant',
                'address' => 'C-302, Rosewood Apartments',
                'status' => 'inactive',
                'monthly_maintenance' => 3500.00,
                'remarks' => 'Unit available for rent'
            ],
            [
                'flat_number' => 'D-401',
                'house_number' => 'D-401',
                'floor' => '1st_floor',
                'owner_name' => 'Kavya Nair',
                'contact_number' => '9876543217',
                'email' => 'kavya@example.com',
                'current_state' => 'Occupied',
                'address' => 'D-401, Rosewood Apartments',
                'status' => 'active',
                'monthly_maintenance' => 4000.00,
                'remarks' => 'New resident'
            ]
        ];

        foreach ($residents as $residentData) {
            Resident::create($residentData);
        }
    }
}

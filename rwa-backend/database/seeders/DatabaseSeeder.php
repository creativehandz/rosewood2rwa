<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a test admin user
        User::factory()->create([
            'name' => 'RWA Admin',
            'email' => 'admin@rosewood.com',
        ]);

        // Seed residents and payments data
        $this->call([
            ResidentSeeder::class,
            MaintenanceChargeSeeder::class,
        ]);
    }
}

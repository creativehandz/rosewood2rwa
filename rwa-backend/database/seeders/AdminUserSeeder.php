<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user if it doesn't exist
        $adminEmail = 'admin@rwa.com';
        
        if (!User::where('email', $adminEmail)->exists()) {
            User::create([
                'name' => 'RWA Admin',
                'email' => $adminEmail,
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
            ]);
            
            echo "Admin user created: {$adminEmail} with password: admin123\n";
        } else {
            echo "Admin user already exists: {$adminEmail}\n";
        }
    }
}
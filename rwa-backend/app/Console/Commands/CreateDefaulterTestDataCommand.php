<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Payment;
use App\Models\Resident;
use Carbon\Carbon;

class CreateDefaulterTestDataCommand extends Command
{
    protected $signature = 'test:create-defaulters';
    protected $description = 'Create test data for defaulters (payments overdue 3+ months)';

    public function handle()
    {
        $this->info('Creating test defaulter data...');
        
        // Get a resident to create old overdue payments for
        $resident = Resident::first();
        
        if (!$resident) {
            $this->error('No residents found. Please create some residents first.');
            return;
        }
        
        // Create payments for 4 months ago that are overdue
        $months = [
            Carbon::now()->subMonths(4)->format('Y-m'),
            Carbon::now()->subMonths(5)->format('Y-m'),
            Carbon::now()->subMonths(6)->format('Y-m'),
        ];
        
        foreach ($months as $month) {
            // Check if payment already exists
            $existingPayment = Payment::where('resident_id', $resident->id)
                ->where('payment_month', $month)
                ->first();
                
            if (!$existingPayment) {
                Payment::create([
                    'resident_id' => $resident->id,
                    'payment_month' => $month,
                    'amount_due' => $resident->monthly_maintenance ?? 800,
                    'amount_paid' => 0, // No payment made - full default
                    'status' => 'Overdue',
                    'remarks' => 'Test defaulter data - ' . Carbon::parse($month . '-01')->format('F Y')
                ]);
                
                $this->line("Created overdue payment for {$month}");
            } else {
                $this->line("Payment for {$month} already exists");
            }
        }
        
        // Create a second defaulter with partial payments
        $resident2 = Resident::skip(1)->first();
        
        if ($resident2) {
            $months = [
                Carbon::now()->subMonths(3)->format('Y-m'),
                Carbon::now()->subMonths(4)->format('Y-m'),
            ];
            
            foreach ($months as $index => $month) {
                $existingPayment = Payment::where('resident_id', $resident2->id)
                    ->where('payment_month', $month)
                    ->first();
                    
                if (!$existingPayment) {
                    Payment::create([
                        'resident_id' => $resident2->id,
                        'payment_month' => $month,
                        'amount_due' => $resident2->monthly_maintenance ?? 800,
                        'amount_paid' => $index == 0 ? 400 : 0, // Partial payment on first month
                        'status' => $index == 0 ? 'Partial' : 'Overdue',
                        'remarks' => 'Test defaulter data - ' . Carbon::parse($month . '-01')->format('F Y')
                    ]);
                    
                    $this->line("Created overdue payment for {$month} (resident 2)");
                } else {
                    $this->line("Payment for {$month} already exists (resident 2)");
                }
            }
        }
        
        $this->info('Test defaulter data created successfully!');
        $this->line('You can now view defaulters at: http://127.0.0.1:8000/payment-management/defaulters');
    }
}
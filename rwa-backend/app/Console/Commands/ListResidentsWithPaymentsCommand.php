<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Payment;
use App\Models\Resident;

class ListResidentsWithPaymentsCommand extends Command
{
    protected $signature = 'list:residents-payments';
    protected $description = 'List residents and their payments';

    public function handle()
    {
        $residents = Resident::with(['payments' => function($query) {
            $query->orderBy('payment_month');
        }])->take(3)->get();
        
        foreach ($residents as $resident) {
            $this->info("Resident ID: {$resident->id} - {$resident->owner_name} - House: {$resident->house_number} {$resident->floor}");
            $this->line("Monthly Maintenance: ₹{$resident->monthly_maintenance}");
            
            if ($resident->payments->count() > 0) {
                $this->line("Payments:");
                foreach ($resident->payments as $payment) {
                    $this->line("  {$payment->payment_month}: Due=₹{$payment->amount_due}, Paid=₹{$payment->amount_paid}, Status={$payment->status}");
                }
            } else {
                $this->line("No payments found");
            }
            $this->line("");
        }
    }
}